<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\FederatedItems;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\FederatedItems\CircleConfig;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Tools\Model\SimpleDataStore;
use PHPUnit\Framework\TestCase;

class CircleConfigTest extends TestCase {
	/**
	 * @dataProvider federationEnablementCases
	 */
	public function testFederationEnablementRejectsExistingVisitors(int $currentConfig, int $requestedConfig): void {
		[$handler, $circleRequest, $memberRequest, $permissionService] = $this->createSubject();
		$circle = $this->createCircle($currentConfig);
		$event = $this->createEvent($circle, $requestedConfig);
		$visitor = (new Member())->setLevel(Member::LEVEL_VISITOR);

		$memberRequest->expects($this->once())
			->method('getMembers')
			->with('circle-single', null, null, 1, false, '', Member::LEVEL_VISITOR)
			->willReturn([$visitor]);
		$circleRequest->expects($this->never())->method('updateConfig');
		$permissionService->expects($this->never())->method('confirmAllowedCircleTypes');
		$this->expectException(FederatedItemBadRequestException::class);
		$this->expectExceptionMessage('Remove Visitor roles before enabling federation');

		$handler->verify($event);
	}

	public static function federationEnablementCases(): array {
		return [
			'normalization adds root' => [Circle::CFG_CIRCLE, Circle::CFG_FEDERATED],
			'root already set' => [Circle::CFG_ROOT, Circle::CFG_ROOT | Circle::CFG_FEDERATED],
		];
	}

	/**
	 * @dataProvider federationEnablementCases
	 */
	public function testFederationEnablementWithoutVisitorsSucceeds(int $currentConfig, int $requestedConfig): void {
		[$handler, , $memberRequest, $permissionService] = $this->createSubject();
		$circle = $this->createCircle($currentConfig);
		$event = $this->createEvent($circle, $requestedConfig);

		$memberRequest->expects($this->once())
			->method('getMembers')
			->with('circle-single', null, null, 1, false, '', Member::LEVEL_VISITOR)
			->willReturn([]);
		$permissionService->expects($this->once())->method('confirmAllowedCircleTypes');

		$handler->verify($event);

		$expectedConfig = $requestedConfig;
		if (!$circle->isConfig(Circle::CFG_ROOT)) {
			$expectedConfig |= Circle::CFG_ROOT;
		}
		$this->assertSame($expectedConfig, $event->getData()->gInt('config'));
		$this->assertSame($expectedConfig, $event->getOutcome()['config']);
	}

	public function testDisablingFederationDoesNotRunVisitorGuard(): void {
		[$handler, , $memberRequest, $permissionService] = $this->createSubject();
		$event = $this->createEvent(
			$this->createCircle(Circle::CFG_ROOT | Circle::CFG_FEDERATED),
			Circle::CFG_ROOT
		);

		$memberRequest->expects($this->never())->method('getMembers');
		$permissionService->expects($this->once())->method('confirmAllowedCircleTypes');

		$handler->verify($event);

		$this->assertSame(Circle::CFG_ROOT, $event->getData()->gInt('config'));
	}

	private function createSubject(): array {
		$circleRequest = $this->createMock(CircleRequest::class);
		$memberRequest = $this->createMock(MemberRequest::class);
		$permissionService = $this->createMock(PermissionService::class);
		$configService = $this->createMock(ConfigService::class);

		return [
			new CircleConfig($circleRequest, $permissionService, $configService, $memberRequest),
			$circleRequest,
			$memberRequest,
			$permissionService,
		];
	}

	private function createCircle(int $config): Circle {
		$initiator = (new Member())->setLevel(Member::LEVEL_ADMIN);

		return (new Circle())
			->setSingleId('circle-single')
			->setConfig($config)
			->setInitiator($initiator);
	}

	private function createEvent(Circle $circle, int $config): FederatedEvent {
		return (new FederatedEvent())
			->setCircle($circle)
			->setParams(new SimpleDataStore(['config' => $config]));
	}
}
