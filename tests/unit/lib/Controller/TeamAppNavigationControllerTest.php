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
use OCA\Circles\Service\TeamOwnedResourceProviderRegistry;
use OCP\AppFramework\OCS\OCSException;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;
use OCP\Teams\ITeamOwnedResourceProvider;
use OCP\Teams\ITeamResourceProvider;
use OCP\Teams\Team;
use OCP\Teams\TeamResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TeamAppNavigationControllerTest extends TestCase {
	private CircleRequest&MockObject $circles;
	private CircleService&MockObject $service;
	private PermissionService&MockObject $permissions;
	private FederatedUserService&MockObject $federatedUserService;
	private IUserSession&MockObject $session;
	private TeamOwnedResourceProviderRegistry&MockObject $registry;
	private TeamAppNavigationController $controller;
	private ITeamOwnedResourceProvider&MockObject $provider;
	private ITeamManager&MockObject $teams;
	private IUser&MockObject $user;
	private LoggerInterface&MockObject $logger;
	/** @var array<string, ITeamOwnedResourceProvider> */
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
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('member');
		$this->session->method('getUser')->willReturnCallback(fn () => $this->authenticated ? $this->user : null);
		$this->registry = $this->createMock(TeamOwnedResourceProviderRegistry::class);
		$this->provider = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->provider->method('canCreateResource')->willReturnCallback(fn (): bool => $this->canCreateResource);
		$this->providers = ['whiteboard' => $this->provider];
		$this->registry->method('getProviders')->willReturnCallback(fn () => $this->providers);
		$this->teams = $this->createMock(ITeamManager::class);
		$this->federatedUserService = $this->createMock(FederatedUserService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->teams->method('getTeam')->with('team', 'member')->willReturn(new Team('team', 'Server team', null));
		$this->controller = new TeamAppNavigationController(
			'circles', $this->createMock(IRequest::class), $this->circles, $this->permissions,
			$this->session, $this->registry, $this->teams, $this->service, $this->federatedUserService, $this->logger,
		);
	}

	private function resource(string $id, string $label): TeamResource {
		return new TeamResource($this->createMock(ITeamResourceProvider::class), $id, $label, '/resource/' . $id);
	}

	private bool $canCreateResource = true;

	#[DataProvider('tabOrderMigrationData')]
	public function testNavigationResponseIncludesMigratedTabOrder(array $order, array $resources, array $expected): void {
		$this->member->setLevel(Member::LEVEL_MEMBER);
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsTabOrder' => json_encode($order)]));
		$this->providers = [];
		foreach (['files' => '42', 'deck' => '7', 'collectives' => '9', 'whiteboard' => '5'] as $providerId => $entryId) {
			$provider = $this->createMock(ITeamOwnedResourceProvider::class);
			$provider->method('getOwnedByTeam')->willReturn([$this->resource($entryId, 'Resource')]);
			$this->providers[$providerId] = $provider;
		}
		$this->service->expects($this->never())->method('updateSetting');
		$shared = [];
		foreach ($resources as [$providerId, $resourceId]) {
			$provider = $this->createMock(ITeamResourceProvider::class);
			$provider->method('getId')->willReturn($providerId);
			$shared[] = new TeamResource($provider, $resourceId, 'Resource', '/resource');
		}
		$this->teams->expects(in_array('collective', $order, true) ? $this->exactly(2) : $this->never())
			->method('getSharedWith')->with('team', 'member')->willReturn($shared);
		$result = $this->controller->getNavigation('team')->getData();
		$this->assertArrayNotHasKey('order', $result);
		$this->assertSame($expected, array_column($result['entries'], 'id'));
		$this->assertSame($expected, array_column($this->controller->getNavigation('team')->getData()['entries'], 'id'));
	}

	public static function tabOrderMigrationData(): array {
		return [
			'empty' => [[], [], ['team-folder', 'collectives-9', 'deck-7', 'files-42', 'whiteboard-5', 'home']],
			'old Circles tabs' => [['page-42', 'team-folder', 'board-7', 'home'], [], ['page-42', 'team-folder', 'deck-7', 'home', 'collectives-9', 'files-42', 'whiteboard-5']],
			'canonical tabs' => [['files-42', 'deck-7', 'collectives-9', 'whiteboard-5', 'home'], [], ['files-42', 'deck-7', 'collectives-9', 'whiteboard-5', 'home', 'team-folder']],
			'mixed and duplicate tabs' => [['board-7', 'home', 'deck-7', 'page-42', 'files-42'], [], ['deck-7', 'home', 'page-42', 'files-42', 'team-folder', 'collectives-9', 'whiteboard-5']],
			'unknown tabs' => [['other-5', 'board-invalid', 'page-', 'home'], [], ['home', 'team-folder', 'collectives-9', 'deck-7', 'files-42', 'whiteboard-5']],
			'embedded legacy prefixes' => [['other-board-7', 'text-page-42'], [], ['team-folder', 'collectives-9', 'deck-7', 'files-42', 'whiteboard-5', 'home']],
			'first shared collective' => [['collective', 'home'], [['deck', '1'], ['collectives', '9'], ['collectives', '10']], ['collectives-9', 'home', 'team-folder', 'deck-7', 'files-42', 'whiteboard-5']],
			'unresolved collective' => [['collective', 'home'], [], ['home', 'team-folder', 'collectives-9', 'deck-7', 'files-42', 'whiteboard-5']],
			'duplicate collective' => [['collective', 'home', 'collectives-9'], [['collectives', '9']], ['collectives-9', 'home', 'team-folder', 'deck-7', 'files-42', 'whiteboard-5']],
		];
	}

	#[DataProvider('navigationSettingsData')]
	public function testBatchNavigationLoadsSharedContextAndSettingsOnce(array $settings, array $entries): void {
		$text = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->providers['text'] = $text;
		$this->member->setLevel(Member::LEVEL_MEMBER);
		$this->permissions->expects($this->once())->method('userMustBeMember')->with('member', 'team');
		$this->permissions->expects($this->never())->method('memberMustBeAtLeastAdmin');
		$this->teams->expects($this->never())->method('getTeam');
		$this->teams->expects($this->once())->method('getTeamFolderProvider');
		$circle = $this->createMock(Circle::class);
		$circle->expects($this->once())->method('getSettings')->willReturn($settings);
		$this->circles->expects($this->once())->method('getCircle')->with('team')->willReturn($circle);
		$providerBoards = [$this->resource('42', 'Board')];
		$providerPages = [$this->resource('7', 'Page')];
		foreach (['whiteboard' => [$this->provider, $providerBoards], 'text' => [$text, $providerPages]] as [$provider, $navigation]) {
			$provider->expects($this->once())->method('getOwnedByTeam')->willReturnCallback(function (string $teamId, string $userId, bool $isAdmin, $folder) use ($navigation): array {
				$this->assertSame('team', $teamId);
				$this->assertSame('member', $userId);
				$this->assertFalse($isAdmin);
				$this->assertNull($folder);
				return $navigation;
			});
		}
		$this->service->expects($this->never())->method('updateSetting');
		$this->assertEquals([
			'entries' => $entries,
			'providers' => [
				'whiteboard' => ['entries' => $providerBoards, 'canAddResource' => false],
				'text' => ['entries' => $providerPages, 'canAddResource' => false],
			],
			'errors' => [],
		], $this->controller->getNavigation('team')->getData());
	}

	public static function navigationSettingsData(): array {
		return [
			'legacy selection settings are ignored' => [[
				'teamsAppNavigation_whiteboard' => '["42","42"]',
				'teamsAppNavigation_text' => '["7"]',
			], [
				['id' => 'team-folder'],
				['id' => 'text-7', 'providerId' => 'text', 'entryId' => '7'],
				['id' => 'whiteboard-42', 'providerId' => 'whiteboard', 'entryId' => '42'],
				['id' => 'home'],
			]],
			'canonical selection and order' => [[
				'teamsNavigation' => '["text-7","whiteboard-42","home"]',
				'teamsAppNavigation_whiteboard' => 'invalid legacy metadata',
			], [
				['id' => 'text-7', 'providerId' => 'text', 'entryId' => '7'],
				['id' => 'whiteboard-42', 'providerId' => 'whiteboard', 'entryId' => '42'],
				['id' => 'home'],
				['id' => 'team-folder'],
			]],
		];
	}

	public function testMissingProviderSettingsMigrateLegacyTabOrder(): void {
		$files = $this->createMock(ITeamOwnedResourceProvider::class);
		$deck = $this->createMock(ITeamOwnedResourceProvider::class);
		$collectives = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->providers = ['files' => $files, 'deck' => $deck, 'collectives' => $collectives];
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([
			'teamsTabOrder' => '["page-7","board-42","collective","home"]',
		]));
		$providerNavigations = [
			'files' => [$this->resource('7', 'Page')],
			'deck' => [$this->resource('42', 'Board')],
			'collectives' => [$this->resource('9', 'Collective')],
		];
		foreach ($this->providers as $providerId => $provider) {
			$provider->method('getOwnedByTeam')->willReturn($providerNavigations[$providerId]);
		}
		$shared = [];
		foreach ([['deck', '42'], ['collectives', '9']] as [$providerId, $resourceId]) {
			$resourceProvider = $this->createMock(ITeamResourceProvider::class);
			$resourceProvider->method('getId')->willReturn($providerId);
			$shared[] = new TeamResource($resourceProvider, $resourceId, 'Resource', '/resource');
		}
		$this->teams->expects($this->once())->method('getSharedWith')->with('team', 'member')->willReturn($shared);
		$this->service->expects($this->never())->method('updateSetting');

		$result = $this->controller->getNavigation('team')->getData()['providers'];

		$this->assertSame(['7'], array_map(static fn (TeamResource $resource) => $resource->getId(), $result['files']['entries']));
		$this->assertSame(['42'], array_map(static fn (TeamResource $resource) => $resource->getId(), $result['deck']['entries']));
		$this->assertSame(['9'], array_map(static fn (TeamResource $resource) => $resource->getId(), $result['collectives']['entries']));
	}

	public function testMissingNavigationOrderShowsEveryProviderEntryByDefault(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getOwnedByTeam')->willReturn([
			$this->resource('42', 'Board'),
			$this->resource('43', 'Page'),
		]);

		$result = $this->controller->getNavigation('team')->getData();

		$this->assertSame(['team-folder', 'whiteboard-42', 'whiteboard-43', 'home'], array_column($result['entries'], 'id'));
		$this->assertSame(['42', '43'], array_map(static fn (TeamResource $resource) => $resource->getId(), $result['providers']['whiteboard']['entries']));
	}

	public function testNativePagesAndEarlierAliasesKeepTheirSavedOrderWithoutProviders(): void {
		$this->providers = [];
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([
			'teamsNavigation' => '["page-42","home","files-43","team-folder"]',
		]));

		$entries = $this->controller->getNavigation('team')->getData()['entries'];

		$this->assertSame([
			['id' => 'page-42'],
			['id' => 'home'],
			['id' => 'files-43', 'providerId' => 'files', 'entryId' => '43'],
			['id' => 'team-folder'],
		], $entries);
	}

	#[DataProvider('legacyNavigationData')]
	public function testLegacyNavigationKeepsMissingEntriesUntilExplicitlySaved(array $settings, array $expected): void {
		$this->member->setLevel(Member::LEVEL_MEMBER);
		$circle = $this->createMock(Circle::class);
		$circle->expects($this->once())->method('getSettings')->willReturn($settings);
		$this->circles->expects($this->once())->method('getCircle')->with('team')->willReturn($circle);
		$this->providers = [];
		foreach (['files' => ['42'], 'deck' => ['7', '8'], 'collectives' => ['9']] as $providerId => $ids) {
			$provider = $this->createMock(ITeamOwnedResourceProvider::class);
			$provider->expects($this->once())->method('getOwnedByTeam')->willReturn(array_map(fn (string $id): TeamResource => $this->resource($id, 'Resource'), $ids));
			$this->providers[$providerId] = $provider;
		}
		$this->service->expects($this->never())->method('updateSetting');

		$result = $this->controller->getNavigation('team')->getData();

		$this->assertSame([], $result['errors']);
		$this->assertSame($expected, array_column($result['entries'], 'id'));
	}

	public static function legacyNavigationData(): array {
		$defaults = ['team-folder', 'collectives-9', 'deck-7', 'deck-8', 'files-42', 'home'];
		return [
			'never reordered' => [[], $defaults],
			'empty old order' => [['teamsTabOrder' => '[]'], $defaults],
			'partial old order' => [
				['teamsTabOrder' => '["page-42","home","board-7"]'],
				['page-42', 'home', 'deck-7', 'team-folder', 'collectives-9', 'deck-8', 'files-42'],
			],
			'resources added after the last reorder' => [
				['teamsTabOrder' => '["team-folder","home"]'],
				['team-folder', 'home', 'collectives-9', 'deck-7', 'deck-8', 'files-42'],
			],
			'saved order overrides the old order' => [
				['teamsNavigation' => '["home","deck-8"]', 'teamsTabOrder' => '["page-42","board-7"]'],
				['home', 'deck-8', 'team-folder', 'files-42', 'deck-7', 'collectives-9'],
			],
			'empty new order still includes every resource' => [
				['teamsNavigation' => '[]', 'teamsTabOrder' => '["page-42","board-7"]'],
				['team-folder', 'home', 'files-42', 'deck-7', 'deck-8', 'collectives-9'],
			],
		];
	}

	public function testFirstNavigationSaveMigratesOrderWithoutHidingEntries(): void {
		$circle = (new Circle())->setSettings(['teamsTabOrder' => '["home","board-7"]']);
		$this->circles->method('getCircle')->with('team')->willReturn($circle);
		$this->providers = ['deck' => $this->provider];
		$entries = [$this->resource('7', 'Old board'), $this->resource('8', 'New board')];
		$this->provider->method('getOwnedByTeam')->willReturnCallback(static function () use (&$entries): array {
			return $entries;
		});
		$this->service->expects($this->once())->method('updateSetting')
			->with('team', 'teamsNavigation', '["deck-8","home","team-folder"]')
			->willReturnCallback(static function (string $circleId, string $setting, string $value) use ($circle): array {
				$circle->setSettings([$setting => $value] + $circle->getSettings());
				return [];
			});
		$this->assertSame(
			['home', 'deck-7', 'team-folder', 'deck-8'],
			array_column($this->controller->getNavigation('team')->getData()['entries'], 'id'),
		);

		$this->controller->setNavigation('team', ['deck-8', 'home', 'team-folder']);

		$this->assertSame('["home","board-7"]', $circle->getSettings()['teamsTabOrder']);
		$this->assertSame(
			['deck-8', 'home', 'team-folder', 'deck-7'],
			array_column($this->controller->getNavigation('team')->getData()['entries'], 'id'),
		);
		$entries[] = $this->resource('9', 'Later board');
		$this->assertSame(
			['deck-8', 'home', 'team-folder', 'deck-7', 'deck-9'],
			array_column($this->controller->getNavigation('team')->getData()['entries'], 'id'),
		);
	}

	public function testBatchNavigationLogsAndIsolatesProviderFailures(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$failure = new \RuntimeException('Provider unavailable');
		$this->provider->method('getOwnedByTeam')->willThrowException($failure);
		$text = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->providers['text'] = $text;
		$pages = [];
		$text->expects($this->once())->method('getOwnedByTeam')->willReturn($pages);
		$this->logger->expects($this->once())->method('error')->with('Could not load Teams app navigation', [
			'provider' => 'whiteboard', 'teamId' => 'team', 'exception' => $failure,
		]);
		$this->assertSame(['entries' => [['id' => 'team-folder'], ['id' => 'home']], 'providers' => ['text' => ['entries' => [], 'canAddResource' => false]], 'errors' => ['whiteboard']], $this->controller->getNavigation('team')->getData());
	}

	public function testProviderEntriesAreVisibleRegardlessOfLegacySelection(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsAppNavigation_whiteboard' => '["42","99"]']));
		$navigation = [$this->resource('42', 'Pinned'), $this->resource('43', 'Unpinned')];
		$this->provider->method('getOwnedByTeam')->willReturn($navigation);

		$result = $this->controller->getNavigation('team')->getData()['providers']['whiteboard'];

		$this->assertSame(['42', '43'], array_map(static fn (TeamResource $resource) => $resource->getId(), $result['entries']));
		$this->assertTrue($result['canAddResource']);
	}

	public function testNavigationRetainsSavedEntriesOfUnavailableProviders(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([
			'teamsNavigation' => '["home","whiteboard-draft-7","team-folder"]',
		]));
		$this->provider->method('getOwnedByTeam')->willThrowException(new \RuntimeException('Unavailable'));
		$this->logger->expects($this->once())->method('error');

		$result = $this->controller->getNavigation('team')->getData();

		$this->assertSame([
			['id' => 'home'],
			['id' => 'whiteboard-draft-7', 'providerId' => 'whiteboard', 'entryId' => 'draft-7'],
			['id' => 'team-folder'],
		], $result['entries']);
		$this->assertSame(['whiteboard'], $result['errors']);
		$this->assertSame([], $result['providers']);
	}

	public function testCanonicalOrderDoesNotHideUnlistedProviderEntries(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([
			'teamsNavigation' => '[]',
			'teamsTabOrder' => '["whiteboard-42","home"]',
			'teamsAppNavigation_whiteboard' => '["42"]',
		]));
		$this->provider->method('getOwnedByTeam')->willReturn([
			$this->resource('42', 'Board'),
			$this->resource('43', 'Page'),
		]);

		$result = $this->controller->getNavigation('team')->getData();

		$this->assertSame(['42', '43'], array_map(static fn (TeamResource $resource) => $resource->getId(), $result['providers']['whiteboard']['entries']));
		$this->assertSame(['team-folder', 'home', 'whiteboard-42', 'whiteboard-43'], array_column($result['entries'], 'id'));
	}

	public function testCanonicalNavigationDoesNotMigrateLegacyLookingIds(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([
			'teamsNavigation' => '["board-7","home","collective"]',
		]));
		$this->teams->expects($this->never())->method('getSharedWith');
		$provider = $this->createMock(ITeamOwnedResourceProvider::class);
		$provider->method('getOwnedByTeam')->willReturn([$this->resource('7', 'Board')]);
		$this->providers = ['board' => $provider];

		$result = $this->controller->getNavigation('team')->getData();

		$this->assertSame(['board-7', 'home', 'team-folder'], array_column($result['entries'], 'id'));
	}

	public function testBatchNavigationIgnoresInvalidLegacySelectionMetadata(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([
			'teamsAppNavigation_whiteboard' => '{"invalid":"42"}',
		]));
		$boards = [
			$this->resource('42', 'Board'),
		];
		$this->provider->expects($this->once())->method('getOwnedByTeam')->willReturn($boards);
		$text = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->providers['text'] = $text;
		$pages = [];
		$text->expects($this->once())->method('getOwnedByTeam')->willReturn($pages);
		$this->assertSame([
			'entries' => [
				['id' => 'team-folder'],
				['id' => 'whiteboard-42', 'providerId' => 'whiteboard', 'entryId' => '42'],
				['id' => 'home'],
			],
			'providers' => [
				'whiteboard' => ['entries' => $boards, 'canAddResource' => true],
				'text' => ['entries' => [], 'canAddResource' => false],
			],
			'errors' => [],
		], $this->controller->getNavigation('team')->getData());
	}

	public function testNonmembersCannotReadBatchNavigation(): void {
		$this->permissions->method('userMustBeMember')->willThrowException(new InsufficientPermissionException());
		$this->teams->expects($this->never())->method('getTeam');
		$this->circles->expects($this->never())->method('getCircle');
		$this->provider->expects($this->never())->method('getOwnedByTeam');
		$this->expectExceptionCode(403);
		$this->controller->getNavigation('team');
	}

	public function testAnonymousUsersCannotReadBatchNavigation(): void {
		$this->authenticated = false;
		$this->permissions->expects($this->never())->method('userMustBeMember');
		$this->provider->expects($this->never())->method('getOwnedByTeam');
		$this->expectExceptionCode(401);
		$this->controller->getNavigation('team');
	}

	public function testMembersCannotCreateProviderResources(): void {
		$this->permissions->method('memberMustBeAtLeastAdmin')->willThrowException(new InsufficientPermissionException());
		$this->provider->expects($this->never())->method('createResource');
		$this->expectExceptionCode(403);
		$this->controller->createResource('team', 'whiteboard');
	}

	public function testCreationIsRejectedWhenTheProviderDisallowsIt(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->canCreateResource = false;
		$this->provider->expects($this->never())->method('getOwnedByTeam');
		$this->provider->expects($this->never())->method('createResource');
		$this->expectExceptionCode(403);
		$this->controller->createResource('team', 'whiteboard');
	}

	public function testInvalidProviderNavigationRequestReturnsBadRequest(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getOwnedByTeam')->willThrowException(new \InvalidArgumentException('Invalid navigation request'));
		$this->provider->expects($this->never())->method('createResource');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);

		$this->controller->createResource('team', 'whiteboard');
	}

	public function testForbiddenProviderNavigationReturnsForbidden(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getOwnedByTeam')->willThrowException(new \OCP\Files\NotPermittedException('Not permitted'));
		$this->provider->expects($this->never())->method('createResource');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(403);

		$this->controller->createResource('team', 'whiteboard');
	}

	public function testDeclaredCreateActionPinsAndReturnsTheNewResourceId(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getOwnedByTeam')->willReturn([]);
		$this->provider->expects($this->once())->method('createResource')->with('team', 'Server team', 'member', true, null)->willReturn('42');
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsNavigation', '["whiteboard-42","team-folder","home"]');
		$this->assertSame('42', $this->controller->createResource('team', 'whiteboard')->getData());
	}

	public function testCreateActionSavesTheSubmittedOrderWithTheNewEntry(): void {
		$circle = $this->createMock(Circle::class);
		$circle->expects($this->once())->method('getSettings')->willReturn(['teamsTabOrder' => '["collective","home"]']);
		$this->circles->expects($this->once())->method('getCircle')->with('team')->willReturn($circle);
		$this->teams->expects($this->never())->method('getSharedWith');
		$this->provider->method('getOwnedByTeam')->willReturn([]);
		$this->provider->expects($this->once())->method('createResource')->willReturn('42');
		$this->service->expects($this->once())->method('updateSetting')
			->with('team', 'teamsNavigation', '["whiteboard-42","team-folder","home","board-7"]');

		$result = $this->controller->createResource('team', 'whiteboard', ['team-folder', 'home', 'board-7']);

		$this->assertSame('42', $result->getData());
	}

	public function testDeclaredCreateActionPreservesDefaultEntriesWithoutSavedSettings(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getOwnedByTeam')->willReturn([$this->resource('42', 'Board')]);
		$this->provider->expects($this->once())->method('createResource')->willReturn('43');
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsNavigation', '["whiteboard-43","team-folder","whiteboard-42","home"]');

		$this->assertSame('43', $this->controller->createResource('team', 'whiteboard')->getData());
	}

	public function testCreateActionMigratesPartialLegacyOrderBeforeSaving(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsTabOrder' => '["home","board-7"]']));
		$this->providers = ['deck' => $this->provider];
		$this->provider->expects($this->once())->method('getOwnedByTeam')->willReturn([$this->resource('7', 'Old board'), $this->resource('8', 'New board')]);
		$this->provider->expects($this->once())->method('createResource')->willReturn('9');
		$this->service->expects($this->once())->method('updateSetting')
			->with('team', 'teamsNavigation', '["deck-9","home","deck-7","team-folder","deck-8"]');

		$this->assertSame('9', $this->controller->createResource('team', 'deck')->getData());
	}

	public function testCreatingAResourcePreservesExistingNavigationEntries(): void {
		$circle = $this->createMock(Circle::class);
		$circle->expects($this->once())->method('getSettings')->willReturn(['teamsAppNavigation_whiteboard' => '["42","99"]']);
		$this->circles->expects($this->once())->method('getCircle')->with('team')->willReturn($circle);
		$this->provider->method('getOwnedByTeam')->willReturn([
			$this->resource('42', 'Board'),
			$this->resource('99', 'Page'),
		]);
		$this->provider->expects($this->once())->method('createResource')->with('team', 'Server team', 'member', true, null)->willReturn('43');
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsNavigation', '["whiteboard-43","team-folder","whiteboard-42","whiteboard-99","home"]');

		$this->assertSame('43', $this->controller->createResource('team', 'whiteboard')->getData());
	}

	public function testCreateActionIgnoresInvalidLegacySelectionSetting(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([
			'teamsAppNavigation_whiteboard' => '{"invalid":"42"}',
		]));
		$this->provider->method('getOwnedByTeam')->willReturn([$this->resource('42', 'Board')]);
		$this->provider->expects($this->once())->method('createResource')->willReturn('43');
		$this->service->expects($this->once())->method('updateSetting')
			->with('team', 'teamsNavigation', '["whiteboard-43","team-folder","whiteboard-42","home"]');

		$this->assertSame('43', $this->controller->createResource('team', 'whiteboard')->getData());
	}

	public function testInvalidResourceIdDoesNotUpdateNavigation(): void {
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings([]));
		$this->provider->method('getOwnedByTeam')->willReturn([]);
		$this->provider->method('createResource')->willReturn('');
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);

		$this->controller->createResource('team', 'whiteboard');
	}

	public function testCreatingAResourceCannotExceedNavigationEntryLimit(): void {
		$ids = array_map(static fn (int $id): string => (string)$id, range(1, 100));
		$this->circles->method('getCircle')->willReturn((new Circle())->setSettings(['teamsNavigation' => json_encode($ids, JSON_THROW_ON_ERROR)]));
		$this->provider->method('getOwnedByTeam')->willReturn([]);
		$this->provider->expects($this->never())->method('createResource');
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);

		$this->controller->createResource('team', 'whiteboard');
	}

	public function testRoutesExposeNavigationEndpoints(): void {
		$routes = array_column((require __DIR__ . '/../../../../appinfo/routes.php')['ocs'], null, 'name');
		$this->assertSame('/teams/{circleId}/apps/navigation', $routes['TeamAppNavigation#getNavigation']['url']);
		$this->assertSame('GET', $routes['TeamAppNavigation#getNavigation']['verb']);
		$this->assertSame('/teams/{circleId}/apps/{providerId}/resources', $routes['TeamAppNavigation#createResource']['url']);
		$this->assertSame('POST', $routes['TeamAppNavigation#createResource']['verb']);
		$this->assertSame('/teams/{circleId}/apps/navigation', $routes['TeamAppNavigation#setNavigation']['url']);
		$this->assertSame('PUT', $routes['TeamAppNavigation#setNavigation']['verb']);
		$this->assertArrayNotHasKey('TeamAppNavigation#setTabOrder', $routes);
		$this->assertArrayNotHasKey('TeamAppNavigation#getTabOrder', $routes);
		$this->assertArrayNotHasKey('TeamTabs#getTabOrder', $routes);
		$this->assertArrayNotHasKey('TeamTabs#setTabOrder', $routes);
		$this->assertArrayNotHasKey('TeamAppNavigation#setEntryNavigation', $routes);
		$this->assertArrayNotHasKey('TeamAppNavigation#getNavigations', $routes);
		$this->assertArrayNotHasKey('TeamAppNavigation#getNavigationEntryIds', $routes);
		$this->assertArrayNotHasKey('TeamAppNavigation#setNavigationEntryIds', $routes);
	}

	public function testLegacyPinnedSettingsDoNotHideProviderEntries(): void {
		$this->circles->method('getCircle')->with('team')->willReturn((new Circle())->setSettings([
			'teamsNavigation' => '["home"]', 'teamsAppPins_whiteboard' => '["42"]', 'teamsAppPins_other' => '["99"]',
		]));
		$this->permissions->expects($this->never())->method('memberMustBeAtLeastAdmin');
		$this->service->expects($this->never())->method('updateSetting');
		$this->provider->expects($this->once())->method('getOwnedByTeam')->with('team', 'member', true, null)
			->willReturn([$this->resource('42', 'Board')]);
		$result = $this->controller->getNavigation('team')->getData();
		$this->assertSame([], $result['errors']);
		$this->assertSame(['42'], array_map(static fn (TeamResource $resource) => $resource->getId(), $result['providers']['whiteboard']['entries']));
	}

	public function testSetNavigationSavesOrderOnly(): void {
		$this->permissions->expects($this->once())->method('memberMustBeAtLeastAdmin')->with($this->member);
		$this->federatedUserService->expects($this->once())->method('setLocalCurrentUser')->with($this->user);
		$this->service->expects($this->once())->method('updateSetting')
			->with('team', 'teamsNavigation', '["team-folder","whiteboard-42","home"]');

		$response = $this->controller->setNavigation('team', ['team-folder', 'whiteboard-42', 'home']);

		$this->assertSame([], $response->getData());
	}

	public function testSetNavigationSanitizesInvalidAndDuplicateIds(): void {
		$this->teams->expects($this->never())->method('getSharedWith');
		$this->service->expects($this->once())->method('updateSetting')
			->with('team', 'teamsNavigation', '["team-folder","whiteboard-42","board-7","page-42","collective"]');

		$response = $this->controller->setNavigation('team', [
			'team-folder', 'whiteboard-42', '', str_repeat('x', 65), "invalid\nid", 'whiteboard-42', 42,
			'board-7', 'page-42', 'collective',
		]);

		$this->assertSame([], $response->getData());
	}

	public function testMemberCannotSetNavigation(): void {
		$this->permissions->method('memberMustBeAtLeastAdmin')->willThrowException(new InsufficientPermissionException());
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(403);
		$this->controller->setNavigation('team', ['whiteboard-42']);
	}

	public function testUnknownProviderIsRejected(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(404);
		$this->controller->createResource('team', '../invalid');
	}

}
