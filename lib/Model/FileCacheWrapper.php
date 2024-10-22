<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use JsonSerializable;
use OCA\Circles\Db\CoreQueryBuilder;
use OCA\Circles\Exceptions\FileCacheNotFoundException;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;

class FileCacheWrapper extends ManagedModel implements IQueryRow, IDeserializable, JsonSerializable {
	use TArrayTools;

	private int $id = 0;
	private string $path = '';
	private int $permissions = 0;
	private int $storageId = 0;
	private string $pathHash = '';
	private int $parent = 0;
	private string $name = '';
	private int $mimeType = 0;
	private int $mimePart = 0;
	private int $size = 0;
	private int $mTime = 0;
	private int $storageMTime = 0;
	private bool $encrypted = false;
	private int $unencryptedSize = 0;
	private string $etag = '';
	private string $checksum = '';
	private string $storage = '';

	public function setId(int $id): self {
		$this->id = $id;
		return $this;
	}

	public function getId(): int {
		return $this->id;
	}

	public function setPath(string $path): self {
		$this->path = $path;
		return $this;
	}

	public function getPath(): string {
		return $this->path;
	}

	public function setPathHash(string $pathHash): self {
		$this->pathHash = $pathHash;
		return $this;
	}

	public function getPathHash(): string {
		return $this->pathHash;
	}

	public function setName(string $name): self {
		$this->name = $name;
		return $this;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setStorageMTime(int $storageMTime): self {
		$this->storageMTime = $storageMTime;
		return $this;
	}

	public function getStorageMTime(): int {
		return $this->storageMTime;
	}

	public function setEncrypted(bool $encrypted): self {
		$this->encrypted = $encrypted;
		return $this;
	}

	public function isEncrypted(): bool {
		return $this->encrypted;
	}

	public function setUnencryptedSize(int $unencryptedSize): self {
		$this->unencryptedSize = $unencryptedSize;
		return $this;
	}

	public function getUnencryptedSize(): int {
		return $this->unencryptedSize;
	}

	public function setEtag(string $etag): self {
		$this->etag = $etag;
		return $this;
	}

	public function getEtag(): string {
		return $this->etag;
	}

	public function setChecksum(string $checksum): self {
		$this->checksum = $checksum;
		return $this;
	}

	public function getChecksum(): string {
		return $this->checksum;
	}

	public function setSize(int $size): self {
		$this->size = $size;
		return $this;
	}

	public function getSize(): int {
		return $this->size;
	}

	public function setMTime(int $mTime): self {
		$this->mTime = $mTime;
		return $this;
	}

	public function getMTime(): int {
		return $this->mTime;
	}

	public function setMimeType(int $mimeType): self {
		$this->mimeType = $mimeType;
		return $this;
	}

	public function getMimeType(): int {
		return $this->mimeType;
	}

	public function setMimePart(int $mimePart): self {
		$this->mimePart = $mimePart;
		return $this;
	}

	public function getMimePart(): int {
		return $this->mimePart;
	}

	public function setStorageId(int $storageId): self {
		$this->storageId = $storageId;
		return $this;
	}

	public function getStorageId(): int {
		return $this->storageId;
	}

	public function setStorage(string $storage): self {
		$this->storage = $storage;
		return $this;
	}

	public function getStorage(): string {
		return $this->storage;
	}

	public function setPermissions(int $permissions): self {
		$this->permissions = $permissions;
		return $this;
	}

	public function getPermissions(): int {
		return $this->permissions;
	}

	public function setParent(int $parent): self {
		$this->parent = $parent;
		return $this;
	}

	public function getParent(): int {
		return $this->parent;
	}

	public function toCache(): array {
		return [
			'fileid' => $this->getId(),
			'path' => $this->getPath(),
			'permissions' => $this->getPermissions(),
			'storage' => $this->getStorageId(), // strange, it is not !?
			'path_hash' => $this->getPathHash(),
			'parent' => $this->getParent(),
			'name' => $this->getName(),
			'mimetype' => $this->getMimeType(),
			'mimepart' => $this->getMimePart(),
			'size' => $this->getSize(),
			'mtime' => $this->getMTime(),
			'storage_mtime' => $this->getStorageMTime(),
			'encrypted' => $this->isEncrypted(),
			'unencrypted_size' => $this->getUnencryptedSize(),
			'etag' => $this->getEtag(),
			'checksum' => $this->getChecksum()
		];
	}


	/**
	 * Returns whether the given database result can be interpreted as
	 * a share with accessible file (not trashed, not deleted)
	 *
	 * @return bool
	 */
	public function isAccessible(): bool {
		if ($this->getId() === 0) {
			return false;
		}

		$path = $this->getPath();
		[$storageType,] = explode('::', $this->getStorage(), 2);

		if ($path === '') {
			// we only accept empty path on external storage
			return (in_array($storageType, ['local', 'webdav', 'ftp', 'sftp', 'swift', 'smb', 'amazon']));
		}

		return !(explode('/', $path, 2)[0] !== 'files' && $storageType === 'home');
	}

	/**
	 * @param array $data
	 *
	 * @return self
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		if ($this->getInt('id', $data) === 0) {
			throw new InvalidItemException();
		}

		$this->setId($this->getInt('id', $data))
			->setPath($this->get('path', $data))
			->setPermissions($this->getInt('permissions', $data))
			->setStorage($this->get('storage', $data))
			->setStorageId($this->getInt('storageId', $data))
			->setPathHash($this->get('pathHash', $data))
			->setParent($this->getInt('parent', $data))
			->setName($this->get('name', $data))
			->setMimeType($this->getInt('mimeType', $data))
			->setMimePart($this->getInt('mimePart', $data))
			->setSize($this->getInt('size', $data))
			->setMTime($this->getInt('mTime', $data))
			->setStorageMTime($this->getInt('storageMTime', $data))
			->setEncrypted($this->getBool('encrypted', $data))
			->setUnencryptedSize($this->getInt('unencryptedSize', $data))
			->setEtag($this->get('etag', $data))
			->setChecksum($this->get('checksum', $data));

		return $this;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IQueryRow
	 * @throws FileCacheNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if ($this->getInt($prefix . 'fileid', $data) === 0) {
			throw new FileCacheNotFoundException();
		}

		$this->setId($this->getInt($prefix . 'fileid', $data));
		$this->setPath($this->get($prefix . 'path', $data));
		$this->setPermissions($this->getInt($prefix . 'permissions', $data));
		$this->setStorageId($this->getInt($prefix . 'storage', $data));
		$this->setPathHash($this->get($prefix . 'path_hash', $data));
		$this->setParent($this->getInt($prefix . 'parent', $data));
		$this->setName($this->get($prefix . 'name', $data));
		$this->setMimeType($this->getInt($prefix . 'mimetype', $data));
		$this->setMimePart($this->getInt($prefix . 'mimepart', $data));
		$this->setSize($this->getInt($prefix . 'size', $data));
		$this->setMTime($this->getInt($prefix . 'mtime', $data));
		$this->setStorageMTime($this->getInt($prefix . 'storage_mtime', $data));
		$this->setEncrypted($this->getBool($prefix . 'encrypted', $data));
		$this->setUnencryptedSize($this->getInt($prefix . 'unencrypted_size', $data));
		$this->setEtag($this->get($prefix . 'etag', $data));
		$this->setChecksum($this->get($prefix . 'checksum', $data));

		// small hack as there is no reason to call a recursive method for a single entry from the table
		$this->setStorage($this->get($prefix . CoreQueryBuilder::STORAGES . '_id', $data));

		return $this;
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'path' => $this->getPath(),
			'permissions' => $this->getPermissions(),
			'storage' => $this->getStorage(),
			'storageId' => $this->getStorageId(),
			'pathHash' => $this->getPathHash(),
			'parent' => $this->getParent(),
			'name' => $this->getName(),
			'mimeType' => $this->getMimeType(),
			'mimePart' => $this->getMimePart(),
			'size' => $this->getSize(),
			'mTime' => $this->getMTime(),
			'storageMTime' => $this->getStorageMTime(),
			'encrypted' => $this->isEncrypted(),
			'unencryptedSize' => $this->getUnencryptedSize(),
			'etag' => $this->getEtag(),
			'checksum' => $this->getChecksum()
		];
	}
}
