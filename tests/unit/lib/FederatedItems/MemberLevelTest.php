<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\FederatedItems;

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\FederatedItems\MemberLevel;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Tools\Model\SimpleDataStore;
use Test\TestCase;

class MemberLevelTest extends TestCase {
	public function testManagePublishesOneActivityAfterPersistingTheNewLevel(): void {
		$memberRequest = $this->createMock(MemberRequest::class);
		$membershipService = $this->createMock(MembershipService::class);
		$eventService = $this->createMock(EventService::class);
		$item = new MemberLevel(
			$memberRequest,
			$membershipService,
			$eventService,
			$this->createMock(ConfigService::class),
		);
		$circle = $this->createMock(Circle::class);
		$member = (new Member())->setSingleId('bob')->setLevel(Member::LEVEL_MEMBER);
		$event = (new FederatedEvent())
			->setCircle($circle)
			->setMember($member)
			->setData(new SimpleDataStore(['level' => Member::LEVEL_MODERATOR]));

		$memberRequest->expects($this->once())
			->method('updateLevel')
			->with($this->callback(static fn (Member $updated): bool => $updated->getLevel() === Member::LEVEL_MODERATOR));
		$membershipService->expects($this->once())->method('onUpdate')->with('bob');
		$eventService->expects($this->once())->method('memberLevelEditing')->with($event);

		$item->manage($event);
	}
}
