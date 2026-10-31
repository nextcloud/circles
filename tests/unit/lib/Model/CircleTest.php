<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Model;

use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CircleTest extends TestCase {
	public static function visibilityLevels(): iterable {
		foreach ([Member::LEVEL_MEMBER, Member::LEVEL_MODERATOR, Member::LEVEL_ADMIN] as $threshold) {
			foreach ([Member::LEVEL_NONE, Member::LEVEL_MEMBER, Member::LEVEL_MODERATOR, Member::LEVEL_ADMIN, Member::LEVEL_OWNER] as $level) {
				yield "$threshold/$level" => [$threshold, $level, $level >= $threshold];
			}
		}
	}

	#[DataProvider('visibilityLevels')]
	public function testMembersVisibility(int $threshold, int $level, bool $expected): void {
		$circle = new Circle();
		$circle->setSettings([Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL => (string)$threshold]);
		$circle->setInitiator((new Member())->setId('initiator')->setLevel($level));

		$this->assertSame($threshold, $circle->getMembersViewPermissionLevel());
		$this->assertSame($expected, $circle->canViewMembers());
	}

	public function testDefaultVisibilityPreservesMemberAccess(): void {
		$circle = new Circle();
		$circle->setInitiator((new Member())->setLevel(Member::LEVEL_MEMBER));

		$this->assertSame(Member::LEVEL_MEMBER, $circle->getMembersViewPermissionLevel());
		$this->assertTrue($circle->canViewMembers());
	}

	public function testNoInitiatorCannotViewMembers(): void {
		$this->assertFalse((new Circle())->canViewMembers());
	}

	public function testInheritedInitiatorDeterminesVisibility(): void {
		$circle = new Circle();
		$circle->setSettings([Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL => (string)Member::LEVEL_MODERATOR]);
		$circle->setInitiator((new Member())->setId('group-member')->setUserType(Member::TYPE_GROUP)->setLevel(Member::LEVEL_MODERATOR));
		$circle->setDirectInitiator((new Member())->setId('direct-member')->setLevel(Member::LEVEL_MEMBER));

		$this->assertTrue($circle->canViewMembers());
	}

	public function testMalformedVisibilityDoesNotGrantMemberAccess(): void {
		$circle = new Circle();
		$circle->setSettings([Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL => 'invalid']);
		$circle->setInitiator((new Member())->setLevel(Member::LEVEL_MEMBER));

		$this->assertSame(Member::LEVEL_ADMIN, $circle->getMembersViewPermissionLevel());
		$this->assertFalse($circle->canViewMembers());
	}

	public function testSerializationOmitsRestrictedMemberListsButKeepsInternalMembers(): void {
		$circle = $this->createPartialMock(Circle::class, ['getUrl']);
		$circle->method('getUrl')->willReturn('/team');
		$member = (new Member())->setId('member')->setLevel(Member::LEVEL_MEMBER);
		$circle->setInitiator($member);
		$circle->setSettings([Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL => (string)Member::LEVEL_ADMIN]);
		$circle->setMembers([$member]);
		$circle->setInheritedMembers([$member], true);
		$circle->setMemberships([]);

		$data = $circle->jsonSerialize();

		$this->assertFalse($data['canViewMembers']);
		$this->assertSame(Member::LEVEL_ADMIN, $data['membersViewPermissionLevel']);
		$this->assertArrayNotHasKey('members', $data);
		$this->assertArrayNotHasKey('inheritedMembers', $data);
		$this->assertArrayNotHasKey('memberships', $data);
		$this->assertArrayNotHasKey('settings', $data);
		$this->assertSame([$member], $circle->getMembers());
		$this->assertSame([$member], $circle->getInheritedMembers());
	}

	public function testSerializationIncludesPermittedMemberLists(): void {
		$circle = $this->createPartialMock(Circle::class, ['getUrl']);
		$circle->method('getUrl')->willReturn('/team');
		$member = (new Member())->setId('member')->setLevel(Member::LEVEL_MODERATOR);
		$circle->setInitiator($member);
		$circle->setSettings([Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL => (string)Member::LEVEL_MODERATOR]);
		$circle->setMembers([$member]);
		$circle->setInheritedMembers([$member], true);
		$circle->setMemberships([]);

		$data = $circle->jsonSerialize();

		$this->assertTrue($data['canViewMembers']);
		$this->assertSame([$member], $data['members']);
		$this->assertSame([$member], $data['inheritedMembers']);
		$this->assertSame([], $data['memberships']);
	}
}
