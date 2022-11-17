<?php

namespace OCA\Circles\Mount;

use OC\Files\Cache\Scanner;
use OC\Files\ObjectStore\NoopScanner;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\IUser;

class CirclesFolderStorage extends Wrapper implements IDisableEncryptionStorage {
	private string $circleId;
	private ICacheEntry $rootEntry;
	private ?IUser $mountOwner;
	/** @var RootEntryCache */
	public $cache;

	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->rootEntry = $parameters['rootCacheEntry'];
		$this->mountOwner = $parameters['mountOwner'];
	}

	/**
	 * @param string $path
	 *
	 * @return false|string
	 */
	public function getOwner($path) {
		return $this->mountOwner !== null ? $this->mountOwner->getUID() : false;
	}

	/**
	 * @param string $path
	 * @param null   $storage
	 *
	 * @return RootEntryCache
	 */
	public function getCache($path = '', $storage = null): RootEntryCache {
		if ($this->cache) {
			return $this->cache;
		}
		if (!$storage) {
			$storage = $this;
		}

		$this->cache = new RootEntryCache(parent::getCache($path, $storage), $this->rootEntry);
		return $this->cache;
	}

	/**
	 * @param string $path
	 * @param null   $storage
	 *
	 * @return Scanner
	 */
	public function getScanner($path = '', $storage = null): Scanner {
		if (!$storage) {
			$storage = $this;
		}
		if ($storage->instanceOfStorage(ObjectStoreStorage::class)) {
			$storage->scanner = new NoopScanner($storage);
		} elseif (!isset($storage->scanner)) {
			$storage->scanner = new Scanner($storage);
		}
		return $storage->scanner;
	}
}
