<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\Controller\TeamAppNavigationController;
use OCA\Circles\Controller\TeamTabsController;
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
use OCP\Teams\ITeamResourceProvider;
use OCP\Teams\Team;
use OCP\Teams\TeamAppAction;
use OCP\Teams\TeamAppActionResult;
use OCP\Teams\TeamAppEntry;
use OCP\Teams\TeamAppNavigation;
use OCP\Teams\TeamResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TeamAppNavigationControllerTest extends TestCase {
	private CircleRequest&MockObject $circles;
	private CircleService&MockObject $service;
	private PermissionService&MockObject $permissions;
	private IUserSession&MockObject $session;
	private TeamAppProviderRegistry&MockObject $registry;
	private TeamAppNavigationController $controller;
	private ITeamAppProvider&MockObject $provider;
	private ITeamManager&MockObject $teams;
	private LoggerInterface&MockObject $logger;
	/** @var array<string, ITeamAppProvider> */
	private array $providers;
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
		$this->providers = ['whiteboard' => $this->provider];
		$this->registry->method('getProviders')->willReturnCallback(fn () => $this->providers);
		$this->teams = $this->createMock(ITeamManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->teams->method('getTeam')->with('team', 'member')->willReturn(new Team('team', 'Server team', null));
		$this->controller = new TeamAppNavigationController(
			'circles', $this->createMock(IRequest::class), $this->circles, $this->service,
			$this->createMock(FederatedUserService::class), $this->permissions, $this->session, $this->registry,
			$this->teams, $this->logger,
		);
	}

	#[DataProvider('tabOrderMigrationData')]
	public function testTabOrderMigration(array $order, array $resources, array $expected): void {
		$this->member->setLevel(Member::LEVEL_MEMBER);
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsTabOrder' => json_encode($order)]));
		$this->service->expects($this->never())->method('updateSetting');
		$shared = [];
		foreach ($resources as [$providerId, $resourceId]) {
			$provider = $this->createMock(ITeamResourceProvider::class);
			$provider->method('getId')->willReturn($providerId);
			$shared[] = new TeamResource($provider, $resourceId, 'Resource', '/resource');
		}
		$this->teams->expects(in_array('collective', $order, true) ? $this->exactly(2) : $this->never())
			->method('getSharedWith')->with('team', 'member')->willReturn($shared);
		$controller = new TeamTabsController(
			'circles', $this->createMock(IRequest::class), $this->circles, $this->service,
			$this->createMock(FederatedUserService::class), $this->permissions, $this->session, $this->teams,
		);

		$this->assertSame(['order' => $expected], $controller->getTabOrder('team')->getData());
		$this->assertSame(['order' => $expected], $controller->getTabOrder('team')->getData());
	}

	public static function tabOrderMigrationData(): array {
		return [
			'empty' => [[], [], []],
			'old Circles tabs' => [['page-42', 'team-folder', 'board-7', 'home'], [], ['text-42', 'team-folder', 'deck-7', 'home']],
			'canonical tabs' => [['text-42', 'deck-7', 'collectives-9', 'whiteboard-5', 'home'], [], ['text-42', 'deck-7', 'collectives-9', 'whiteboard-5', 'home']],
			'mixed and duplicate tabs' => [['board-7', 'home', 'deck-7', 'page-42', 'text-42'], [], ['deck-7', 'home', 'text-42']],
			'unknown tabs' => [['other-5', 'board-invalid', 'page-', 'home'], [], ['other-5', 'board-invalid', 'page-', 'home']],
			'embedded legacy prefixes' => [['other-board-7', 'text-page-42'], [], ['other-board-7', 'text-page-42']],
			'first shared collective' => [['collective', 'home'], [['deck', '1'], ['collectives', '9'], ['collectives', '10']], ['collectives-9', 'home']],
			'unresolved collective' => [['collective', 'home'], [], ['collective', 'home']],
			'duplicate collective' => [['collective', 'home', 'collectives-9'], [['collectives', '9']], ['collectives-9', 'home']],
		];
	}

	public function testBatchNavigationLoadsSharedContextAndSettingsOnce(): void {
		$text = $this->createMock(ITeamAppProvider::class);
		$this->providers['text'] = $text;
		$this->member->setLevel(Member::LEVEL_MEMBER);
		$this->permissions->expects($this->once())->method('userMustBeMember')->with('member', 'team');
		$this->permissions->expects($this->never())->method('memberMustBeAtLeastAdmin');
		$this->teams->expects($this->once())->method('getTeam')->with('team', 'member');
		$this->teams->expects($this->once())->method('getTeamFolderProvider');
		$this->circles->expects($this->once())->method('getCircle')->with('team')->willReturn((new Circle())->setSettings([
			'teamsAppNavigation_whiteboard' => '["42","42"]', 'teamsAppNavigation_text' => '["7"]',
		]));
		$providerBoards = new TeamAppNavigation([new TeamAppEntry('42', 'Board')]);
		$providerPages = new TeamAppNavigation([new TeamAppEntry('7', 'Page')]);
		$boards = ['entries' => $providerBoards->entries, 'selectedEntryIds' => ['42']];
		$pages = ['entries' => $providerPages->entries, 'selectedEntryIds' => ['7']];
		foreach (['whiteboard' => [$this->provider, $providerBoards], 'text' => [$text, $providerPages]] as [$provider, $navigation]) {
			$provider->expects($this->once())->method('getNavigation')->willReturnCallback(function (string $teamId, string $userId, bool $isAdmin, $folder) use ($navigation): TeamAppNavigation {
				$this->assertSame('team', $teamId);
				$this->assertSame('member', $userId);
				$this->assertFalse($isAdmin);
				$this->assertNull($folder);
				return $navigation;
			});
		}
		$this->service->expects($this->never())->method('updateSetting');
		$this->assertEquals(['providers' => ['whiteboard' => $boards, 'text' => $pages], 'errors' => []], $this->controller->getNavigations('team')->getData());
	}

	public function testBatchNavigationLogsAndIsolatesProviderFailures(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$failure = new \RuntimeException('Provider unavailable');
		$this->provider->method('getNavigation')->willThrowException($failure);
		$text = $this->createMock(ITeamAppProvider::class);
		$this->providers['text'] = $text;
		$pages = new TeamAppNavigation([]);
		$text->expects($this->once())->method('getNavigation')->willReturn($pages);
		$this->logger->expects($this->once())->method('error')->with('Could not load Teams app navigation', [
			'provider' => 'whiteboard', 'teamId' => 'team', 'exception' => $failure,
		]);
		$this->assertSame(['providers' => ['text' => ['entries' => [], 'selectedEntryIds' => []]], 'errors' => ['whiteboard']], $this->controller->getNavigations('team')->getData());
	}

	public function testSavedNavigationIdsMarkAllProviderCandidates(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppNavigation_whiteboard' => '["42","99"]']));
		$navigation = new TeamAppNavigation([new TeamAppEntry('42', 'Pinned'), new TeamAppEntry('43', 'Unpinned')]);
		$this->provider->method('getNavigation')->willReturn($navigation);

		$result = $this->controller->getNavigations('team')->getData()['providers']['whiteboard'];

		$this->assertSame(['42', '43'], array_column($result['entries'], 'id'));
		$this->assertSame(['42'], $result['selectedEntryIds']);
	}

	public function testCanAddAnAvailableEntryToNavigation(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([new TeamAppEntry('42', 'Board')]));
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsAppNavigation_whiteboard', '["42"]');

		$this->controller->setEntryNavigation('team', 'whiteboard', '42', true);
	}

	public function testCanRemoveAnEntryWithoutCurrentlyDiscoveringIt(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppNavigation_whiteboard' => '["42","99"]']));
		$this->provider->expects($this->never())->method('getNavigation');
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsAppNavigation_whiteboard', '["99"]');

		$this->controller->setEntryNavigation('team', 'whiteboard', '42', false);
	}

	public function testMembersCannotChangeEntryNavigation(): void {
		$this->permissions->method('memberMustBeAtLeastAdmin')->willThrowException(new InsufficientPermissionException());
		$this->circles->expects($this->never())->method('getCircle');
		$this->provider->expects($this->never())->method('getNavigation');
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectExceptionCode(403);

		$this->controller->setEntryNavigation('team', 'whiteboard', '42', true);
	}

	public function testCannotAddAnUnavailableEntryToNavigation(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([]));
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectExceptionCode(404);

		$this->controller->setEntryNavigation('team', 'whiteboard', '42', true);
	}

	public function testIndividualNavigationUpdateCannotExceedEntryLimit(): void {
		$ids = array_map(static fn (int $id): string => (string)$id, range(1, 100));
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppNavigation_whiteboard' => json_encode($ids, JSON_THROW_ON_ERROR)]));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([new TeamAppEntry('new-entry', 'New')]));
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectExceptionCode(400);

		$this->controller->setEntryNavigation('team', 'whiteboard', 'new-entry', true);
	}

	public function testBatchNavigationIsolatesInvalidProviderMetadata(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([
			'teamsAppNavigation_whiteboard' => '{"invalid":"42"}',
		]));
		$this->provider->expects($this->never())->method('getNavigation');
		$text = $this->createMock(ITeamAppProvider::class);
		$this->providers['text'] = $text;
		$pages = new TeamAppNavigation([]);
		$text->expects($this->once())->method('getNavigation')->willReturn($pages);
		$this->logger->expects($this->once())->method('error');
		$this->assertSame(['providers' => ['text' => ['entries' => [], 'selectedEntryIds' => []]], 'errors' => ['whiteboard']], $this->controller->getNavigations('team')->getData());
	}

	public function testNonmembersCannotReadBatchNavigation(): void {
		$this->permissions->method('userMustBeMember')->willThrowException(new InsufficientPermissionException());
		$this->teams->expects($this->never())->method('getTeam');
		$this->circles->expects($this->never())->method('getCircle');
		$this->provider->expects($this->never())->method('getNavigation');
		$this->expectExceptionCode(403);
		$this->controller->getNavigations('team');
	}

	public function testAnonymousUsersCannotReadBatchNavigation(): void {
		$this->authenticated = false;
		$this->permissions->expects($this->never())->method('userMustBeMember');
		$this->provider->expects($this->never())->method('getNavigation');
		$this->expectExceptionCode(401);
		$this->controller->getNavigations('team');
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
		$result = new TeamAppActionResult(navigationEntryIdsToAdd: ['42'], open: '42');
		$this->provider->expects($this->once())->method('executeAction')->with('team', 'Server team', 'member', true, null, 'whiteboard-create', ['value' => 'Sketch'])->willReturn($result);
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsAppNavigation_whiteboard', '["42"]');
		$this->assertSame($result, $this->controller->action('team', 'whiteboard', 'whiteboard-create', ['value' => 'Sketch'])->getData());
	}

	public function testActionDeltasPreserveSavedEntriesNotMentionedByProvider(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppNavigation_whiteboard' => '["42","99"]']));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([], [new TeamAppAction('navigation', 'Navigation') ]));
		$result = new TeamAppActionResult(navigationEntryIdsToAdd: ['43'], navigationEntryIdsToRemove: ['42']);
		$this->provider->method('executeAction')->willReturn($result);
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsAppNavigation_whiteboard', '["99","43"]');

		$this->assertSame($result, $this->controller->action('team', 'whiteboard', 'navigation')->getData());
	}

	public function testActionDeltaCannotExceedNavigationEntryLimit(): void {
		$ids = array_map(static fn (int $id): string => (string)$id, range(1, 100));
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppNavigation_whiteboard' => json_encode($ids, JSON_THROW_ON_ERROR)]));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([], [new TeamAppAction('navigation', 'Navigation')]));
		$this->provider->method('executeAction')->willReturn(new TeamAppActionResult(navigationEntryIdsToAdd: ['new-entry']));
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);

		$this->controller->action('team', 'whiteboard', 'navigation');
	}

	public function testActionWithoutReplacementNavigationEntriesDoesNotChangeStoredNavigationEntries(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppNavigation_whiteboard' => '["42"]']));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([
			new TeamAppEntry('42', 'Board', actions: [new TeamAppAction('rename-42', 'Rename')]),
		]));
		$result = new TeamAppActionResult();
		$this->provider->method('executeAction')->willReturn($result);
		$this->service->expects($this->never())->method('updateSetting');

		$this->assertSame($result, $this->controller->action('team', 'whiteboard', 'rename-42')->getData());
	}

	public function testEntryActionsAreDispatchedAndValidationErrorsBecomeBadRequests(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppNavigation_whiteboard' => '["42"]']));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([
			new TeamAppEntry('42', 'Board', actions: [new TeamAppAction('rename-42', 'Rename')]),
		]));
		$this->provider->method('executeAction')->willThrowException(new \InvalidArgumentException('Invalid name'));
		$this->expectExceptionCode(400);
		$this->controller->action('team', 'whiteboard', 'rename-42', ['value' => '../secret']);
	}

	public function testRoutesExposeNavigationEndpoints(): void {
		$routes = array_column((require __DIR__ . '/../../../../appinfo/routes.php')['ocs'], null, 'name');
		$this->assertSame('/teams/{circleId}/apps/navigation', $routes['TeamAppNavigation#getNavigations']['url']);
		$this->assertSame('GET', $routes['TeamAppNavigation#getNavigations']['verb']);
		$this->assertArrayHasKey('TeamAppNavigation#action', $routes);
		$this->assertSame('/teams/{circleId}/apps/{providerId}/entries/navigation', $routes['TeamAppNavigation#setEntryNavigation']['url']);
		$this->assertArrayNotHasKey('TeamAppNavigation#getNavigation', $routes);
		$this->assertArrayNotHasKey('TeamAppNavigation#getNavigationEntryIds', $routes);
		$this->assertArrayNotHasKey('TeamAppNavigation#setNavigationEntryIds', $routes);
	}

	public function testLegacyPinnedNavigationEntriesAreNotPreserved(): void {
		$this->circles->method('getCircle')->with('team')->willReturn((new Circle())->setSettings([
			'teamsAppPins_whiteboard' => '["42"]', 'teamsAppPins_other' => '["99"]',
		]));
		$this->permissions->expects($this->never())->method('memberMustBeAtLeastAdmin');
		$this->service->expects($this->never())->method('updateSetting');
		$this->provider->expects($this->once())->method('getNavigation')->with('team', 'member', true, null)
			->willReturn(new TeamAppNavigation([new TeamAppEntry('42', 'Board')]));
		$result = $this->controller->getNavigations('team')->getData();
		$this->assertSame([], $result['errors']);
		$this->assertSame(['42'], array_column($result['providers']['whiteboard']['entries'], 'id'));
		$this->assertSame([], $result['providers']['whiteboard']['selectedEntryIds']);
	}

	public function testWritesRequireAdminAndOnlyChangeProviderSetting(): void {
		$this->permissions->expects($this->exactly(2))->method('memberMustBeAtLeastAdmin');
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsAppNavigation_whiteboard', '["42"]');
		$this->saveNavigationEntryIds(['42', '42']);
	}

	public function testMemberCannotWriteNavigationEntries(): void {
		$this->permissions->method('memberMustBeAtLeastAdmin')->willThrowException(new InsufficientPermissionException());
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(403);
		$this->saveNavigationEntryIds(['42']);
	}

	public function testUnknownProviderIsRejected(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(404);
		$this->controller->action('team', '../invalid', 'navigation');
	}

	public function testMalformedIdsAreRejected(): void {
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);
		$this->saveNavigationEntryIds(['']);
	}

	public function testAcceptsExactNavigationEntryLimits(): void {
		$ids = array_map(static fn (int $id): string => str_pad((string)$id, 64, 'x'), range(1, 100));
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsAppNavigation_whiteboard', json_encode($ids, JSON_THROW_ON_ERROR));
		$this->saveNavigationEntryIds($ids);
	}

	#[DataProvider('invalidIds')]
	public function testRejectsInvalidNavigationEntryShapes(array $ids): void {
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);
		$this->saveNavigationEntryIds($ids);
	}

	private function saveNavigationEntryIds(array $ids): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getNavigation')->willReturn(new TeamAppNavigation([], [new TeamAppAction('navigation', 'Navigation')]));
		$this->provider->method('executeAction')->willReturn(new TeamAppActionResult(navigationEntryIdsToAdd: $ids));
		$this->controller->action('team', 'whiteboard', 'navigation');
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
