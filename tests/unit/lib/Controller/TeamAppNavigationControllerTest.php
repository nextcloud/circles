<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\Controller\TeamAppNavigationController;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\TeamAppProviderRegistry;
use OCP\AppFramework\OCS\OCSException;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Teams\ITeamAppProvider;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;
use OCP\Teams\TeamAppAction;
use OCP\Teams\TeamAppActionResult;
use OCP\Teams\TeamAppEntry;
use OCP\Teams\TeamAppNavigation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TeamAppNavigationControllerTest extends TestCase {
	private CircleRequest&MockObject $circles;
	private CircleService&MockObject $service;
	private PermissionService&MockObject $permissions;
	private IUserSession&MockObject $session;
	private TeamAppProviderRegistry&MockObject $registry;
	private TeamAppNavigationController $controller;
	private ITeamAppProvider&MockObject $provider;
	private ITeamManager&MockObject $teams;
	private Member $member;
	private bool $authenticated = true;

	protected function setUp(): void {
		$this->circles = $this->createMock(CircleRequest::class);
		$this->service = $this->createMock(CircleService::class);
		$this->permissions = $this->createMock(PermissionService::class);
		$this->member = (new Member())->setLevel(Member::LEVEL_ADMIN);
		$this->permissions->method('userMustBeMember')->willReturn($this->member);
		$this->session = $this->createMock(IUserSession::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('member');
		$this->session->method('getUser')->willReturnCallback(fn () => $this->authenticated ? $user : null);
		$this->registry = $this->createMock(TeamAppProviderRegistry::class);
		$this->provider = $this->createMock(ITeamAppProvider::class);
		$this->registry->method('getProviders')->willReturn(['whiteboard' => $this->provider]);
		$this->teams = $this->createMock(ITeamManager::class);
		$this->teams->method('getTeam')->with('team', 'member')->willReturn(new Team('team', 'Server team', null));
		$this->controller = new TeamAppNavigationController(
			'circles', $this->createMock(IRequest::class), $this->circles, $this->service,
			$this->createMock(FederatedUserService::class), $this->permissions, $this->session, $this->registry,
			$this->teams,
		);
	}

	public function testNavigationUsesServerOwnedTeamUserRoleAndNavigationEntries(): void {
		$this->member->setLevel(Member::LEVEL_MEMBER);
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppPins_whiteboard' => '["42"]']));
		$this->provider->expects($this->once())->method('getNavigation')->willReturnCallback(function (array $context): TeamAppNavigation {
			$this->assertSame('member', $context['userId']);
			$this->assertSame('Server team', $context['teamName']);
			$this->assertFalse($context['isAdmin']);
			$this->assertSame(['42'], $context['navigationEntryIds']);
			$this->assertNull($context['folder']);
			$this->assertIsCallable($context['saveNavigationEntryIds']);
			return new TeamAppNavigation([]);
		});
		$this->assertEquals(new TeamAppNavigation([]), $this->controller->getNavigation('team', 'whiteboard')->getData());
	}

	public function testNonmembersCannotDiscoverAppResources(): void {
		$this->permissions->method('userMustBeMember')->willThrowException(new InsufficientPermissionException());
		$this->teams->expects($this->never())->method('getTeam');
		$this->provider->expects($this->never())->method('getNavigation');
		$this->expectExceptionCode(403);
		$this->controller->getNavigation('team', 'whiteboard');
	}

	public function testMembersCannotExecuteProviderActions(): void {
		$this->permissions->method('memberMustBeAtLeastAdmin')->willThrowException(new InsufficientPermissionException());
		$this->provider->expects($this->never())->method('executeAction');
		$this->expectExceptionCode(403);
		$this->controller->action('team', 'whiteboard', 'whiteboard-create');
	}

	public function testForgedOrStaleActionsAreNotExecuted(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([], []));
		$this->provider->expects($this->never())->method('executeAction');
		$this->expectExceptionCode(404);
		$this->controller->action('team', 'whiteboard', 'delete-99');
	}

	public function testDeclaredActionsPersistProviderNavigationEntriesAndReturnTheEntryToOpen(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([], [new TeamAppAction('whiteboard-create', 'New')]));
		$result = new TeamAppActionResult(['42'], '42');
		$this->provider->expects($this->once())->method('executeAction')->with($this->callback(static fn (array $context): bool => $context['userId'] === 'member' && $context['isAdmin']), 'whiteboard-create', ['value' => 'Sketch'])->willReturn($result);
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsAppPins_whiteboard', '["42"]');
		$this->assertSame($result, $this->controller->action('team', 'whiteboard', 'whiteboard-create', ['value' => 'Sketch'])->getData());
	}

	public function testDeclaredActionCanClearAllNavigationEntries(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppPins_whiteboard' => '["42"]']));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([], [new TeamAppAction('whiteboard-navigation', 'NavigationEntries')]));
		$result = new TeamAppActionResult(navigationEntryIds: []);
		$this->provider->method('executeAction')->willReturn($result);
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsAppPins_whiteboard', '[]');

		$this->assertSame($result, $this->controller->action('team', 'whiteboard', 'whiteboard-navigation')->getData());
	}

	public function testActionWithoutReplacementNavigationEntriesDoesNotChangeStoredNavigationEntries(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([
			new TeamAppEntry('42', 'Board', actions: [new TeamAppAction('rename-42', 'Rename')]),
		]));
		$result = new TeamAppActionResult();
		$this->provider->method('executeAction')->willReturn($result);
		$this->service->expects($this->never())->method('updateSetting');

		$this->assertSame($result, $this->controller->action('team', 'whiteboard', 'rename-42')->getData());
	}

	public function testEntryActionsAreDispatchedAndValidationErrorsBecomeBadRequests(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([
			new TeamAppEntry('42', 'Board', actions: [new TeamAppAction('rename-42', 'Rename')]),
		]));
		$this->provider->method('executeAction')->willThrowException(new \InvalidArgumentException('Invalid name'));
		$this->expectExceptionCode(400);
		$this->controller->action('team', 'whiteboard', 'rename-42', ['value' => '../secret']);
	}

	public function testRoutesExposeNavigationEndpoints(): void {
		$routes = array_column((require __DIR__ . '/../../../../appinfo/routes.php')['ocs'], null, 'name');
		$this->assertSame('/teams/{circleId}/apps/{providerId}/navigation/selection', $routes['TeamAppNavigation#getNavigationEntryIds']['url']);
		$this->assertSame('GET', $routes['TeamAppNavigation#getNavigationEntryIds']['verb']);
		$this->assertSame('/teams/{circleId}/apps/{providerId}/navigation/selection', $routes['TeamAppNavigation#setNavigationEntryIds']['url']);
		$this->assertSame('PUT', $routes['TeamAppNavigation#setNavigationEntryIds']['verb']);
		$this->assertSame('/teams/{circleId}/apps/{providerId}/navigation', $routes['TeamAppNavigation#getNavigation']['url']);
		$this->assertSame('GET', $routes['TeamAppNavigation#getNavigation']['verb']);
		$this->assertArrayHasKey('TeamAppNavigation#action', $routes);
	}

	public function testPreviouslySavedProviderNavigationEntriesRemainReadable(): void {
		$this->circles->method('getCircle')->with('team')->willReturn((new Circle())->setSettings([
			'teamsAppPins_whiteboard' => '["42"]', 'teamsAppPins_other' => '["99"]',
		]));
		$this->permissions->expects($this->never())->method('memberMustBeAtLeastAdmin');
		$this->service->expects($this->never())->method('updateSetting');
		$this->assertSame(['navigationEntryIds' => ['42']], $this->controller->getNavigationEntryIds('team', 'whiteboard')->getData());
	}

	public function testWritesRequireAdminAndOnlyChangeProviderSetting(): void {
		$this->permissions->expects($this->once())->method('memberMustBeAtLeastAdmin');
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsAppPins_whiteboard', '["42"]');
		$this->assertSame(['navigationEntryIds' => ['42']], $this->controller->setNavigationEntryIds('team', 'whiteboard', ['42', '42'])->getData());
	}

	public function testNonmemberCannotReadNavigationEntries(): void {
		$this->permissions->method('userMustBeMember')->willThrowException(new InsufficientPermissionException());
		$this->circles->expects($this->never())->method('getCircle');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(403);
		$this->controller->getNavigationEntryIds('team', 'whiteboard');
	}

	public function testMemberCannotWriteNavigationEntries(): void {
		$this->permissions->method('memberMustBeAtLeastAdmin')->willThrowException(new InsufficientPermissionException());
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(403);
		$this->controller->setNavigationEntryIds('team', 'whiteboard', ['42']);
	}

	public function testUnknownProviderIsRejected(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(404);
		$this->controller->getNavigationEntryIds('team', '../invalid');
	}

	public function testMalformedIdsAreRejected(): void {
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);
		$this->controller->setNavigationEntryIds('team', 'whiteboard', ['']);
	}

	public function testAnonymousUserCannotReadNavigationEntries(): void {
		$this->authenticated = false;
		$this->permissions->expects($this->never())->method('userMustBeMember');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(401);
		$this->controller->getNavigationEntryIds('team', 'whiteboard');
	}

	public function testAcceptsExactNavigationEntryLimits(): void {
		$ids = array_map(static fn (int $id): string => str_pad((string)$id, 64, 'x'), range(1, 100));
		$this->assertSame(['navigationEntryIds' => $ids], $this->controller->setNavigationEntryIds('team', 'whiteboard', $ids)->getData());
	}

	#[DataProvider('invalidIds')]
	public function testRejectsInvalidNavigationEntryShapes(array $ids): void {
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);
		$this->controller->setNavigationEntryIds('team', 'whiteboard', $ids);
	}

	public static function invalidIds(): array {
		return [
			'too many' => [array_fill(0, 101, '1')],
			'too long' => [[str_repeat('x', 65)]],
			'not a string' => [[42]],
			'associative' => [['key' => '42']],
			'control character' => [["\n"]],
		];
	}
}
