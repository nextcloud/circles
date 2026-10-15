<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Model;

use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Exceptions\ParseMemberLevelException;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\Member;
use PHPUnit\Framework\TestCase;

class MemberTest extends TestCase {
	public function testVisitorAndExistingLevelsRoundTrip(): void {
		$this->assertSame(Member::LEVEL_VISITOR, Member::parseLevelInt(2));
		$this->assertSame(Member::LEVEL_VISITOR, Member::parseLevelString('Visitor'));
		$this->assertSame(Member::LEVEL_VISITOR, Member::parseLevelString('visitor'));

		foreach (Member::$DEF_LEVEL as $level => $label) {
			$this->assertSame($level, Member::parseLevelInt($level));
			$this->assertSame($level, Member::parseLevelString($label));
		}
	}

	public function testMemberSatisfiesVisitorMinimum(): void {
		$member = (new Member())->setLevel(Member::LEVEL_MEMBER);

		$this->assertNull((new MemberHelper($member))->mustBeVisitor());
	}

	public function testVisitorFailsMemberHelperMemberCheck(): void {
		$visitor = (new Member())->setLevel(Member::LEVEL_VISITOR);

		$this->expectException(MemberLevelException::class);
		(new MemberHelper($visitor))->mustBeMember();
	}

	public function testNoneAndUnknownLevelsHaveRankZero(): void {
		$this->assertSame(0, Member::getLevelRank(Member::LEVEL_NONE));
		foreach ([-1, 3, 5, 6, 7, 10, 99] as $level) {
			$this->assertSame(0, Member::getLevelRank($level));
			$this->assertSame(0, Member::compareLevels($level, Member::LEVEL_NONE));
		}
	}

	public function testCompareLevelsUsesSemanticRoleOrder(): void {
		$this->assertGreaterThan(0, Member::compareLevels(Member::LEVEL_MEMBER, Member::LEVEL_VISITOR));
		$this->assertLessThan(0, Member::compareLevels(Member::LEVEL_VISITOR, Member::LEVEL_MEMBER));
	}

	public function testRankMapKeysMatchAssignableLevelsAndNone(): void {
		$levelKeys = array_keys(Member::$DEF_LEVEL);
		$levelKeys[] = Member::LEVEL_NONE;
		$rankKeys = array_keys(Member::LEVEL_RANKS);
		sort($levelKeys);
		sort($rankKeys);

		$this->assertSame($levelKeys, $rankKeys);
	}

	public function testNoneIsNotAnAssignableLevel(): void {
		$this->expectException(ParseMemberLevelException::class);
		Member::parseLevelInt(Member::LEVEL_NONE);
	}
}
