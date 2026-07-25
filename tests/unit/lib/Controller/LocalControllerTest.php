<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Controller\LocalController;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Probes\BasicProbe;
use OCA\Circles\Model\Probes\CircleProbe;
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

	/** @var MemberService|MockObject */
	private $memberService;

	/** @var MembershipService|MockObject */
	private $membershipService;

	/** @var SearchService|MockObject */
	private $searchService;

	/** @var PermissionService|MockObject */
	private $permissionService;

	/** @var ConfigService|MockObject */
	private $configService;

	/** @var LocalController */
	private $localController;

	public function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->federatedUserService = $this->createMock(FederatedUserService::class);
		$this->circleService = $this->createMock(CircleService::class);
		$this->memberService = $this->createMock(MemberService::class);
		$this->membershipService = $this->createMock(MembershipService::class);
		$this->searchService = $this->createMock(SearchService::class);
		$this->permissionService = $this->createMock(PermissionService::class);
		$this->configService = $this->createMock(ConfigService::class);
		$this->configService->expects($this->any())->method('getAppValueBool')->with(ConfigService::FRONTEND_ENABLED)->willReturn(true);
		$this->localController = new LocalController(Application::APP_ID,
			$this->request,
			$this->userSession,
			$this->federatedUserService,
			$this->circleService,
			$this->memberService,
			$this->membershipService,
			$this->permissionService,
			$this->searchService,
			$this->configService);
	}

	/**
	 * @dataProvider dataForCirclesList
	 */
	public function testCirclesList(int $limit, int $offset): void {
		$probe = new CircleProbe();
		$probe->filterHiddenCircles()
			  ->filterBackendCircles()
			  ->addDetail(BasicProbe::DETAILS_POPULATION)
			  ->setItemsOffset($offset)
			  ->setItemsLimit($limit);
		$circle1 = new Circle();
		$circle1->setName('Circle One');
		$circle1->setSingleId('CircleOne');
		$circle2 = new Circle();
		$circle2->setName('Circle Two');
		$circle2->setSingleId('CircleTwo');
		$circle3 = new Circle();
		$circle3->setName('Circle Three');
		$circle3->setSingleId('CircleThree');
		$circles = [$circle1, $circle2, $circle3];
		$selectedCircles = array_slice($circles, $offset, $limit > 0 ? $limit : null);
		$this->circleService->expects($this->once())->method('getCircles')->with($probe)->willReturn($selectedCircles);
		$response = new DataResponse($this->serializeArray($selectedCircles));
		$this->assertEquals($response, $this->localController->circles($limit, $offset));
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
