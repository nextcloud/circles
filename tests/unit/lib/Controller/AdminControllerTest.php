<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Controller\AdminController;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\SearchService;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\Group;
use Psr\Container\ContainerInterface;
use Test\TestCase;

#[Group('DB')]
class AdminControllerTest extends TestCase {
	// suffix user ids with hash to avoid collision with existing users
	private const TEST_USER_1 = 'circles-test-user-98cb5bac';
	private const TEST_USER_2 = 'circles-test-user-bc412b0c';

	private ContainerInterface $container;
	private Application $app;
	private AdminController $adminController;
	private array $circlesToCleanup = [];
	private static array $usersToCleanup = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$app = new Application();
		$c = $app->getContainer();

		foreach ([self::TEST_USER_1, self::TEST_USER_2] as $userId) {
			$user = $c->get(IUserManager::class)->get($userId);
			if ($user === null) {
				$c->get(IUserManager::class)->createUser($userId, 'test-pwd');
				self::$usersToCleanup[] = $userId;
			}
		}
	}

	public function setUp(): void {
		parent::setUp();

		$this->app = new Application();
		$this->container = $this->app->getContainer();

		$c = $this->container;

		$user1 = $c->get(IUserManager::class)->get(self::TEST_USER_1);
		$userSession = $c->get(IUserSession::class);
		$userSession->setUser($user1);
		$federatedUserService = $c->get(FederatedUserService::class);
		$federatedUserService->setLocalCurrentUser($user1);

		$this->adminController = new AdminController(
			Application::APP_ID,
			$c->get(IRequest::class),
			$userSession,
			$federatedUserService,
			$c->get(CircleService::class),
			$c->get(MemberService::class),
			$c->get(MembershipService::class),
			$c->get(SearchService::class),
			$c->get(ConfigService::class),
		);
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		$app = new Application();
		$c = $app->getContainer();

		foreach (self::$usersToCleanup as $userId) {
			$c->get(IUserManager::class)->get($userId)?->delete();
		}
	}

	protected function tearDown(): void {
		parent::tearDown();

		$circleService = $this->container->get(CircleService::class);
		foreach ($this->circlesToCleanup as $circleId) {
			try {
				$circleService->destroy($circleId);
			} catch (\Throwable) {
				// continue cleanup
			}
		}
	}

	public function testCreate(): void {
		$result = $this->adminController->create(self::TEST_USER_1, 'test-circle')->getData();
		$this->circlesToCleanup[] = $result['id'];

		$this->assertSame($result['name'], 'test-circle');
		$this->assertSame($result['population'], 0);
		$this->assertSame($result['config'], 0);
		$this->assertSame($result['initiator']['userId'], self::TEST_USER_1);
		$this->assertSame($result['initiator']['level'], Member::LEVEL_OWNER);
		$this->assertSame($result['owner']['userId'], self::TEST_USER_1);
	}

	public function testDestroy(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');

		$this->adminController->destroy(self::TEST_USER_1, $circleData['id']);

		$this->expectException(CircleNotFoundException::class);

		$c->get(CircleService::class)->getCircle($circleData['id']);
	}

	public function testMemberAdd(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$result = $this->adminController->memberAdd(self::TEST_USER_1, $circleData['id'], self::TEST_USER_2, Member::TYPE_USER)->getData();

		$this->assertSame($result['circleId'], $circleData['id']);
		$this->assertSame($result['userId'], self::TEST_USER_2);
		$this->assertSame($result['level'], Member::LEVEL_MEMBER);
	}

	public function testMemberLevel(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$user2 = $c->get(FederatedUserService::class)->generateFederatedUser(self::TEST_USER_2, Member::TYPE_USER);
		$memberData = $c->get(MemberService::class)->addMember($circleData['id'], $user2);

		$result = $this->adminController->memberLevel(self::TEST_USER_1, $circleData['id'], $memberData['id'], Member::LEVEL_MODERATOR)->getData();

		$this->assertSame($result['circleId'], $circleData['id']);
		$this->assertSame($result['userId'], self::TEST_USER_2);
		$this->assertSame($result['level'], Member::LEVEL_MODERATOR);
	}

	public function testCircles(): void {
		$c = $this->container;

		/**
		 * count before, as circles not created by this test may be returned
		 * by the controller (e.g. "visible to everyone" circles)
		 */
		$countBefore = count($this->adminController->circles(self::TEST_USER_1)->getData());

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$circleData2 = $c->get(CircleService::class)->create('test-circle-2');
		$circleData3 = $c->get(CircleService::class)->create('test-circle-3');
		$this->circlesToCleanup[] = $circleData['id'];
		$this->circlesToCleanup[] = $circleData2['id'];
		$this->circlesToCleanup[] = $circleData3['id'];

		$circleDataMap = [
			$circleData['id'] => $circleData,
			$circleData2['id'] => $circleData2,
			$circleData3['id'] => $circleData3,
		];

		$result = $this->adminController->circles(self::TEST_USER_1)->getData();

		$this->assertCount($countBefore + 3, $result);

		foreach ($result as $circle) {
			// skip circles that were not created by this test
			if (!isset($circleDataMap[$circle['id']])) {
				continue;
			}
			$this->assertSame($circle['id'], $circleDataMap[$circle['id']]['id']);
			$this->assertSame($circle['name'], $circleDataMap[$circle['id']]['name']);
			$this->assertSame($circle['config'], $circleDataMap[$circle['id']]['config']);
			$this->assertSame($circle['initiator']['userId'], $circleDataMap[$circle['id']]['initiator']['userId']);
			$this->assertSame($circle['initiator']['level'], $circleDataMap[$circle['id']]['initiator']['level']);
			$this->assertSame($circle['owner']['userId'], $circleDataMap[$circle['id']]['owner']['userId']);
		}
	}

	public function testCircleDetails(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$result = $this->adminController->circleDetails(self::TEST_USER_1, $circleData['id'])->getData();

		$this->assertSame($result['id'], $circleData['id']);
		$this->assertSame($result['name'], $circleData['name']);
		$this->assertSame($result['config'], $circleData['config']);
		$this->assertSame($result['initiator']['userId'], $circleData['initiator']['userId']);
		$this->assertSame($result['initiator']['level'], $circleData['initiator']['level']);
		$this->assertSame($result['owner']['userId'], $circleData['owner']['userId']);
	}

	public function testCircleJoin(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		// circle is visible to everyone and anyone can join
		$circleConfig = Circle::CFG_VISIBLE + Circle::CFG_OPEN;
		$c->get(CircleService::class)->updateConfig($circleData['id'], $circleConfig);

		$result = $this->adminController->circleJoin(self::TEST_USER_2, $circleData['id'])->getData();

		/** @var Member $member */
		$member = $c->get(MemberService::class)->getMemberById($result['id'], $result['circleId']);

		$this->assertSame($result['id'], $member->getId());
		$this->assertSame($result['circleId'], $circleData['id']);
		$this->assertSame($result['userId'], self::TEST_USER_2);
		$this->assertSame($result['userType'], Member::TYPE_USER);
		$this->assertSame($result['level'], Member::LEVEL_MEMBER);
	}

	public function testCircleLeave(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		// circle is visible to everyone and anyone can join
		$circleConfig = Circle::CFG_VISIBLE + Circle::CFG_OPEN;
		$c->get(CircleService::class)->updateConfig($circleData['id'], $circleConfig);

		$federatedUserService = $c->get(FederatedUserService::class);
		$user2 = $c->get(IUserManager::class)->get(self::TEST_USER_2);

		$federatedUserService->setLocalCurrentUser($user2);
		$memberData = $c->get(CircleService::class)->circleJoin($circleData['id']);

		$this->adminController->circleLeave(self::TEST_USER_2, $circleData['id'])->getData();

		$this->expectException(MemberNotFoundException::class);

		$c->get(MemberService::class)->getMemberById($memberData['id'], $circleData['id']);
	}

	public function testMemberConfirm(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		// circle is visible to everyone but requires approval to join
		$circleConfig = Circle::CFG_VISIBLE + Circle::CFG_OPEN + Circle::CFG_REQUEST;
		$c->get(CircleService::class)->updateConfig($circleData['id'], $circleConfig);

		$federatedUserService = $c->get(FederatedUserService::class);
		$user1 = $c->get(IUserManager::class)->get(self::TEST_USER_1);
		$user2 = $c->get(IUserManager::class)->get(self::TEST_USER_2);

		$federatedUserService->setLocalCurrentUser($user2);
		$memberData = $c->get(CircleService::class)->circleJoin($circleData['id']);

		$federatedUserService->setLocalCurrentUser($user1);

		// as TEST_USER_1, allow TEST_USER_2 to be a member of the team
		$result = $this->adminController->memberConfirm(self::TEST_USER_1, $circleData['id'], $memberData['id'])->getData();

		$this->assertSame($result['id'], $memberData['id']);
		$this->assertSame($result['circleId'], $circleData['id']);
		$this->assertSame($result['userId'], self::TEST_USER_2);
		$this->assertSame($result['userType'], Member::TYPE_USER);
		$this->assertSame($result['level'], Member::LEVEL_MEMBER);
	}

	public function testMemberRemove(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$user2 = $c->get(FederatedUserService::class)->generateFederatedUser(self::TEST_USER_2, Member::TYPE_USER);
		$memberData = $c->get(MemberService::class)->addMember($circleData['id'], $user2);

		$this->adminController->memberRemove(self::TEST_USER_1, $circleData['id'], $memberData['id']);

		$this->expectException(MemberNotFoundException::class);

		$c->get(MemberService::class)->getMemberById($memberData['id'], $circleData['id']);
	}

	public function testMembers(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$user2 = $c->get(FederatedUserService::class)->generateFederatedUser(self::TEST_USER_2, Member::TYPE_USER);
		$c->get(MemberService::class)->addMember($circleData['id'], $user2);

		$result = $this->adminController->members(self::TEST_USER_1, $circleData['id'])->getData();

		$this->assertCount(2, $result);

		$userIds = array_column($result, 'userId');
		$this->assertContains(self::TEST_USER_1, $userIds);
		$this->assertContains(self::TEST_USER_2, $userIds);

		$circleIds = array_column($result, 'circleId');
		foreach ($circleIds as $circleId) {
			$this->assertSame($circleId, $circleData['id']);
		}
	}

	public function testEditName(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$result = $this->adminController->editName(self::TEST_USER_1, $circleData['id'], 'test-cricle-new-name')->getData();

		$this->assertSame($result['id'], $circleData['id']);
		$this->assertSame($result['name'], 'test-cricle-new-name');
	}

	public function testEditDescription(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$result = $this->adminController->editDescription(self::TEST_USER_1, $circleData['id'], 'test-cricle-new-description')->getData();

		$this->assertSame($result['id'], $circleData['id']);
		$this->assertSame($result['description'], 'test-cricle-new-description');
	}

	public function testEditSetting(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$result = $this->adminController->editSetting(self::TEST_USER_1, $circleData['id'], ConfigService::MEMBERS_LIMIT, '25')->getData();

		$this->assertSame($result['id'], $circleData['id']);
		$this->assertSame($result['settings'][ConfigService::MEMBERS_LIMIT], '25');
	}

	public function testEditConfig(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$result = $this->adminController->editConfig(self::TEST_USER_1, $circleData['id'], 128)->getData();

		$this->assertSame($result['id'], $circleData['id']);
		$this->assertSame($result['config'], 128);
	}

	public function testLink(): void {
		$c = $this->container;

		$circleData = $c->get(CircleService::class)->create('test-circle');
		$this->circlesToCleanup[] = $circleData['id'];

		$user2 = $c->get(FederatedUserService::class)->generateFederatedUser(self::TEST_USER_2, Member::TYPE_USER);
		$memberData = $c->get(MemberService::class)->addMember($circleData['id'], $user2);

		// as TEST_USER_1, get membership details of TEST_USER_2
		$result = $this->adminController->link(self::TEST_USER_1, $circleData['id'], $memberData['singleId'])->getData();

		$this->assertSame($result['circleId'], $circleData['id']);
		$this->assertSame($result['singleId'], $memberData['singleId']);
		$this->assertSame($result['level'], Member::LEVEL_MEMBER);
		$this->assertSame($result['inheritanceDepth'], 1);
	}
}
