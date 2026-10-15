<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\CirclesQueryHelper;
use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\Group;
use Psr\Container\ContainerInterface;
use Test\TestCase;

#[Group('DB')]
class CirclesQueryHelperTest extends TestCase {
	private ContainerInterface $container;
	private IUserManager $userManager;
	private IUserSession $userSession;
	private CircleService $circleService;
	private MemberService $memberService;
	private FederatedUserService $federatedUserService;
	private CirclesQueryHelper $circlesQueryHelper;
	private string $token;
	private string $creatorId;
	private string $targetId;
	private array $createdUsers = [];
	private array $circlesToCleanup = [];

	protected function setUp(): void {
		parent::setUp();

		$this->container = (new Application())->getContainer();
		$this->userManager = $this->container->get(IUserManager::class);
		$this->userSession = $this->container->get(IUserSession::class);
		$this->circleService = $this->container->get(CircleService::class);
		$this->memberService = $this->container->get(MemberService::class);
		$this->federatedUserService = $this->container->get(FederatedUserService::class);
		$this->circlesQueryHelper = $this->container->get(CirclesQueryHelper::class);
		$this->token = bin2hex(random_bytes(6));
		$this->creatorId = $this->createUser('creator');
		$this->targetId = $this->createUser('target');
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

	public function testLimitToMembershipsSelectsStoredVisitorAndMemberLevels(): void {
		$visitorCircleId = $this->createCircle('visitor');
		$memberCircleId = $this->createCircle('member');
		$this->addMemberAtLevel($visitorCircleId, Member::LEVEL_VISITOR);
		$this->addMemberAtLevel($memberCircleId, Member::LEVEL_MEMBER);
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($this->targetId);

		$this->assertSame(Member::LEVEL_VISITOR, $this->getSelectedLevel($visitorCircleId, $federatedUser));
		$this->assertSame(Member::LEVEL_MEMBER, $this->getSelectedLevel($memberCircleId, $federatedUser));
	}

	private function getSelectedLevel(string $circleId, FederatedUser $federatedUser): int {
		$queryBuilder = $this->circlesQueryHelper->getQueryBuilder();
		$queryBuilder->select('a.unique_id')->from(CoreRequestBuilder::TABLE_CIRCLE, 'a');
		$this->circlesQueryHelper->limitToMemberships('a', 'unique_id', $federatedUser, 'circle_level');
		$queryBuilder->andWhere(
			$queryBuilder->expr()->eq('a.unique_id', $queryBuilder->createNamedParameter($circleId))
		);

		$row = $queryBuilder->executeQuery()->fetchAssociative();
		$this->assertIsArray($row);

		return (int)$row['circle_level'];
	}

	private function createUser(string $name): string {
		$userId = 'circles-query-helper-' . $this->token . '-' . $name;
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
		$this->federatedUserService->setLocalCurrentUser($user);
	}

	private function createCircle(string $name): string {
		$this->setCurrentUser($this->creatorId);
		$circle = $this->circleService->create($name . '-' . $this->token, createTeamFolder: false);
		$this->circlesToCleanup[$circle['id']] = $this->creatorId;

		return $circle['id'];
	}

	private function addMemberAtLevel(string $circleId, int $level): void {
		$this->setCurrentUser($this->creatorId);
		$memberData = $this->memberService->addMember(
			$circleId,
			$this->federatedUserService->generateFederatedUser($this->targetId, Member::TYPE_USER),
		);
		if ($level !== Member::LEVEL_MEMBER) {
			$this->memberService->memberLevel($memberData['id'], $level);
		}
	}
}
