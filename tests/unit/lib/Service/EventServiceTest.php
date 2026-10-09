<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Service;

use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\ActivityService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCP\EventDispatcher\IEventDispatcher;
use Test\TestCase;

class EventServiceTest extends TestCase {
	public function testCircleEditingPublishesActivity(): void {
		$activityService = $this->createMock(ActivityService::class);
		$service = new EventService($this->createMock(IEventDispatcher::class), $activityService);
		$circle = $this->createMock(Circle::class);
		$event = (new FederatedEvent())
			->setCircle($circle)
			->setData(new SimpleDataStore(['name' => 'New team title']));

		$activityService->expects($this->once())
			->method('onCircleEdited')
			->with($circle, 'name');

		$service->circleEditing($event);
	}

	public function testCircleSettingChangedPublishesActivity(): void {
		$activityService = $this->createMock(ActivityService::class);
		$service = new EventService($this->createMock(IEventDispatcher::class), $activityService);
		$circle = $this->createMock(Circle::class);

		$activityService->expects($this->once())
			->method('onCircleSetting')
			->with($circle, Circle::SETTING_TEAM_FOLDER_QUOTA);

		$service->circleSettingChanged($circle, Circle::SETTING_TEAM_FOLDER_QUOTA);
	}

	public function testCircleConfigChangedPublishesActivity(): void {
		$activityService = $this->createMock(ActivityService::class);
		$service = new EventService($this->createMock(IEventDispatcher::class), $activityService);
		$circle = $this->createMock(Circle::class);

		$activityService->expects($this->once())
			->method('onCircleConfigChanged')
			->with($circle, Circle::CFG_VISIBLE, Circle::CFG_VISIBLE | Circle::CFG_OPEN);

		$service->circleConfigChanged($circle, Circle::CFG_VISIBLE, Circle::CFG_VISIBLE | Circle::CFG_OPEN);
	}
}
