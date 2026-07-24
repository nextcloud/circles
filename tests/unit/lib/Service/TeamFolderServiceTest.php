<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\TeamFolderService;
use OCP\AppFramework\Services\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TeamFolderServiceTest extends TestCase {
	private TeamFolderService $service;
	private IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->service = new TeamFolderService(
			$this->appConfig,
		);
	}

	public function testShouldCreateTeamFolderSkipsForPersonalCircle(): void {
		$circle = $this->createCircle(Circle::CFG_PERSONAL);
		$this->appConfig->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(true);

		$this->assertFalse($this->service->shouldCreateTeamFolder($circle));
	}

	public function testShouldCreateTeamFolderSkipsForHiddenCircle(): void {
		$circle = $this->createCircle(Circle::CFG_HIDDEN);
		$this->appConfig->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(true);

		$this->assertFalse($this->service->shouldCreateTeamFolder($circle));
	}

	public function testShouldCreateTeamFolderSkipsForSystemCircle(): void {
		$circle = $this->createCircle(Circle::CFG_SYSTEM);
		$this->appConfig->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(true);

		$this->assertFalse($this->service->shouldCreateTeamFolder($circle));
	}

	public function testShouldCreateTeamFolderSkipsForBackendCircle(): void {
		$circle = $this->createCircle(Circle::CFG_BACKEND);
		$this->appConfig->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(true);

		$this->assertFalse($this->service->shouldCreateTeamFolder($circle));
	}

	public function testShouldCreateTeamFolderSkipsWhenAutoCreateDisabled(): void {
		$circle = $this->createCircle();
		$this->appConfig->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(false);

		$this->assertFalse($this->service->shouldCreateTeamFolder($circle));
	}

	public function testShouldCreateTeamFolderReturnsTrueForEligibleCircle(): void {
		$circle = $this->createCircle();
		$this->appConfig->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(true);

		$this->assertTrue($this->service->shouldCreateTeamFolder($circle));
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
