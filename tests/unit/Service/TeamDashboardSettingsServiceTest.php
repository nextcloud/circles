<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\Service\TeamDashboardSettingsService;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use PHPUnit\Framework\TestCase;

class TeamDashboardSettingsServiceTest extends TestCase {
	public function testDefaultsAndExplicitEmptyAreDifferent(): void {
		$service = new TeamDashboardSettingsService();
		self::assertNull($service->decode(null));
		self::assertNull($service->decode('null'));
		self::assertSame([], $service->decode('[]'));
		self::assertSame(['deck-team-today'], $service->validate(['deck-team-today', 'deck-team-today'], ['deck-team-today']));
	}

	/** @dataProvider invalidValues */
	public function testRejectsMalformedSelections(mixed $value): void {
		$this->expectException(OCSBadRequestException::class);
		(new TeamDashboardSettingsService())->validate($value);
	}

	public static function invalidValues(): array {
		return [
			['deck-team-today'],
			[['key' => 'deck-team-today']],
			[[['nested']]],
			[[42]],
			[['']],
			[[str_repeat('a', 65)]],
			[array_fill(0, 101, 'deck-team-today')],
		];
	}

	public function testUnknownIdsAreRejected(): void {
		$this->expectException(OCSBadRequestException::class);
		(new TeamDashboardSettingsService())->validate(['personal-widget'], ['deck-team-today']);
	}

	/** @dataProvider corruptSettings */
	public function testCorruptSettingsFailClosed(string $stored): void {
		$this->expectException(OCSException::class);
		(new TeamDashboardSettingsService())->decode($stored);
	}

	public static function corruptSettings(): array {
		return [['broken'], ['{}'], ['{"widget":"deck"}'], ['"deck"']];
	}
}
