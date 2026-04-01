<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\External;

use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IScanner;
use OCP\Files\Cache\IWatcher;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Files\Storage\IReliableEtagStorage;
use OCP\Files\Storage\IStorage;

class Storage implements IDisableEncryptionStorage, IReliableEtagStorage {
	public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher {
	}

	public function getRemoteUser(): string {
	}

	public function getRemote(): string {
	}

	public function getMountPoint(): string {
	}

	public function getToken(): string {
	}

	public function getPassword(): ?string {
	}

	public function getId(): string {
	}

	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
	}

	public function getScanner(string $path = '', ?IStorage $storage = null): IScanner {
	}

	public function hasUpdated(string $path, int $time): bool {
	}

	public function test(): bool {
	}

	/**
	 * Check whether this storage is permanently or temporarily
	 * unavailable
	 *
	 * @throws StorageNotAvailableException
	 * @throws StorageInvalidException
	 */
	public function checkStorageAvailability(): void {
	}

	public function file_exists(string $path): bool {
	}

	public function getShareInfo(int $depth = -1) {
	}

	public function getOwner(string $path): string|false {
	}

	public function isSharable(string $path): bool {
	}

	public function getPermissions(string $path): int {
	}

	public function needsPartFile(): bool {
		return false;
	}

	public function free_space(string $path): int|float|false {
	}
}
