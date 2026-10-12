<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\MembershipService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MembershipServiceTest extends TestCase {
	public function testVisitorOnlyMembershipIsRetained(): void {
		$visitor = $this->createMembership(Member::LEVEL_VISITOR, 0, 'visitor-path');

		$memberships = $this->fill([$visitor]);

		$this->assertSame($visitor, $memberships['destination']);
	}

	public function testMemberWinsOverVisitorRegardlessOfTraversalOrder(): void {
		$visitor = $this->createMembership(Member::LEVEL_VISITOR, 0, 'direct-visitor');
		$member = $this->createMembership(Member::LEVEL_MEMBER, 2, 'inherited-member');

		foreach ([[$visitor, $member], [$member, $visitor]] as $traversal) {
			$memberships = $this->fill($traversal);

			$this->assertSame(Member::LEVEL_MEMBER, $memberships['destination']->getLevel());
		}
	}

	public function testEqualRolePrefersShorterInheritancePath(): void {
		$longPath = $this->createMembership(Member::LEVEL_MEMBER, 3, 'long-path');
		$shortPath = $this->createMembership(Member::LEVEL_MEMBER, 1, 'short-path');

		$memberships = $this->fill([$longPath, $shortPath]);

		$this->assertSame($shortPath->getInheritanceFirst(), $memberships['destination']->getInheritanceFirst());
		$this->assertSame(1, $memberships['destination']->getInheritanceDepth());
	}

	public function testHigherRolesRemainPreferred(): void {
		foreach ([Member::LEVEL_MODERATOR, Member::LEVEL_ADMIN, Member::LEVEL_OWNER] as $level) {
			$higher = $this->createMembership($level, 4, 'higher-role');
			$visitor = $this->createMembership(Member::LEVEL_VISITOR, 0, 'visitor');

			$memberships = $this->fill([$higher, $visitor]);

			$this->assertSame($level, $memberships['destination']->getLevel());
		}
	}

	public function testUnknownMembershipRanksBelowVisitorAndMember(): void {
		foreach ([Member::LEVEL_VISITOR, Member::LEVEL_MEMBER] as $knownLevel) {
			$unknown = $this->createMembership(73, 0, 'unknown');
			$known = $this->createMembership($knownLevel, 4, 'known');

			$memberships = $this->fill([$unknown, $known]);

			$this->assertSame($knownLevel, $memberships['destination']->getLevel());
		}
	}

	public function testUnknownMembershipDoesNotReplaceVisitorOrMember(): void {
		foreach ([Member::LEVEL_VISITOR, Member::LEVEL_MEMBER] as $knownLevel) {
			$known = $this->createMembership($knownLevel, 0, 'known');
			$unknown = $this->createMembership(73, 4, 'unknown');

			$memberships = $this->fill([$known, $unknown]);

			$this->assertSame($knownLevel, $memberships['destination']->getLevel());
		}
	}

	private function fill(array $incoming): array {
		$service = (new ReflectionClass(MembershipService::class))->newInstanceWithoutConstructor();
		$method = (new ReflectionClass(MembershipService::class))->getMethod('fillMemberships');
		$memberships = [];

		foreach ($incoming as $membership) {
			$arguments = [$membership];
			$arguments[] = &$memberships;
			$method->invokeArgs($service, $arguments);
		}

		return $memberships;
	}

	private function createMembership(int $level, int $depth, string $path): Membership {
		return (new Membership())
			->setSingleId('member-single')
			->setCircleId('destination')
			->setLevel($level)
			->setInheritanceDepth($depth)
			->setInheritanceFirst($path)
			->setInheritanceLast($path)
			->setInheritancePath([$path]);
	}
}
