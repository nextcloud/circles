<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use OCA\Circles\Db\ShareWrapperRequest;
use OCA\Circles\Service\ShareWrapperService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Psr\Log\LoggerInterface;

/**
 * Event listener for file/folder operations to invalidate team share cache
 *
 * @template-implements IEventListener<NodeCreatedEvent|NodeDeletedEvent|NodeRenamedEvent|NodeWrittenEvent>
 */
class NodeEventListener implements IEventListener {
	public function __construct(
		private readonly ShareWrapperService $shareWrapperService,
		private readonly ShareWrapperRequest $shareWrapperRequest,
		private readonly LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof NodeCreatedEvent
			|| $event instanceof NodeDeletedEvent
			|| $event instanceof NodeRenamedEvent
			|| $event instanceof NodeWrittenEvent)) {
			return;
		}

		try {
			/** @var Node $node */
			$node = $event instanceof NodeRenamedEvent ? $event->getTarget() : $event->getNode();
			$this->invalidateCacheForNode($node);
		} catch (NotFoundException|NotPermittedException $e) {
			$this->logger->error('Failed to process node event: ' . $e->getMessage());
		}
	}

	/**
	 * Invalidate cache for a node and all its parent folders
	 * This ensures cache is cleared when files in team-shared folders are modified
	 */
	private function invalidateCacheForNode(Node $node): void {
		$affectedCircles = [];
		$visitedNodeIds = [];

		// Check if this node is directly shared with circles
		try {
			$shares = $this->shareWrapperRequest->getSharesByFileId($node->getId(), false);
			foreach ($shares as $share) {
				$affectedCircles[$share->getSharedWith()] = true;
			}
			$visitedNodeIds[$node->getId()] = true;
		} catch (\Exception $e) {
			$this->logger->error('Failed to get shares for node ' . $node->getId() . ': ' . $e->getMessage());
		}

		// Check parent folders (file might be inside a shared folder)
		try {
			$current = $node;

			while (true) {
				$parent = $current->getParent();

				// Stop if we've reached the root (parent is null or same as current)
				if ($parent === null || $parent->getId() === $current->getId()) {
					break;
				}

				// Detect infinite loop: if we've already visited this node ID, stop
				if (isset($visitedNodeIds[$parent->getId()])) {
					$this->logger->debug('Detected cycle in folder hierarchy at node ' . $parent->getId());
					break;
				}

				$visitedNodeIds[$parent->getId()] = true;

				// Check if parent is shared
				$parentShares = $this->shareWrapperRequest->getSharesByFileId($parent->getId(), false);
				foreach ($parentShares as $share) {
					$affectedCircles[$share->getSharedWith()] = true;
				}

				$current = $parent;
			}
		} catch (NotFoundException|NotPermittedException $e) {
			$this->logger->debug('Stopped parent traversal: ' . $e->getMessage());
		}

		// Invalidate cache for all affected circles
		if ($affectedCircles !== []) {
			foreach (array_keys($affectedCircles) as $circleId) {
				$this->shareWrapperService->clearCacheForCircle($circleId);
			}

			$this->logger->debug(
				'Invalidated cache for node ' . $node->getId() .
				' affecting ' . count($affectedCircles) . ' circle(s), ' .
				'traversed ' . count($visitedNodeIds) . ' level(s)'
			);
		}
	}
}
