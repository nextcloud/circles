<?php declare(strict_types=1);


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


use Exception;
use OC;
use OCA\Circles\Db\GSSharesRequest;
use OCA\Circles\Model\GlobalScale\GSShare;
use OCP\AppFramework\QueryException;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;


/**
 * Class MountProvider
 *
 * @package OCA\Circles\GlobalScale\GSMount
 */
class MountProvider implements IMountProvider {


	const STORAGE = '\OCA\Files_Sharing\External\Storage';


	/** @var MountManager */
	private $mountManager;

	/** @var ICloudIdManager */
	private $cloudIdManager;

	/** @var GSSharesRequest */
	private $gsSharesRequest;


	/**
	 * MountProvider constructor.
	 *
	 * @param MountManager $mountManager
	 * @param ICloudIdManager $cloudIdManager
	 * @param GSSharesRequest $gsSharesRequest
	 */
	public function __construct(
		MountManager $mountManager, ICloudIdManager $cloudIdManager, GSSharesRequest $gsSharesRequest
	) {
		$this->mountManager = $mountManager;
		$this->cloudIdManager = $cloudIdManager;
		$this->gsSharesRequest = $gsSharesRequest;
	}


	/**
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 *
	 * @return IMountPoint[]
	 * @throws QueryException
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		$shares = $this->gsSharesRequest->getForUser($user->getUID());

		$mounts = [];
		foreach ($shares as $share) {
			try {
				if ($share->getMountPoint() !== '-') {
					$mounts[] = $this->generateMount($share, $user->getUID(), $loader);
				}
			} catch (Exception $e) {
			}
		}

		return $mounts;
	}


	/**
	 * @param GSShare $share
	 * @param string $userId
	 * @param IStorageFactory $storageFactory
	 *
	 * @return Mount
	 * @throws Exception
	 */
	public function generateMount(
		GSShare $share, string $userId, IStorageFactory $storageFactory
	) {
		// might be better to test https first, then http. as this would only work from webclient !
		$data = $share->toMount($userId, $_SERVER['REQUEST_SCHEME']);
		$data['manager'] = $this->mountManager;
		$data['gsShareId'] = $share->getId();
		$data['cloudId'] = $this->cloudIdManager->getCloudId($data['owner'], $data['remote']);
		$data['certificateManager'] = OC::$server->getCertificateManager($userId);
		$data['HttpClientService'] = OC::$server->getHTTPClientService();

		return new Mount(
			self::STORAGE, $share->getMountPoint($userId), $data, $this->mountManager, $storageFactory
		);
	}

}

