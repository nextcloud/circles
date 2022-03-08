<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020
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


namespace OCA\Circles\GlobalScale\GSMount;

use OCA\Circles\Tools\Traits\TArrayTools;
use Exception;
use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;

/**
 * Class Mount
 *
 * @package OCA\Circles\GlobalScale\GSMount
 */
class Mount extends MountPoint implements MoveableMount {
	use TArrayTools;


	/** @var MountManager */
	protected $mountManager;

	/** @var int */
	private $gsShareId = -1;

	/**
	 * Mount constructor.
	 *
	 * @param $storage
	 * @param string $mountPoint
	 * @param array $options
	 * @param MountManager $manager
	 * @param null $loader
	 *
	 * @throws Exception
	 */
	public function __construct(
		$storage, string $mountPoint, array $options, MountManager $manager, $loader = null
	) {
		parent::__construct($storage, $mountPoint, $options, $loader);
		$this->gsShareId = $this->getInt('gsShareId', $options);
		$this->mountManager = $manager;
	}


	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 *
	 * @return bool
	 */
	public function moveMount($target) {
		$result = $this->mountManager->renameShare($this->gsShareId, $target);
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
		return $this->mountManager->unshare($this->gsShareId);
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
}
