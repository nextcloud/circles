<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\FederatedItems\MemberDisplayName;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\FederatedUserService;
use OCP\Accounts\UserUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<UserUpdatedEvent|Event> */
class AccountUpdated implements IEventListener {
	public function __construct(
		private CircleRequest $circleRequest,
		private CircleService $circleService,
		private FederatedEventService $federatedEventService,
		private FederatedUserService $federatedUserService,
		private LoggerInterface $logger,
		private MemberRequest $memberRequest,
	) {
	}


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof UserUpdatedEvent) && !($event instanceof UserChangedEvent)) {
			return;
		}

		try {
			$user = $event->getUser();
			$federatedUser = $this->federatedUserService->getLocalFederatedUser($user->getUID());

			$this->memberRequest->updateDisplayName($federatedUser->getSingleId(), $user->getDisplayName());
			$this->circleRequest->updateDisplayName($federatedUser->getSingleId(), $user->getDisplayName());
			$this->federatedUserService->setCurrentUser($federatedUser);

			$probe = new CircleProbe();
			$probe->includeSystemCircles()
				->mustBeMember()
				->canBeRequestingMembership();

			// cannot use probeCircles() as we also want to update name on almost-members (invited/requesting)
			$circles = $this->circleService->getCircles($probe);

			foreach ($circles as $circle) {
				// we are only interested in direct membership
				if ($circle->getInitiator()->getSingleId() !== $federatedUser->getSingleId()) {
					continue;
				}

				$event = new FederatedEvent(MemberDisplayName::class);
				$event->setCircle($circle);
				$event->getParams()->s('displayName', $user->getDisplayName());

				try {
					$this->federatedEventService->newEvent($event);
				} catch (Exception $e) {
					$this->logger->warning('issue on sync circle on user update', ['exception' => $e, 'event' => $event]);
				}
			}
		} catch (Exception $e) {
			$this->logger->warning('issue on sync circles data on user update', ['exception' => $e]);
		}
	}
}
