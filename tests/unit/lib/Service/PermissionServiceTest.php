<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\PermissionService;
use OCP\IGroupManager;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

final class PermissionServiceTest extends TestCase {
	private const LIMIT_SINGLE_ID = 'creators-single-id';

	private FederatedUserService&MockObject $federatedUserService;
	private ConfigService&MockObject $configService;
	private PermissionService $service;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$this->federatedUserService = $this->createMock(
			FederatedUserService::class
		);
		$this->configService = $this->createMock(ConfigService::class);

		$this->service = new PermissionService(
			$l10n,
			$this->federatedUserService,
			$this->configService,
			$this->createMock(MemberRequest::class),
			$this->createMock(MembershipRequest::class),
			$this->createMock(IGroupManager::class),
		);
	}

	private function setCreationLimit(string $singleId): void {
		$this->configService->method('getAppValue')
			->with(ConfigService::LIMIT_CIRCLE_CREATION)
			->willReturn($singleId);
	}

	/**
	 * Federated user whose membership lookup for the limit entity either
	 * succeeds or throws MembershipNotFoundException.
	 */
	private function createFederatedUser(
		bool $isLinked,
	): FederatedUser&MockObject {
		$federatedUser = $this->createMock(FederatedUser::class);
		$getLink = $federatedUser->expects($this->once())
			->method('getLink')
			->with(self::LIMIT_SINGLE_ID);
		if ($isLinked) {
			$getLink->willReturn($this->createMock(Membership::class));
		} else {
			$getLink->willThrowException(new MembershipNotFoundException());
		}

		return $federatedUser;
	}

	public function testCanUserCreateCircleWithoutLimit(): void {
		$this->setCreationLimit('');
		$this->federatedUserService->expects($this->never())
			->method('getLocalFederatedUser');

		$this->assertTrue($this->service->canUserCreateCircle('alice'));
	}

	public function testCanUserCreateCircleWhenLinkedToLimit(): void {
		$this->setCreationLimit(self::LIMIT_SINGLE_ID);
		$this->federatedUserService->expects($this->once())
			->method('getLocalFederatedUser')
			->with('alice')
			->willReturn($this->createFederatedUser(true));

		$this->assertTrue($this->service->canUserCreateCircle('alice'));
	}

	public function testCanUserCreateCircleWhenNotLinkedToLimit(): void {
		$this->setCreationLimit(self::LIMIT_SINGLE_ID);
		$this->federatedUserService->expects($this->once())
			->method('getLocalFederatedUser')
			->with('alice')
			->willReturn($this->createFederatedUser(false));

		$this->assertFalse($this->service->canUserCreateCircle('alice'));
	}

	public function testCanUserCreateCircleWhenUserCannotBeResolved(): void {
		$this->setCreationLimit(self::LIMIT_SINGLE_ID);
		$this->federatedUserService->expects($this->once())
			->method('getLocalFederatedUser')
			->with('')
			->willThrowException(new FederatedUserNotFoundException());

		$this->assertFalse($this->service->canUserCreateCircle(''));
	}

	public function testConfirmCircleCreationWithoutLimit(): void {
		$this->setCreationLimit('');
		$this->federatedUserService->expects($this->never())
			->method('getCurrentUser');

		$this->service->confirmCircleCreation();
	}

	public function testConfirmCircleCreationWhenLinkedToLimit(): void {
		$this->setCreationLimit(self::LIMIT_SINGLE_ID);
		$this->federatedUserService->expects($this->once())
			->method('mustHaveCurrentUser');
		$this->federatedUserService->expects($this->once())
			->method('getCurrentUser')
			->willReturn($this->createFederatedUser(true));

		$this->service->confirmCircleCreation();
	}

	public function testConfirmCircleCreationWhenNotLinkedToLimit(): void {
		$this->setCreationLimit(self::LIMIT_SINGLE_ID);
		$this->federatedUserService->expects($this->once())
			->method('mustHaveCurrentUser');
		$this->federatedUserService->expects($this->once())
			->method('getCurrentUser')
			->willReturn($this->createFederatedUser(false));

		$this->expectException(InsufficientPermissionException::class);
		$this->expectExceptionMessage(
			'You have no permission to create a new team'
		);

		$this->service->confirmCircleCreation();
	}
}
