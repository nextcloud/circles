<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Model;

use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Db\CoreQueryBuilder;
use OCA\Circles\Exceptions\FileCacheNotFoundException;

/**
 * Class FileCacheWrapper
 *
 * @package OCA\Circles\Model
 */
class FileCacheWrapper extends ManagedModel implements IQueryRow, IDeserializable, JsonSerializable {
	use TArrayTools;


	/** @var int */
	private $id = 0;

	/** @var string */
	private $path = '';

	/** @var int */
	private $permissions = 0;

	/** @var int */
	private $storageId = 0;

	/** @var string */
	private $pathHash = '';

	/** @var int */
	private $parent = 0;

	/** @var string */
	private $name = '';

	/** @var int */
	private $mimeType = 0;

	/** @var int */
	private $mimePart = 0;

	/** @var int */
	private $size = 0;

	/** @var int */
	private $mTime = 0;

	/** @var int */
	private $storageMTime = 0;

	/** @var bool */
	private $encrypted = false;

	/** @var int */
	private $unencryptedSize = 0;

	/** @var string */
	private $etag = '';

	/** @var string */
	private $checksum = '';

	/** @var string */
	private $storage = '';


	/**
	 * @param int $id
	 *
	 * @return FileCacheWrapper
	 */
	public function setId(int $id): self {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @param string $path
	 *
	 * @return FileCacheWrapper
	 */
	public function setPath(string $path): self {
		$this->path = $path;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @param string $pathHash
	 *
	 * @return FileCacheWrapper
	 */
	public function setPathHash(string $pathHash): self {
		$this->pathHash = $pathHash;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPathHash(): string {
		return $this->pathHash;
	}

	/**
	 * @param string $name
	 *
	 * @return FileCacheWrapper
	 */
	public function setName(string $name): self {
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @param int $storageMTime
	 *
	 * @return FileCacheWrapper
	 */
	public function setStorageMTime(int $storageMTime): self {
		$this->storageMTime = $storageMTime;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getStorageMTime(): int {
		return $this->storageMTime;
	}


	/**
	 * @param bool $encrypted
	 *
	 * @return FileCacheWrapper
	 */
	public function setEncrypted(bool $encrypted): self {
		$this->encrypted = $encrypted;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isEncrypted(): bool {
		return $this->encrypted;
	}


	/**
	 * @param int $unencryptedSize
	 *
	 * @return FileCacheWrapper
	 */
	public function setUnencryptedSize(int $unencryptedSize): self {
		$this->unencryptedSize = $unencryptedSize;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getUnencryptedSize(): int {
		return $this->unencryptedSize;
	}


	/**
	 * @param string $etag
	 *
	 * @return FileCacheWrapper
	 */
	public function setEtag(string $etag): self {
		$this->etag = $etag;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEtag(): string {
		return $this->etag;
	}


	/**
	 * @param string $checksum
	 *
	 * @return FileCacheWrapper
	 */
	public function setChecksum(string $checksum): self {
		$this->checksum = $checksum;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getChecksum(): string {
		return $this->checksum;
	}


	/**
	 * @param int $size
	 *
	 * @return FileCacheWrapper
	 */
	public function setSize(int $size): self {
		$this->size = $size;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getSize(): int {
		return $this->size;
	}


	/**
	 * @param int $mTime
	 *
	 * @return FileCacheWrapper
	 */
	public function setMTime(int $mTime): self {
		$this->mTime = $mTime;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getMTime(): int {
		return $this->mTime;
	}


	/**
	 * @param int $mimeType
	 *
	 * @return FileCacheWrapper
	 */
	public function setMimeType(int $mimeType): self {
		$this->mimeType = $mimeType;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getMimeType(): int {
		return $this->mimeType;
	}


	/**
	 * @param int $mimePart
	 *
	 * @return FileCacheWrapper
	 */
	public function setMimePart(int $mimePart): self {
		$this->mimePart = $mimePart;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getMimePart(): int {
		return $this->mimePart;
	}


	/**
	 * @param int $storageId
	 *
	 * @return FileCacheWrapper
	 */
	public function setStorageId(int $storageId): self {
		$this->storageId = $storageId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getStorageId(): int {
		return $this->storageId;
	}


	/**
	 * @param string $storage
	 *
	 * @return FileCacheWrapper
	 */
	public function setStorage(string $storage): self {
		$this->storage = $storage;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getStorage(): string {
		return $this->storage;
	}


	/**
	 * @param int $permissions
	 *
	 * @return FileCacheWrapper
	 */
	public function setPermissions(int $permissions): self {
		$this->permissions = $permissions;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPermissions(): int {
		return $this->permissions;
	}


	/**
	 * @param int $parent
	 *
	 * @return FileCacheWrapper
	 */
	public function setParent(int $parent): self {
		$this->parent = $parent;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getParent(): int {
		return $this->parent;
	}


	/**
	 * @return array
	 */
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
		if ($this->getId() === 0 || $this->getPath() === '') {
			return false;
		}

		return !(explode('/', $this->getPath(), 2)[0] !== 'files'
				 && explode(':', $this->getStorage(), 2)[0] === 'home');
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


	/**
	 * @return string[]
	 */
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
