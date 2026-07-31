<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Controller\LocalController;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\SearchService;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class LocalControllerTest extends TestCase {
	use TDeserialize;

	// suffix user ids with hash to avoid collision with existing users
	private const TEST_USER_1 = 'circles-test-user-98cb5bac';
	private const TEST_USER_2 = 'circles-test-user-bc412b0c';
	private const TEST_USER_3 = 'circles-test-user-sd5468sd';
	private const TEST_USER_4 = 'circles-test-user-32sds25s';

	private ContainerInterface $container;
	private Application $app;
	private LocalController $localController;
	private IUserManager $userManager;
	private IUserSession $userSession;
	private string $circleId = '';
	private array $circlesToCleanup = [];
	private static array $usersToCleanup = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$app = new Application();
		$userManager = $app->getContainer()->get(IUserManager::class);

		foreach ([self::TEST_USER_1, self::TEST_USER_2, self::TEST_USER_3, self::TEST_USER_4] as $userId) {
			$user = $userManager->get($userId);
			if ($user === null) {
				$userManager->createUser($userId, 'test-pwd');
				self::$usersToCleanup[] = $userId;
			}
		}
	}

	public function setUp(): void {
		parent::setUp();

		$this->app = new Application();
		$this->container = $this->app->getContainer();
		$this->userManager = $this->container->get(IUserManager::class);
		$this->userSession = $this->container->get(IUserSession::class);

		$user1 = $this->userManager->get(self::TEST_USER_1);
		$this->userSession->setUser($user1);

		$this->localController = new LocalController(
			Application::APP_ID,
			$this->container->get(IRequest::class),
			$this->userSession,
			$this->container->get(FederatedUserService::class),
			$this->container->get(CircleService::class),
			$this->container->get(MemberService::class),
			$this->container->get(MembershipService::class),
			$this->container->get(PermissionService::class),
			$this->container->get(SearchService::class),
			$this->container->get(ConfigService::class),
		);

		// Create a circle as TEST_USER_1 (owner)
		$circleResult = $this->localController->create('test-circle')->getData();
		$this->circlesToCleanup[] = $circleResult['id'];
		$this->circleId = $circleResult['id'];
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		$app = new Application();
		$userManager = $app->getContainer()->get(IUserManager::class);

		foreach (self::$usersToCleanup as $userId) {
			$userManager->get($userId)?->delete();
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

	/**
	 * @dataProvider dataForCirclesList
	 */
	public function testCirclesList(int $limit, int $offset, int $expectedCount): void {
		$result1 = $this->localController->create('test-circle1')->getData();
		$result2 = $this->localController->create('test-circle2')->getData();
		$this->circlesToCleanup[] = $result1['id'];
		$this->circlesToCleanup[] = $result2['id'];

		$data = $this->localController->circles($limit, $offset)->getData();

		$this->assertEquals(count($data), $expectedCount);
	}

	public static function dataForCirclesList(): array {
		return [
			[-1, 0, 3],
			[1, 1, 1],
			[-1, 1, 2],
		];
	}

	public function testMemberAdd(): void {
		// Add TEST_USER_2 as a member
		$memberResult = $this->localController->memberAdd($this->circleId, self::TEST_USER_2, Member::TYPE_USER)->getData();

		// Verify the member was added
		$this->assertNotNull($memberResult);
		$this->assertEquals(self::TEST_USER_2, $memberResult['userId']);
		$this->assertEquals($this->circleId, $memberResult['circleId']);
		$this->assertEquals(Member::TYPE_USER, $memberResult['userType']);
	}

	public function testMemberAddPermissionDeniedForNonMember(): void {
		// Switch to TEST_USER_2 (who is not a member)
		$this->userSession->setUser($this->userManager->get(self::TEST_USER_2));

		$response = $this->localController->memberAdd($this->circleId, self::TEST_USER_3, Member::TYPE_USER);
		$this->assertEquals($response->getData()['message'], 'Insufficient permissions to perform this action');
	}

	public function testMemberAddPermissionDeniedForRegularMember(): void {
		// Add TEST_USER_2 as a regular member
		$this->localController->memberAdd($this->circleId, self::TEST_USER_2, Member::TYPE_USER);

		// Switch to TEST_USER_2
		$this->userSession->setUser($this->userManager->get(self::TEST_USER_2));

		// TEST_USER_2 (regular member) tries to add another user
		// This should fail because they're not a moderator
		$response = $this->localController->memberAdd($this->circleId, self::TEST_USER_3, Member::TYPE_USER);
		$this->assertEquals($response->getData()['message'], 'Insufficient permissions to perform this action');
	}

	public function testMemberAddAllowedForRegularMemberInFriendCircle(): void {
		// Make it a friend circle (CFG_FRIEND = 128)
		$this->localController->editConfig($this->circleId, Circle::CFG_FRIEND);

		// Add TEST_USER_2 as a regular member
		$this->localController->memberAdd($this->circleId, self::TEST_USER_2, Member::TYPE_USER);

		// Switch to TEST_USER_2
		$this->userSession->setUser($this->userManager->get(self::TEST_USER_2));

		// TEST_USER_2 (regular member) tries to add TEST_USER_3
		$result = $this->localController->memberAdd($this->circleId, self::TEST_USER_3, Member::TYPE_USER)->getData();

		// Verify the member was added
		$this->assertNotNull($result);
		$this->assertEquals($result['userId'], self::TEST_USER_3);
		$this->assertEquals($result['circleId'], $this->circleId);
	}

	public function testMemberAddAllowedForModerator(): void {
		// Add TEST_USER_2 as a member
		$memberResult = $this->localController->memberAdd($this->circleId, self::TEST_USER_2, Member::TYPE_USER)->getData();

		// Promote TEST_USER_2 to moderator
		$this->localController->memberLevel($this->circleId, $memberResult['id'], Member::LEVEL_MODERATOR);

		// Switch to TEST_USER_2
		$this->userSession->setUser($this->userManager->get(self::TEST_USER_2));

		// TEST_USER_2 (moderator) tries to add TEST_USER_3
		$result = $this->localController->memberAdd($this->circleId, self::TEST_USER_3, Member::TYPE_USER)->getData();

		// Verify the member was added
		$this->assertNotNull($result);
		$this->assertEquals(self::TEST_USER_3, $result['userId']);
		$this->assertEquals($this->circleId, $result['circleId']);
	}

	public function testMembersAddMultipleUsers(): void {
		// Add multiple members at once
		$members = [
			['id' => self::TEST_USER_2, 'type' => Member::TYPE_USER],
			['id' => self::TEST_USER_3, 'type' => Member::TYPE_USER],
		];

		$result = $this->localController->membersAdd($this->circleId, $members)->getData();

		// Verify both members were added
		$this->assertCount(2, $result);
		$this->assertEquals($result[0]['userId'], self::TEST_USER_2);
		$this->assertEquals($result[1]['userId'], self::TEST_USER_3);
	}

	public function testMembersAddPermissionDenied(): void {
		// Switch to TEST_USER_2 (who is not a member)
		$this->userSession->setUser($this->userManager->get(self::TEST_USER_2));

		// Attempt to add multiple members without permission
		$members = [
			['id' => self::TEST_USER_3, 'type' => Member::TYPE_USER],
			['id' => self::TEST_USER_4, 'type' => Member::TYPE_USER],
		];

		$response = $this->localController->membersAdd($this->circleId, $members);
		$this->assertEquals($response->getData()['message'], 'Insufficient permissions to perform this action');
	}
}
