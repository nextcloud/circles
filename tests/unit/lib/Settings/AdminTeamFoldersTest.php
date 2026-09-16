<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Settings;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Service\TeamFolderPolicy;
use OCA\Circles\Settings\AdminTeamFolders;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

final class AdminTeamFoldersTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IL10N&MockObject $l10n;
	private IInitialState&MockObject $initialState;
	private TeamFolderPolicy&MockObject $teamFolderPolicy;
	private AdminTeamFolders $settings;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->teamFolderPolicy = $this->createMock(TeamFolderPolicy::class);
		$this->settings = new AdminTeamFolders(
			$this->appConfig,
			$this->l10n,
			$this->initialState,
			$this->teamFolderPolicy,
		);
	}

	public function testGetFormProvidesDisabledTeamFolderProvisioningState(): void {
		$this->appConfig->expects($this->once())
			->method('getValueInt')
			->with(Application::APP_ID, ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, 0)
			->willReturn(104857600);
		$this->teamFolderPolicy->expects($this->once())
			->method('isTeamFolderProvisioningEnabled')
			->willReturn(false);

		$provided = [];
		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $value) use (&$provided): void {
				$provided[$key] = $value;
			});

		$this->settings->getForm();

		$this->assertSame(104857600, $provided['teamFolderDefaultQuota']);
		$this->assertFalse($provided['teamFolderProvisioningEnabled']);
	}
}
