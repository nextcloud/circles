<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Service;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ActivityService;
use OCA\Circles\Service\ConfigService;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ActivityServiceTest extends TestCase {
	private IActivityManager&MockObject $activityManager;
	private MemberRequest&MockObject $memberRequest;
	private CircleRequest&MockObject $circleRequest;
	private ActivityService $activityService;

	protected function setUp(): void {
		parent::setUp();

		$this->activityManager = $this->createMock(IActivityManager::class);
		$this->memberRequest = $this->createMock(MemberRequest::class);
		$this->circleRequest = $this->createMock(CircleRequest::class);
		$this->activityService = new ActivityService(
			$this->activityManager,
			$this->createMock(IUserManager::class),
			$this->memberRequest,
			$this->circleRequest,
			$this->createMock(ConfigService::class),
		);
	}

	private function createCircle(): Circle&MockObject {
		$circle = $this->createMock(Circle::class);
		$circle->method('getSingleId')->willReturn('team-single-id');
		$circle->method('getName')->willReturn('Design');
		$circle->method('getConfig')->willReturn(0);
		$circle->method('getUrl')->willReturn('');

		return $circle;
	}

	public function testTeamActivityUsesTheResolvedActivityObjectId(): void {
		$circle = $this->createCircle();
		$circle->method('isConfig')->willReturn(false);
		$circle->method('hasInitiator')->willReturn(false);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->memberRequest->expects($this->once())
			->method('getInheritedMembers')
			->with('team-single-id', false, 1)
			->willReturn([$user]);
		$this->circleRequest->expects($this->once())
			->method('getActivityObjectId')
			->with('team-single-id')
			->willReturn(42);

		$event = $this->createMock(IEvent::class);
		$event->method('setApp')->willReturnSelf();
		$event->method('setType')->willReturnSelf();
		$event->expects($this->once())
			->method('setObject')
			->with(Application::APP_ID, 42, 'Design')
			->willReturnSelf();
		$event->method('setSubject')->willReturnSelf();
		$event->method('setAffectedUser')->with('alice')->willReturnSelf();

		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($event);
		$this->activityManager->expects($this->once())
			->method('publish')
			->with($event);

		$this->activityService->onCircleDestruction($circle);
	}

	public function testTeamActivityUsesTheInitiatorAsAuthor(): void {
		$circle = $this->createCircle();
		$circle->method('isConfig')->willReturn(false);
		$circle->method('hasInitiator')->willReturn(true);

		$initiator = $this->createMock(Member::class);
		$initiator->method('getUserType')->willReturn(Member::TYPE_USER);
		$initiator->method('getUserId')->willReturn('admin');
		$circle->method('getInitiator')->willReturn($initiator);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->memberRequest->method('getInheritedMembers')->willReturn([$user]);

		$event = $this->createMock(IEvent::class);
		$event->method('setApp')->willReturnSelf();
		$event->method('setType')->willReturnSelf();
		$event->method('setObject')->willReturnSelf();
		$event->method('setSubject')->willReturnSelf();
		$event->method('setAffectedUser')->willReturnSelf();
		$event->expects($this->once())->method('setAuthor')->with('admin');

		$this->activityManager->method('generateEvent')->willReturn($event);
		$this->activityManager->expects($this->once())->method('publish')->with($event);

		$this->activityService->onCircleDestruction($circle);
	}

	public function testTeamActivityPublishesMemberRoleChangesOnTheCircleObject(): void {
		$circle = $this->createCircle();
		$circle->method('hasInitiator')->willReturn(false);
		$member = $this->createMock(Member::class);
		$member->method('getUserId')->willReturn('bob');
		$member->method('getDisplayName')->willReturn('Bob');
		$member->method('getUserType')->willReturn(Member::TYPE_USER);
		$member->method('getLevel')->willReturn(Member::LEVEL_MEMBER);
		$member->method('getStatus')->willReturn(Member::STATUS_MEMBER);
		$member->method('getSingleId')->willReturn('bob-single-id');
		$moderator = $this->createMock(IUser::class);
		$moderator->method('getUID')->willReturn('alice');
		$this->memberRequest->expects($this->once())
			->method('getInheritedMembers')
			->with('team-single-id', false, Member::LEVEL_MODERATOR)
			->willReturn([$moderator]);
		$this->circleRequest->expects($this->once())
			->method('getActivityObjectId')
			->with('team-single-id')
			->willReturn(42);
		$event = $this->createMock(IEvent::class);
		$event->method('setApp')->with(Application::APP_ID)->willReturnSelf();
		$event->method('setType')->with('circles_as_moderator')->willReturnSelf();
		$event->expects($this->once())->method('setObject')->with(Application::APP_ID, 42, 'Design')->willReturnSelf();
		$event->expects($this->once())->method('setSubject')->with('member_level', [
			'ver' => 2,
			'circle' => ['singleId' => 'team-single-id', 'name' => 'Design', 'config' => 0, 'url' => ''],
			'initiator' => null,
			'member' => ['userId' => 'bob', 'displayName' => 'Bob', 'type' => Member::TYPE_USER, 'level' => Member::LEVEL_MEMBER, 'status' => Member::STATUS_MEMBER],
			'level' => Member::LEVEL_MODERATOR,
		])->willReturnSelf();
		$event->expects($this->exactly(2))->method('setAffectedUser')->with($this->callback(static fn (string $userId): bool => in_array($userId, ['alice', 'bob'], true)))->willReturnSelf();
		$this->activityManager->expects($this->once())->method('generateEvent')->willReturn($event);
		$this->activityManager->expects($this->exactly(2))->method('publish')->with($event);

		$this->activityService->onMemberLevel($circle, $member, Member::LEVEL_MODERATOR);
	}

	public function testTeamActivityPublishesOwnerChangesToAllMembers(): void {
		$circle = $this->createCircle();
		$circle->method('hasInitiator')->willReturn(false);
		$member = $this->createMock(Member::class);
		$member->method('getUserId')->willReturn('bob');
		$member->method('getDisplayName')->willReturn('Bob');
		$member->method('getUserType')->willReturn(Member::TYPE_USER);
		$member->method('getLevel')->willReturn(Member::LEVEL_MEMBER);
		$member->method('getStatus')->willReturn(Member::STATUS_MEMBER);
		$member->method('getSingleId')->willReturn('bob-single-id');
		$this->memberRequest->expects($this->once())->method('getInheritedMembers')->with('team-single-id', false, Member::LEVEL_MEMBER)->willReturn([$member]);
		$this->circleRequest->expects($this->once())
			->method('getActivityObjectId')
			->with('team-single-id')
			->willReturn(42);
		$event = $this->createMock(IEvent::class);
		$event->method('setApp')->willReturnSelf();
		$event->method('setType')->with('circles_as_moderator')->willReturnSelf();
		$event->expects($this->once())->method('setObject')->with(Application::APP_ID, 42, 'Design')->willReturnSelf();
		$event->expects($this->once())->method('setSubject')->with('member_owner', $this->isType('array'))->willReturnSelf();
		$event->expects($this->once())->method('setAffectedUser')->with('bob')->willReturnSelf();
		$this->activityManager->expects($this->once())->method('generateEvent')->willReturn($event);
		$this->activityManager->expects($this->once())->method('publish')->with($event);

		$this->activityService->onMemberLevel($circle, $member, Member::LEVEL_OWNER);
	}

	public function testTeamActivityPublishesVisibleSettingChanges(): void {
		$circle = $this->createCircle();
		$circle->method('isConfig')->willReturn(false);
		$circle->method('hasInitiator')->willReturn(false);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->memberRequest->expects($this->once())->method('getInheritedMembers')->with('team-single-id', false, Member::LEVEL_MEMBER)->willReturn([$user]);
		$this->circleRequest->expects($this->once())
			->method('getActivityObjectId')
			->with('team-single-id')
			->willReturn(42);
		$event = $this->createMock(IEvent::class);
		$event->method('setApp')->willReturnSelf();
		$event->method('setType')->with('circles_as_member')->willReturnSelf();
		$event->method('setObject')->with(Application::APP_ID, 42, 'Design')->willReturnSelf();
		$event->expects($this->once())->method('setSubject')->with('circle_setting_changed', [
			'ver' => 2,
			'circle' => ['singleId' => 'team-single-id', 'name' => 'Design', 'config' => 0, 'url' => ''],
			'initiator' => null,
			'setting' => Circle::SETTING_TEAM_FOLDER_QUOTA,
		])->willReturnSelf();
		$event->method('setAffectedUser')->with('alice')->willReturnSelf();
		$this->activityManager->expects($this->once())->method('generateEvent')->willReturn($event);
		$this->activityManager->expects($this->once())->method('publish')->with($event);

		$this->activityService->onCircleSetting($circle, Circle::SETTING_TEAM_FOLDER_QUOTA);
	}

	public function testTeamActivityPublishesUnlistedSettingChangesWithoutTheSettingName(): void {
		$circle = $this->createCircle();
		$circle->method('isConfig')->willReturn(false);
		$circle->method('hasInitiator')->willReturn(false);
		$this->memberRequest->method('getInheritedMembers')->willReturn([]);
		$event = $this->createMock(IEvent::class);
		$event->method('setApp')->willReturnSelf();
		$event->method('setType')->willReturnSelf();
		$event->method('setObject')->willReturnSelf();
		$event->expects($this->once())->method('setSubject')->with('circle_setting_changed', [
			'ver' => 2,
			'circle' => ['singleId' => 'team-single-id', 'name' => 'Design', 'config' => 0, 'url' => ''],
			'initiator' => null,
			'setting' => 'generic',
		])->willReturnSelf();
		$this->activityManager->expects($this->once())->method('generateEvent')->willReturn($event);

		$this->activityService->onCircleSetting($circle, Circle::SETTING_EXTERNAL_ID);
	}
}
