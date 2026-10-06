<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Model;

use OCA\Circles\Model\FileCacheWrapper;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class FileCacheWrapperTest extends TestCase {
	public static function dataIsAccessible(): array {
		return [
			'home file' => ['home::alice', 'files/doc.txt', true],
			'home trashbin' => ['home::alice', 'files_trashbin/files/doc.txt.d1700000000', false],
			'home versions' => ['home::alice', 'files_versions/doc.txt.v1700000000', false],
			'object store home file' => ['object::user:alice', 'files/doc.txt', true],
			'object store home trashbin' => ['object::user:alice', 'files_trashbin/files/doc.txt.d1700000000', false],
			'group folder in root storage' => ['local::/var/www/html/data/', '__groupfolders/1/doc.txt', true],
			'group folder trashbin in root storage' => ['local::/var/www/html/data/', '__groupfolders/trash/1/doc.txt.d1700000000', false],
			'group folder in root object storage' => ['object::store:amazon::bucket', '__groupfolders/1/doc.txt', true],
			'group folder trashbin in root object storage' => ['object::store:amazon::bucket', '__groupfolders/trash/1/doc.txt.d1700000000', false],
			'group folder in separate local storage' => ['local::/var/www/html/data/__groupfolders/1/', 'files/doc.txt', true],
			'group folder trashbin in separate local storage' => ['local::/var/www/html/data/__groupfolders/1/', 'trash/doc.txt.d1700000000', false],
			'group folder versions in separate local storage' => ['local::/var/www/html/data/__groupfolders/1/', 'versions/1/1700000000', false],
			'group folder in separate object storage' => ['object::groupfolder:1.object::store:amazon::bucket', 'files/doc.txt', true],
			'group folder trashbin in separate object storage' => ['object::groupfolder:1.object::store:amazon::bucket', 'trash/doc.txt.d1700000000', false],
			'external storage file' => ['local::/mnt/external/', 'trash/doc.txt', true],
			'external storage root' => ['local::/mnt/external/', '', true],
			'home root' => ['home::alice', '', false],
		];
	}

	#[DataProvider('dataIsAccessible')]
	public function testIsAccessible(string $storage, string $path, bool $expected): void {
		$fileCache = new FileCacheWrapper();
		$fileCache->setId(42)
			->setStorage($storage)
			->setPath($path);

		$this->assertSame($expected, $fileCache->isAccessible());
	}

	public function testIsAccessibleWithoutFileId(): void {
		$fileCache = new FileCacheWrapper();
		$fileCache->setStorage('home::alice')
			->setPath('files/doc.txt');

		$this->assertFalse($fileCache->isAccessible());
	}
}
