<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit;

use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\FileCacheWrapper;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Service\ShareWrapperService;
use OCA\Circles\ShareByCircleProvider;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShareByCircleProviderTest extends TestCase {
	private ShareByCircleProvider $provider;
	private ShareWrapperService&MockObject $shareWrapperService;
	private FederatedUserService&MockObject $federatedUserService;

	protected function setUp(): void {
		parent::setUp();

		$this->shareWrapperService = $this->createMock(ShareWrapperService::class);
		$this->federatedUserService = $this->createMock(FederatedUserService::class);

		$this->provider = new ShareByCircleProvider(
			$this->createMock(IUserManager::class),
			$this->createMock(IRootFolder::class),
			$this->createMock(IL10N::class),
			$this->createMock(LoggerInterface::class),
			$this->createMock(IURLGenerator::class),
			$this->shareWrapperService,
			$this->createMock(ShareTokenService::class),
			$this->federatedUserService,
			$this->createMock(FederatedEventService::class),
			$this->createMock(CircleService::class),
			$this->createMock(EventService::class),
		);
	}

	public function testGetAllSharesInFolderQueriesWithoutFederatedUser(): void {
		$node = $this->createMock(Folder::class);

		$this->federatedUserService->expects($this->never())
			->method('getLocalFederatedUser');
		$this->shareWrapperService->expects($this->once())
			->method('getSharesInFolder')
			->with(null, $node, false, true)
			->willReturn([]);

		$this->assertSame([], $this->provider->getAllSharesInFolder($node));
	}

	public function testGetAllSharesInFolderGroupsSharesByFileSource(): void {
		$node = $this->createMock(Folder::class);
		$firstShare = $this->createMock(IShare::class);
		$secondShare = $this->createMock(IShare::class);
		$thirdShare = $this->createMock(IShare::class);

		$this->shareWrapperService->method('getSharesInFolder')
			->willReturn([
				$this->createWrappedShare(42, $firstShare),
				$this->createWrappedShare(42, $secondShare),
				$this->createWrappedShare(1337, $thirdShare),
			]);

		$this->assertSame([
			42 => [$firstShare, $secondShare],
			1337 => [$thirdShare],
		], $this->provider->getAllSharesInFolder($node));
	}

	public function testGetAllSharesInFolderSkipsInaccessibleShares(): void {
		$node = $this->createMock(Folder::class);

		$this->shareWrapperService->method('getSharesInFolder')
			->willReturn([
				$this->createWrappedShare(42, $this->createMock(IShare::class), false),
			]);

		$this->assertSame([42 => []], $this->provider->getAllSharesInFolder($node));
	}

	public function testGetSharesInFolderQueriesWithFederatedUser(): void {
		$node = $this->createMock(Folder::class);
		$federatedUser = $this->createMock(FederatedUser::class);

		$this->federatedUserService->expects($this->once())
			->method('getLocalFederatedUser')
			->with('test-user')
			->willReturn($federatedUser);
		$this->shareWrapperService->expects($this->once())
			->method('getSharesInFolder')
			->with($federatedUser, $node, true, true)
			->willReturn([]);

		$this->assertSame([], $this->provider->getSharesInFolder('test-user', $node, true));
	}

	public function testGetSharesInFolderForwardsNonShallow(): void {
		$node = $this->createMock(Folder::class);
		$federatedUser = $this->createMock(FederatedUser::class);

		$this->federatedUserService->method('getLocalFederatedUser')
			->willReturn($federatedUser);
		$this->shareWrapperService->expects($this->once())
			->method('getSharesInFolder')
			->with($federatedUser, $node, true, false)
			->willReturn([]);

		$this->assertSame([], $this->provider->getSharesInFolder('test-user', $node, true, false));
	}

	private function createWrappedShare(
		int $fileSource,
		IShare $share,
		bool $accessible = true,
	): ShareWrapper&MockObject {
		$fileCache = $this->createMock(FileCacheWrapper::class);
		$fileCache->method('isAccessible')->willReturn($accessible);
		$fileCache->method('getPath')->willReturn('__groupfolders/1/document.md');

		$wrappedShare = $this->createMock(ShareWrapper::class);
		$wrappedShare->method('getFileSource')->willReturn($fileSource);
		$wrappedShare->method('getFileCache')->willReturn($fileCache);
		$wrappedShare->method('getShare')->willReturn($share);

		return $wrappedShare;
	}
}
