<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TeamFolderPolicyTest extends TestCase {
	private TeamFolderPolicy $service;
	private IAppConfig&MockObject $appConfig;
	private IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValue')
			->with(TeamFolderPolicy::SYSTEM_TEAM_FOLDER_AUTO_CREATE, null)
			->willReturn(null);

		$this->service = new TeamFolderPolicy(
			$this->appConfig,
			$this->config,
		);
	}

	public function testShouldCreateTeamFolderSkipsForPersonalCircle(): void {
		$circle = $this->createCircle(Circle::CFG_PERSONAL);

		$this->assertFalse($this->service->shouldCreateTeamFolder($circle));
	}

	public function testShouldCreateTeamFolderSkipsForHiddenCircle(): void {
		$circle = $this->createCircle(Circle::CFG_HIDDEN);

		$this->assertFalse($this->service->shouldCreateTeamFolder($circle));
	}

	public function testShouldCreateTeamFolderSkipsForSystemCircle(): void {
		$circle = $this->createCircle(Circle::CFG_SYSTEM);

		$this->assertFalse($this->service->shouldCreateTeamFolder($circle));
	}

	public function testShouldCreateTeamFolderSkipsForBackendCircle(): void {
		$circle = $this->createCircle(Circle::CFG_BACKEND);

		$this->assertFalse($this->service->shouldCreateTeamFolder($circle));
	}

	public function testShouldCreateTeamFolderSkipsWhenSystemConfigDisabled(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->with(TeamFolderPolicy::SYSTEM_TEAM_FOLDER_AUTO_CREATE, null)
			->willReturn(false);
		$service = new TeamFolderPolicy($this->appConfig, $config);

		$this->assertFalse($service->isTeamFolderProvisioningEnabled());
		$this->assertFalse($service->shouldCreateTeamFolder($this->createCircle()));
	}

	public function testShouldCreateTeamFolderEnabledWhenSystemConfigTrue(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->with(TeamFolderPolicy::SYSTEM_TEAM_FOLDER_AUTO_CREATE, null)
			->willReturn(true);
		$service = new TeamFolderPolicy($this->appConfig, $config);

		$this->assertTrue($service->isTeamFolderProvisioningEnabled());
		$this->assertTrue($service->shouldCreateTeamFolder($this->createCircle()));
	}

	public function testShouldCreateTeamFolderDefaultsToEnabledWhenUnset(): void {
		$this->assertTrue($this->service->isTeamFolderProvisioningEnabled());
		$this->assertTrue($this->service->shouldCreateTeamFolder($this->createCircle()));
	}

	public function testIsEligibleCircleIndependentOfProvisioningFlag(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->with(TeamFolderPolicy::SYSTEM_TEAM_FOLDER_AUTO_CREATE, null)
			->willReturn(false);
		$service = new TeamFolderPolicy($this->appConfig, $config);

		$this->assertTrue($service->isEligibleCircle($this->createCircle()));
		$this->assertFalse($service->isEligibleCircle($this->createCircle(Circle::CFG_PERSONAL)));
	}

	public function testGetDefaultQuota(): void {
		$this->appConfig->method('getAppValueInt')
			->with(ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, 0)
			->willReturn(1073741824);

		$this->assertSame(1073741824, $this->service->getDefaultQuota());
	}

	/**
	 * @param int $config bitwise circle config flags
	 */
	private function createCircle(int $config = Circle::CFG_CIRCLE): Circle&MockObject {
		$circle = $this->createMock(Circle::class);
		$circle->method('isConfig')
			->willReturnCallback(function (int $flag) use ($config): bool {
				return ($config & $flag) === $flag;
			});

		return $circle;
	}
}
