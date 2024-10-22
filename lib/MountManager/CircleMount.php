<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\MountManager;

use Exception;
use JsonSerializable;
use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;
use OCA\Circles\Exceptions\MountPointConstructionException;
use OCA\Circles\Model\Mount;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\Files\Storage\IStorageFactory;

/**
 * Class CircleMount
 *
 * @package OCA\Circles\MountManager
 */
class CircleMount extends MountPoint implements MoveableMount, JsonSerializable {
	use TArrayTools;


	/** @var Mount */
	private $mount;

	/** @var string */
	private $storageClass;


	/**
	 * CircleMount constructor.
	 *
	 * @param Mount $mount
	 * @param string $storage
	 * @param IStorageFactory|null $loader
	 *
	 * @throws MountPointConstructionException
	 */
	public function __construct(
		Mount $mount,
		string $storage,
		?IStorageFactory $loader = null,
	) {
		try {
			parent::__construct(
				$storage,
				$mount->getMountPoint(false),
				$mount->toMount(),
				$loader
			);
		} catch (Exception $e) {
			throw new MountPointConstructionException();
		}

		$this->mount = $mount;
		$this->storageClass = $storage;
	}


	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 *
	 * @return bool
	 */
	public function moveMount($target) {
		$result = $this->mount->getMountManager()->renameShare($this->gsShareId, $target);
		$this->setMountPoint($target);

		return $result;
	}

	/**
	 * Remove the mount points
	 *
	 * @return mixed
	 * @return bool
	 */
	public function removeMount() {
		return $this->mount->getMountManager()->unshare($this->gsShareId);
	}


	/**
	 * Get the type of mount point, used to distinguish things like shares and external storages
	 * in the web interface
	 *
	 * @return string
	 */
	public function getMountType() {
		return 'shared';
	}

	public function getInitiator() {
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'mount' => $this->mount,
			'storage' => $this->storageClass
		];
	}
}
