<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\Db\ShareWrapperRequest;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ShareWrapperService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShareWrapperServiceTest extends TestCase {
	private ICache&MockObject $cache;
	private ShareWrapperRequest&MockObject $shareWrapperRequest;
	private ShareWrapperService $service;
	/** @var string[] */
	private array $clearedPrefixes = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cache = $this->createMock(ICache::class);
		$this->cache->method('clear')
			->willReturnCallback(function (string $prefix): bool {
				$this->clearedPrefixes[] = $prefix;

				return true;
			});

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($this->cache);

		$this->shareWrapperRequest = $this->createMock(ShareWrapperRequest::class);

		$this->service = new ShareWrapperService(
			$cacheFactory,
			$this->shareWrapperRequest,
		);
	}

	public function testSaveClearsSharedWithCache(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getSharedWith')->willReturn('circle');
		$this->shareWrapperRequest->expects($this->once())->method('save')->with($share)
			->willReturnCallback(function (): int {
				$this->assertSame([], $this->clearedPrefixes, 'Cache must be cleared after the share is stored');
				return 1;
			});

		$this->service->save($share);

		$this->assertSame([''], $this->clearedPrefixes);
	}

	public function testDeleteClearsSharedWithCache(): void {
		$shareWrapper = new ShareWrapper();
		$shareWrapper->setId('42');
		$shareWrapper->setSharedWith('circle');
		$this->shareWrapperRequest->expects($this->once())->method('delete')->with(42)
			->willReturnCallback(function (): void {
				$this->assertSame([], $this->clearedPrefixes, 'Cache must be cleared after the share is deleted');
			});

		$this->service->delete($shareWrapper);

		$this->assertSame([''], $this->clearedPrefixes);
	}
}
