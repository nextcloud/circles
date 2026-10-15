<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Db;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\CoreQueryBuilder;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Repository\TeamFavoriteRepository;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\TimezoneService;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\Group;
use Psr\Container\ContainerInterface;
use Test\TestCase;

#[Group('DB')]
class MemberLevelOrderingTest extends TestCase {
	private const CASE_BODY = 'WHEN 9 THEN 5 WHEN 8 THEN 4 WHEN 4 THEN 3 WHEN 1 THEN 2 WHEN 2 THEN 1 WHEN 0 THEN 0 ELSE 0 END';

	private ContainerInterface $container;
	private IUserManager $userManager;
	private IUserSession $userSession;
	private CircleService $circleService;
	private MemberService $memberService;
	private MemberRequest $memberRequest;
	private MembershipService $membershipService;
	private string $token;
	private string $creatorId;
	private string $actorId;
	private array $createdUsers = [];
	private array $circlesToCleanup = [];

	protected function setUp(): void {
		parent::setUp();

		$this->container = (new Application())->getContainer();
		$this->userManager = $this->container->get(IUserManager::class);
		$this->userSession = $this->container->get(IUserSession::class);
		$this->circleService = $this->container->get(CircleService::class);
		$this->memberService = $this->container->get(MemberService::class);
		$this->memberRequest = $this->container->get(MemberRequest::class);
		$this->membershipService = $this->container->get(MembershipService::class);
		$this->token = bin2hex(random_bytes(6));
		$this->creatorId = $this->createUser('creator');
		$this->actorId = $this->createUser('actor');
		$this->setCurrentUser($this->creatorId);
	}

	protected function tearDown(): void {
		foreach ($this->circlesToCleanup as $circleId => $ownerId) {
			try {
				$this->setCurrentUser($ownerId);
				$this->circleService->destroy($circleId);
			} catch (\Throwable) {
			}
		}

		foreach ($this->createdUsers as $userId) {
			$this->userManager->get($userId)?->delete();
		}

		parent::tearDown();
	}

	public function testRequestSqlUsesRankCaseAndExpectedAliases(): void {
		$memberRequest = new class($this->container->get(IDBConnection::class), $this->container->get(TimezoneService::class), $this->container->get(ConfigService::class), ) extends MemberRequest {
			public string $capturedSql = '';

			public function getItemsFromRequest(CoreQueryBuilder $qb, bool $asFederatedUser = false): array {
				$this->capturedSql = $qb->getSQL();

				return [];
			}
		};
		$memberRequest->getMembers('ordering-test-circle', limit: 1);
		$normalizedMemberSql = strtolower(str_replace(['`', '"'], '', $memberRequest->capturedSql));

		$this->assertStringContainsString(
			'order by case c.level ' . strtolower(self::CASE_BODY) . ' desc, c.cached_name asc',
			$normalizedMemberSql
		);

		$circleRequest = new class($this->container->get(TeamFavoriteRepository::class), $this->container->get(TimezoneService::class), $this->container->get(ConfigService::class), ) extends CircleRequest {
			public string $capturedSql = '';

			public function getItemsFromRequest(CoreQueryBuilder $qb): array {
				$this->capturedSql = $qb->getSQL();

				return [];
			}
		};
		$initiator = (new FederatedUser())->set(
			$this->actorId,
			'',
			Member::TYPE_USER,
		);
		$circleRequest->getCircles($initiator, new CircleProbe());

		$normalizedCircleSql = strtolower(str_replace(['`', '"'], '', $circleRequest->capturedSql));
		$this->assertSame(1, preg_match('/order by case ([a-z0-9_]+)\.level /', $normalizedCircleSql, $matches));
		$this->assertNotSame(CoreQueryBuilder::CIRCLE, $matches[1]);
		$this->assertStringContainsString(
			'order by case ' . $matches[1] . '.level ' . strtolower(self::CASE_BODY) . ' desc, b.display_name asc',
			$normalizedCircleSql
		);
	}

	public function testMemberOrderingAndLimitPlaceVisitorBelowMember(): void {
		$circle = $this->createCircle('member-order');
		$roles = [
			'none' => Member::LEVEL_NONE,
			'member' => Member::LEVEL_MEMBER,
			'visitor' => Member::LEVEL_VISITOR,
			'moderator' => Member::LEVEL_MODERATOR,
			'admin' => Member::LEVEL_ADMIN,
			'unknown' => 73,
		];

		foreach ($roles as $name => $level) {
			$this->addMemberAtLevel($circle['id'], $this->createUser('member-' . $name), $level);
		}

		$members = $this->memberRequest->getMembers($circle['id']);
		$this->assertSame([
			Member::LEVEL_OWNER,
			Member::LEVEL_ADMIN,
			Member::LEVEL_MODERATOR,
			Member::LEVEL_MEMBER,
			Member::LEVEL_VISITOR,
		], array_map(static fn (Member $member): int => $member->getLevel(), array_slice($members, 0, 5)));

		$rankZero = array_slice($members, 5);
		$this->assertEqualsCanonicalizing(
			[Member::LEVEL_NONE, 73],
			array_map(static fn (Member $member): int => $member->getLevel(), $rankZero)
		);
		$names = array_map(static fn (Member $member): string => $member->getDisplayName(), $rankZero);
		$sortedNames = $names;
		sort($sortedNames, SORT_STRING);
		$this->assertSame($sortedNames, $names);

		$limited = $this->memberRequest->getMembers($circle['id'], limit: 5);
		$this->assertSame([
			Member::LEVEL_OWNER,
			Member::LEVEL_ADMIN,
			Member::LEVEL_MODERATOR,
			Member::LEVEL_MEMBER,
			Member::LEVEL_VISITOR,
		], array_map(static fn (Member $member): int => $member->getLevel(), $limited));
	}

	public function testCircleOrderingAndOffsetUseSemanticInitiatorRank(): void {
		$roleCases = [
			'owner' => Member::LEVEL_OWNER,
			'admin' => Member::LEVEL_ADMIN,
			'moderator' => Member::LEVEL_MODERATOR,
			'member' => Member::LEVEL_MEMBER,
			'visitor' => Member::LEVEL_VISITOR,
			'none' => Member::LEVEL_NONE,
			'unknown' => 73,
		];
		$createdCircleIds = [];

		foreach ($roleCases as $name => $level) {
			$circle = $this->createCircle('circle-order-' . $name);
			$createdCircleIds[$circle['id']] = true;
			$member = $this->addMemberAtLevel($circle['id'], $this->actorId, $level);
			if ($level === Member::LEVEL_OWNER) {
				$this->circlesToCleanup[$circle['id']] = $this->actorId;
			}
		}

		$this->setCurrentUser($this->actorId);
		$initiator = $this->container->get(FederatedUserService::class)->getLocalFederatedUser($this->actorId);
		$circles = array_values(array_filter(
			$this->container->get(CircleRequest::class)->getCircles($initiator, new CircleProbe()),
			static fn (Circle $circle): bool => isset($createdCircleIds[$circle->getSingleId()])
		));
		$this->assertCount(count($roleCases), $circles);

		$initiatorLevels = array_map(static fn (Circle $circle): int => $circle->getInitiator()->getLevel(), $circles);
		$this->assertSame([
			Member::LEVEL_OWNER,
			Member::LEVEL_ADMIN,
			Member::LEVEL_MODERATOR,
			Member::LEVEL_MEMBER,
			Member::LEVEL_VISITOR,
		], array_slice($initiatorLevels, 0, 5));
		$this->assertEqualsCanonicalizing([Member::LEVEL_NONE, 73], array_slice($initiatorLevels, 5));

		$rankZeroNames = array_map(static fn (Circle $circle): string => $circle->getDisplayName(), array_slice($circles, 5));
		$sortedRankZeroNames = $rankZeroNames;
		sort($sortedRankZeroNames, SORT_STRING);
		$this->assertSame($sortedRankZeroNames, $rankZeroNames);

		$offsetCircles = $this->container->get(CircleRequest::class)->getCircles(
			$initiator,
			(new CircleProbe())->setItemsOffset(1)->setItemsLimit(2),
		);
		$this->assertSame([
			Member::LEVEL_ADMIN,
			Member::LEVEL_MODERATOR,
		], array_map(static fn (Circle $circle): int => $circle->getInitiator()->getLevel(), $offsetCircles));
	}

	private function createUser(string $name): string {
		$userId = 'circles-order-' . $this->token . '-' . $name;
		$this->userManager->createUser($userId, 'test-pwd');
		$this->createdUsers[] = $userId;

		return $userId;
	}

	private function setCurrentUser(string $userId): void {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new \RuntimeException('Test user not found');
		}

		$this->userSession->setUser($user);
		$this->container->get(FederatedUserService::class)->setLocalCurrentUser($user);
	}

	private function createCircle(string $name): array {
		$this->setCurrentUser($this->creatorId);
		$circle = $this->circleService->create($name . '-' . $this->token, createTeamFolder: false);
		$this->circlesToCleanup[$circle['id']] = $this->creatorId;

		return $circle;
	}

	private function addMemberAtLevel(string $circleId, string $userId, int $level): array {
		$this->setCurrentUser($this->creatorId);
		$federatedUserService = $this->container->get(FederatedUserService::class);
		$memberData = $this->memberService->addMember(
			$circleId,
			$federatedUserService->generateFederatedUser($userId, Member::TYPE_USER),
		);

		if ($level === Member::LEVEL_NONE || $level === 73) {
			$member = $this->memberService->getMemberById($memberData['id'], $circleId);
			$member->setLevel($level);
			$this->memberRequest->updateLevel($member);
			$this->membershipService->onUpdate($member->getSingleId());
		} elseif ($level !== Member::LEVEL_MEMBER) {
			$this->memberService->memberLevel($memberData['id'], $level);
		}

		return $memberData;
	}
}
