<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\AppFramework\Services\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TeamFolderPolicyTest extends TestCase {
	private TeamFolderPolicy $service;
	private IAppConfig&MockObject $appConfig;
	private MembershipRequest&MockObject $membershipRequest;
	private CircleRequest&MockObject $circleRequest;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->membershipRequest = $this->createMock(MembershipRequest::class);
		$this->circleRequest = $this->createMock(CircleRequest::class);
		$this->appConfig->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(true);

		$this->service = new TeamFolderPolicy(
			$this->appConfig,
			$this->membershipRequest,
			$this->circleRequest,
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

	public function testShouldCreateTeamFolderSkipsWhenAppConfigDisabled(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(false);
		$service = new TeamFolderPolicy(
			$appConfig,
			$this->membershipRequest,
			$this->circleRequest,
		);

		$this->assertFalse($service->isTeamFolderProvisioningEnabled());
		$this->assertFalse($service->shouldCreateTeamFolder($this->createCircle()));
	}

	public function testShouldCreateTeamFolderEnabledWhenAppConfigTrue(): void {
		$this->assertTrue($this->service->isTeamFolderProvisioningEnabled());
		$this->assertTrue($this->service->shouldCreateTeamFolder($this->createCircle()));
	}

	public function testShouldCreateTeamFolderDefaultsToEnabledWhenUnset(): void {
		$this->assertTrue($this->service->isTeamFolderProvisioningEnabled());
		$this->assertTrue($this->service->shouldCreateTeamFolder($this->createCircle()));
	}

	public function testIsEligibleCircleIndependentOfProvisioningFlag(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(false);
		$service = new TeamFolderPolicy(
			$appConfig,
			$this->membershipRequest,
			$this->circleRequest,
		);

		$this->assertTrue($service->isEligibleCircle($this->createCircle()));
		$this->assertFalse($service->isEligibleCircle($this->createCircle(Circle::CFG_PERSONAL)));
	}

	public function testGetDefaultQuotaUsesConfiguredValue(): void {
		$this->appConfig->method('getAppValueInt')
			->with(ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, ConfigLexicon::DEFAULT_QUOTA)
			->willReturn(1073741824);

		$this->assertSame(1073741824, $this->service->getDefaultQuota());
	}

	public function testGetTeamFolderQuotaReadsValidSetting(): void {
		$circle = (new Circle())->setSettings([Circle::SETTING_TEAM_FOLDER_QUOTA => 5368709120]);

		$this->assertSame(5368709120, $this->service->getTeamFolderQuota($circle));
	}

	public function testGetTeamFolderQuotaIgnoresMissingOrInvalidSetting(): void {
		$this->assertNull($this->service->getTeamFolderQuota((new Circle())->setSettings([])));
		$this->assertNull($this->service->getTeamFolderQuota(
			(new Circle())->setSettings([Circle::SETTING_TEAM_FOLDER_QUOTA => -1]),
		));
	}

	public function testSetTeamFolderQuotaPreservesOtherSettings(): void {
		$circle = (new Circle())->setSettings(['population' => 10]);
		$this->circleRequest->expects($this->once())
			->method('updateSettings')
			->with($this->callback(static fn (Circle $updated): bool => $updated->getSettings() === [
				'population' => 10,
				Circle::SETTING_TEAM_FOLDER_QUOTA => 2147483648,
			]));

		$this->service->setTeamFolderQuota($circle, 2147483648);
	}

	public function testRemoveTeamFolderQuotaPreservesOtherSettings(): void {
		$circle = (new Circle())->setSettings([
			'population' => 10,
			Circle::SETTING_TEAM_FOLDER_QUOTA => 2147483648,
		]);
		$this->circleRequest->expects($this->once())
			->method('updateSettings')
			->with($this->callback(static fn (Circle $updated): bool => $updated->getSettings() === ['population' => 10]));

		$this->service->removeTeamFolderQuota($circle);
	}

	public function testGetQuotaForCircleUsesDefaultWithoutMatchingTeam(): void {
		$this->configureDefaultQuota(ConfigLexicon::DEFAULT_QUOTA);
		$this->configureMemberships('alice', ['support']);
		$this->configureMembershipCircles(['support' => null]);

		$this->assertSame(ConfigLexicon::DEFAULT_QUOTA, $this->service->getQuotaForCircle($this->createCircleWithOwner('alice')));
	}

	public function testGetQuotaForCircleUsesHighestMatchingQuota(): void {
		$this->configureDefaultQuota(ConfigLexicon::DEFAULT_QUOTA);
		$this->configureMemberships('bob', ['marketing', 'engineering']);
		$this->configureMembershipCircles([
			'marketing' => 2147483648,
			'engineering' => 5368709120,
		]);

		$this->assertSame(5368709120, $this->service->getQuotaForCircle($this->createCircleWithOwner('bob')));
	}

	public function testGetQuotaForCirclePrefersTeamOverride(): void {
		$this->membershipRequest->expects($this->never())->method('getMemberships');
		$circle = (new Circle())->setSettings([Circle::SETTING_TEAM_FOLDER_QUOTA => 2147483648]);

		$this->assertSame(2147483648, $this->service->getQuotaForCircle($circle));
	}

	public function testGetQuotaForCircleTreatsUnlimitedAsHighestQuota(): void {
		$this->configureDefaultQuota(ConfigLexicon::DEFAULT_QUOTA);
		$this->configureMemberships('bob', ['marketing', 'engineering']);
		$this->configureMembershipCircles([
			'marketing' => 2147483648,
			'engineering' => 0,
		]);

		$this->assertSame(0, $this->service->getQuotaForCircle($this->createCircleWithOwner('bob')));
	}

	public function testGetQuotaForCircleUsesDefaultForRemoteOwner(): void {
		$this->configureDefaultQuota(ConfigLexicon::DEFAULT_QUOTA);
		$this->membershipRequest->expects($this->never())->method('getMemberships');

		$this->assertSame(ConfigLexicon::DEFAULT_QUOTA, $this->service->getQuotaForCircle($this->createCircleWithOwner('remote-user', false)));
	}

	public function testSetTeamFolderQuotaRejectsNegativeQuota(): void {
		$this->circleRequest->expects($this->never())->method('updateSettings');
		$this->expectException(\InvalidArgumentException::class);

		$this->service->setTeamFolderQuota(new Circle(), -1);
	}

	/** @param array<string, int|null> $quotas */
	private function configureMembershipCircles(array $quotas): void {
		$circles = [];
		foreach ($quotas as $teamId => $quota) {
			$settings = $quota === null ? [] : [Circle::SETTING_TEAM_FOLDER_QUOTA => $quota];
			$circles[$teamId] = (new Circle())->setSingleId($teamId)->setSettings($settings);
		}

		$this->circleRequest->method('getCircle')
			->willReturnCallback(static fn (string $teamId): Circle => $circles[$teamId]);
	}

	private function configureDefaultQuota(int $quota): void {
		$this->appConfig->method('getAppValueInt')->willReturn($quota);
	}

	/** @param list<string> $teamIds */
	private function configureMemberships(string $ownerSingleId, array $teamIds): void {
		$memberships = array_map(function (string $teamId): Membership&MockObject {
			$membership = $this->createMock(Membership::class);
			$membership->method('getCircleId')->willReturn($teamId);
			return $membership;
		}, $teamIds);
		$this->membershipRequest->method('getMemberships')->with($ownerSingleId)->willReturn($memberships);
	}

	private function createCircleWithOwner(string $userId, bool $local = true): Circle&MockObject {
		$owner = $this->createMock(Member::class);
		$owner->method('isLocal')->willReturn($local);
		$owner->method('getSingleId')->willReturn($userId);
		$circle = $this->createMock(Circle::class);
		$circle->method('getOwner')->willReturn($owner);

		return $circle;
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
