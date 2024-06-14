<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\MountManager;

use OCA\Circles\Db\GSSharesRequest;
use OCA\Circles\Model\GlobalScale\GSShareMountpoint;
use OCP\Share\Exceptions\ShareNotFound;

/**
 * Class CircleMountManager
 * @deprecated
 * @package OCA\Circles\MountManager
 */
class CircleMountManager {
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
