<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Dashboard;

use OCA\Circles\Dashboard\TeamDashboardWidget;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\PermissionService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\Model\WidgetButton;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

final class TeamDashboardWidgetTest extends TestCase {
	private IInitialState&MockObject $initialState;
	private PermissionService&MockObject $permissionService;
	private TeamDashboardWidget $widget;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRoute')->willReturn('/apps/circles/teams');
		$urlGenerator->method('getAbsoluteURL')->willReturnArgument(0);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$this->initialState = $this->createMock(IInitialState::class);
		$this->permissionService = $this->createMock(PermissionService::class);

		$this->widget = new TeamDashboardWidget(
			$urlGenerator,
			$l10n,
			$this->createMock(ConfigService::class),
			$this->initialState,
			$userSession,
			$this->permissionService,
		);
	}

	/**
	 * @return list<string>
	 */
	private function getButtonTypes(): array {
		return array_map(
			static fn (WidgetButton $button): string => $button->getType(),
			$this->widget->getWidgetButtons('alice'),
		);
	}

	public function testWidgetButtonsOfferCreationWhenAllowed(): void {
		$this->permissionService->expects($this->once())
			->method('canUserCreateCircle')
			->with('alice')
			->willReturn(true);

		$this->assertSame(
			[WidgetButton::TYPE_MORE, WidgetButton::TYPE_SETUP],
			$this->getButtonTypes(),
		);
	}

	public function testWidgetButtonsHideCreationWhenNotAllowed(): void {
		$this->permissionService->expects($this->once())
			->method('canUserCreateCircle')
			->with('alice')
			->willReturn(false);

		$this->assertSame([WidgetButton::TYPE_MORE], $this->getButtonTypes());
	}

	public function testLoadProvidesCanCreateTeam(): void {
		$this->permissionService->expects($this->once())
			->method('canUserCreateCircle')
			->with('alice')
			->willReturn(false);
		$this->initialState->expects($this->once())
			->method('provideInitialState')
			->with('canCreateTeam', false);

		$this->widget->load();
	}
}
