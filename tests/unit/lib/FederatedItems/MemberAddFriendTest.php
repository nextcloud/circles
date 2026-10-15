<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\FederatedItems;

use JsonSerializable;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\FederatedItems\MassiveMemberAdd;
use OCA\Circles\FederatedItems\SingleMemberAdd;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\EventService;
use PHPUnit\Framework\TestCase;

class MemberAddFriendTest extends TestCase {
	/**
	 * @dataProvider friendModes
	 */
	public function testSingleAddRejectsvisitorInBothFriendModes(bool $friend): void {
		[$event, $target] = $this->createEvent(Member::LEVEL_VISITOR, $friend);
		$eventService = $this->createMock(EventService::class);
		$eventService->expects($this->never())->method('memberPreparing');
		$handler = $this->singleHandler($target, $eventService);

		$this->expectException(MemberLevelException::class);
		$handler->verify($event);
	}

	public function testSingleAddStillAllowsMemberInFriendCircle(): void {
		[$event, $target] = $this->createEvent(Member::LEVEL_MEMBER, true);
		$eventService = $this->createMock(EventService::class);
		$eventService->expects($this->once())->method('memberPreparing')->with($event);
		$handler = $this->singleHandler($target, $eventService);

		$handler->verify($event);

		$this->assertSame($target, $event->getMember());
	}

	/**
	 * @dataProvider friendModes
	 */
	public function testBulkAddRejectsvisitorInBothFriendModes(bool $friend): void {
		[$event, $target] = $this->createEvent(Member::LEVEL_VISITOR, $friend);
		$event->setMembers([$target]);
		$eventService = $this->createMock(EventService::class);
		$eventService->expects($this->never())->method('memberPreparing');
		$handler = $this->massiveHandler($target, $eventService);

		$this->expectException(MemberLevelException::class);
		$handler->verify($event);
	}

	public function testBulkAddStillAllowsMemberInFriendCircle(): void {
		[$event, $target] = $this->createEvent(Member::LEVEL_MEMBER, true);
		$event->setMembers([$target]);
		$eventService = $this->createMock(EventService::class);
		$eventService->expects($this->once())->method('memberPreparing')->with($event);
		$handler = $this->massiveHandler($target, $eventService);

		$handler->verify($event);

		$this->assertSame([$target], $event->getMembers());
	}

	public static function friendModes(): array {
		return [
			'friend enabled' => [true],
			'friend disabled' => [false],
		];
	}

	private function singleHandler(Member $generated, EventService $eventService): SingleMemberAdd {
		return new class($generated, $eventService) extends SingleMemberAdd {
			public function __construct(
				private Member $generated,
				EventService $eventService,
			) {
				$this->eventService = $eventService;
			}

			protected function generateMember(FederatedEvent $event, Circle $circle, Member $member): Member {
				return $this->generated;
			}

			public function serialize(JsonSerializable $model): array {
				return [];
			}
		};
	}

	private function massiveHandler(Member $generated, EventService $eventService): MassiveMemberAdd {
		return new class($generated, $eventService) extends MassiveMemberAdd {
			public function __construct(
				private Member $generated,
				EventService $eventService,
			) {
				$this->eventService = $eventService;
			}

			protected function generateMember(FederatedEvent $event, Circle $circle, Member $member): Member {
				return $this->generated;
			}

			public function serialize(JsonSerializable $model): array {
				return [];
			}

			public function serializeArray(array $data): array {
				return [];
			}
		};
	}

	private function createEvent(int $initiatorLevel, bool $friend): array {
		$initiator = (new Member())
			->setId('initiator')
			->setSingleId('initiator-single')
			->setLevel($initiatorLevel);
		$target = (new Member())
			->setId('target')
			->setSingleId('target-single')
			->setUserId('target-user')
			->setLevel(Member::LEVEL_MEMBER)
			->setStatus(Member::STATUS_MEMBER);
		$circle = (new Circle())
			->setSingleId('circle-single')
			->setConfig($friend ? Circle::CFG_FRIEND : Circle::CFG_CIRCLE)
			->setInitiator($initiator);
		$event = (new FederatedEvent())
			->setCircle($circle)
			->setMember($target);

		return [$event, $target];
	}
}
