<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\FederatedItems;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\FederatedItems\CircleSetting;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Tools\Model\SimpleDataStore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CircleSettingTest extends TestCase {
	private function createSetting(): CircleSetting {
		return new CircleSetting(
			$this->createMock(CircleRequest::class),
			$this->createMock(ShareTokenService::class),
			$this->createMock(ConfigService::class),
			$this->createMock(AuditLogService::class),
		);
	}

	private function createEvent(?string $value, int $level = Member::LEVEL_ADMIN): FederatedEvent {
		$circle = $this->createPartialMock(Circle::class, ['getUrl']);
		$circle->method('getUrl')->willReturn('/team');
		$initiator = $this->createPartialMock(Member::class, ['jsonSerialize']);
		$initiator->method('jsonSerialize')->willReturn([]);
		$circle->setInitiator($initiator->setId('initiator')->setLevel($level));
		$circle->setSettings([
			'otherSetting' => 'preserved',
			Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL => (string)Member::LEVEL_ADMIN,
		]);

		return (new FederatedEvent(CircleSetting::class))
			->setCircle($circle)
			->setParams(new SimpleDataStore([
				'setting' => Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL,
				'value' => $value ?? '',
				'unset' => $value === null,
			]));
	}

	public static function validLevels(): array {
		return [['1'], ['4'], ['8'], [null]];
	}

	#[DataProvider('validLevels')]
	public function testAcceptsVisibilityLevelAndUnset(?string $value): void {
		$event = $this->createEvent($value);
		$this->createSetting()->verify($event);
		$settings = $event->getData()->gArray('settings');

		$this->assertSame('preserved', $settings['otherSetting']);
		if ($value === null) {
			$this->assertArrayNotHasKey(Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL, $settings);
		} else {
			$this->assertSame($value, $settings[Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL]);
		}
	}

	public static function invalidLevels(): array {
		return [['0'], ['9'], ['2'], ['-1'], [''], ['admin'], ['4invalid'], ['4.0']];
	}

	#[DataProvider('invalidLevels')]
	public function testRejectsInvalidVisibilityLevel(string $value): void {
		$this->expectException(FederatedItemBadRequestException::class);
		$this->createSetting()->verify($this->createEvent($value));
	}

	public function testModeratorCannotChangeVisibility(): void {
		$this->expectException(MemberLevelException::class);
		$this->createSetting()->verify($this->createEvent('1', Member::LEVEL_MODERATOR));
	}
}
