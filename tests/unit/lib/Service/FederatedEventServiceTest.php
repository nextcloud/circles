<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\Db\EventWrapperRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\RemoteUpstreamService;
use OCP\AppFramework\Services\IAppConfig;
use PHPUnit\Framework\TestCase;

class FederatedEventServiceTest extends TestCase {
	public function testVisitorCannotConfirmAsInitiator(): void {
		$service = $this->createSubject();
		$event = $this->createEvent(Member::LEVEL_VISITOR);

		$this->expectException(InitiatorNotConfirmedException::class);
		$service->confirmInitiator($event);
	}

	public function testMemberCanConfirmAsInitiator(): void {
		$service = $this->createSubject();
		$event = $this->createEvent(Member::LEVEL_MEMBER);

		$service->confirmInitiator($event);
		$this->addToAssertionCount(1);
	}

	private function createEvent(int $level): FederatedEvent {
		$initiator = (new Member())
			->setInstance('test-instance')
			->setLevel($level);
		$circle = (new Circle())->setInitiator($initiator);

		return (new FederatedEvent())
			->setSender('test-instance')
			->setCircle($circle);
	}

	private function createSubject(): FederatedEventService {
		return new FederatedEventService(
			$this->createMock(IAppConfig::class),
			$this->createMock(EventWrapperRequest::class),
			$this->createMock(RemoteRequest::class),
			$this->createMock(MemberRequest::class),
			$this->createMock(RemoteUpstreamService::class),
			$this->createMock(EventService::class),
			$this->createMock(InterfaceService::class),
			$this->createMock(ConfigService::class),
		);
	}
}
