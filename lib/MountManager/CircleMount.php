<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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


namespace OCA\Circles\MountManager;

use OCA\Circles\Tools\Traits\TArrayTools;
use Exception;
use JsonSerializable;
use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;
use OCA\Circles\Exceptions\MountPointConstructionException;
use OCA\Circles\Model\Mount;
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
		?IStorageFactory $loader = null
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
