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

use OCA\Circles\Db\GSSharesRequest;
use OCA\Circles\Model\GlobalScale\GSShareMountpoint;
use OCP\Share\Exceptions\ShareNotFound;

/**
 * Class MountManager
 *
 * @package OCA\Circles\GlobalScale\GSMount
 */
class MountManager {
	/** @var string */
	private $userId;

	/** @var GSSharesRequest */
	private $gsSharesRequest;


	/**
	 * MountManager constructor.
	 *
	 * @param string $userId
	 * @param GSSharesRequest $gsSharesRequest
	 */
	public function __construct($userId, GSSharesRequest $gsSharesRequest) {
		$this->userId = $userId;
		$this->gsSharesRequest = $gsSharesRequest;
	}


	/**
	 * @param int $gsShareId
	 * @param string $target
	 *
	 * @return bool
	 */
	public function renameShare(int $gsShareId, string $target) {
		try {
			if ($target !== '-') {
				$target = $this->stripPath($target);
				$this->gsSharesRequest->getShareMountPointByPath($this->userId, $target);

				return false;
			}
		} catch (ShareNotFound $e) {
		}

		$mountPoint = new GSShareMountpoint($gsShareId, $this->userId, $target);
		try {
			$this->gsSharesRequest->getShareMountPointById($gsShareId, $this->userId);
			$this->gsSharesRequest->updateShareMountPoint($mountPoint);
		} catch (ShareNotFound $e) {
			$this->gsSharesRequest->generateShareMountPoint($mountPoint);
		}

		return true;
	}


	// TODO: implement !
	public function getMountManager() {
		return $this;
	}

	// TODO: implement !
	public function removeShare($mountPoint) {
	}

	// TODO: implement !
	public function removeMount($mountPoint) {
	}


	/**
	 * @param int $gsShareId
	 *
	 * @return bool
	 */
	public function unshare(int $gsShareId) {
		return $this->renameShare($gsShareId, '-');
	}


	/**
	 * remove '/user/files' from the path and trailing slashes
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function stripPath($path) {
		$prefix = '/' . $this->userId . '/files';

		return rtrim(substr($path, strlen($prefix)), '/');
	}
}
