<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\FederatedItems;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\FederatedItems\CircleSetting;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Tools\Model\SimpleDataStore;
use Test\TestCase;

class CircleSettingTest extends TestCase {
	public function testManagePublishesSettingActivityAfterPersistingSettings(): void {
		$circleRequest = $this->createMock(CircleRequest::class);
		$eventService = $this->createMock(EventService::class);
		$item = new CircleSetting(
			$circleRequest,
			$this->createMock(ShareTokenService::class),
			$this->createMock(ConfigService::class),
			$eventService,
		);
		$circle = (new Circle())->setSingleId('team-single-id');
		$event = (new FederatedEvent())
			->setCircle($circle)
			->setParams(new SimpleDataStore(['setting' => Circle::SETTING_TEAM_FOLDER_QUOTA]))
			->setData(new SimpleDataStore(['settings' => [Circle::SETTING_TEAM_FOLDER_QUOTA => 1073741824]]));

		$circleRequest->expects($this->once())
			->method('updateSettings')
			->with($this->callback(static fn (Circle $updated): bool => $updated->getSettings()[Circle::SETTING_TEAM_FOLDER_QUOTA] === 1073741824));
		$eventService->expects($this->once())
			->method('circleSettingChanged')
			->with($this->callback(static fn (Circle $updated): bool => $updated->getSettings()[Circle::SETTING_TEAM_FOLDER_QUOTA] === 1073741824), Circle::SETTING_TEAM_FOLDER_QUOTA);

		$item->manage($event);
	}
}
