<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Mount;

use OC\Files\Filesystem;
use OC\Files\SetupManager;
use OC\Files\SetupManagerFactory;
use OCP\Cache\CappedMemoryCache;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;

class Manager implements IMountManager {
	public function __construct(SetupManagerFactory $setupManagerFactory)
 {
 }

	/**
	 * @param IMountPoint $mount
	 */
	public function addMount(IMountPoint $mount)
 {
 }

	/**
	 * @param string $mountPoint
	 */
	public function removeMount(string $mountPoint)
 {
 }

	/**
	 * @param string $mountPoint
	 * @param string $target
	 */
	public function moveMount(string $mountPoint, string $target)
 {
 }

	/**
	 * Find the mount for $path
	 *
	 * @param string $path
	 * @return IMountPoint
	 */
	public function find(string $path): IMountPoint
 {
 }

	/**
	 * Find all mounts in $path
	 *
	 * @param string $path
	 * @return IMountPoint[]
	 */
	public function findIn(string $path): array
 {
 }

	public function clear()
 {
 }

	/**
	 * Find mounts by storage id
	 *
	 * @param string $id
	 * @return IMountPoint[]
	 */
	public function findByStorageId(string $id): array
 {
 }

	/**
	 * @return IMountPoint[]
	 */
	public function getAll(): array
 {
 }

	/**
	 * Find mounts by numeric storage id
	 *
	 * @param int $id
	 * @return IMountPoint[]
	 */
	public function findByNumericId(int $id): array
 {
 }

	public function getSetupManager(): SetupManager
 {
 }

	/**
	 * Return all mounts in a path from a specific mount provider
	 *
	 * @param string $path
	 * @param string[] $mountProviders
	 * @return MountPoint[]
	 */
	public function getMountsByMountProvider(string $path, array $mountProviders)
 {
 }

	/**
	 * Return the mount matching a cached mount info (or mount file info)
	 *
	 * @param ICachedMountInfo $info
	 *
	 * @return IMountPoint|null
	 */
	public function getMountFromMountInfo(ICachedMountInfo $info): ?IMountPoint
 {
 }
}
