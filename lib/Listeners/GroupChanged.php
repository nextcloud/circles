<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupChangedEvent;

/** @template-implements IEventListener<GroupChangedEvent|Event> */
class GroupChanged implements IEventListener {
	public function __construct(
		private readonly FederatedUserService $federatedUserService,
		private readonly CircleService $circleService,
		private readonly CircleRequest $circleRequest,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof GroupChangedEvent)) {
			return;
		}

		if ($event->getFeature() !== 'displayName') {
			return;
		}

		$groupId = $event->getGroup()->getGID();

		$circle = new Circle();
		$circle->setName('group:' . $groupId)
			->setConfig(Circle::CFG_SYSTEM | Circle::CFG_NO_OWNER | Circle::CFG_HIDDEN)
			->setSource(Member::TYPE_GROUP);

		$owner = $this->federatedUserService->getAppInitiator(
			Application::APP_ID,
			Member::APP_CIRCLES,
			Application::APP_NAME
		);
		$member = new Member();
		$member->importFromIFederatedUser($owner);
		$member->setLevel(Member::LEVEL_OWNER)
			->setStatus(Member::STATUS_MEMBER);
		$circle->setOwner($member);

		try {
			$this->federatedUserService->setCurrentUser($owner);
			$circle = $this->circleRequest->searchCircle($circle);
		} catch (CircleNotFoundException) {
			return;
		}

		$this->circleService->updateDisplayName($circle->getSingleId(), $event->getValue());
	}
}
