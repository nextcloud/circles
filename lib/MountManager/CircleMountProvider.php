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


namespace OCA\Circles\MountManager;


use daita\MySmallPhpTools\Traits\TArrayTools;
use Exception;
use OC;
use OC\Http\Client\ClientService;
use OCA\Circles\Db\MountRequest;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MountPointConstructionException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\GlobalScale\GSShare;
use OCA\Circles\Model\GlobalScale\GSShareMountpoint;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Mount;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Files_Sharing\External\Storage as ExternalStorage;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;


/**
 * Class CircleMountProvider
 *
 * @package OCA\Circles\MountManager
 */
class CircleMountProvider implements IMountProvider {


	use TArrayTools;


//	const LOCAL_STORAGE = ::class;
	const EXTERNAL_STORAGE = ExternalStorage::class;


	/** @var ClientService */
	private $clientService;

	/** @var CircleMountManager */
	private $circleMountManager;

	/** @var ICloudIdManager */
	private $cloudIdManager;

	/** @var MountRequest */
	private $mountRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MountProvider constructor.
	 *
	 * @param ClientService $clientService
	 * @param CircleMountManager $circleMountManager
	 * @param ICloudIdManager $cloudIdManager
	 * @param MountRequest $mountRequest
	 * @param FederatedUserService $federatedUserService
	 * @param ConfigService $configService
	 */
	public function __construct(
		ClientService $clientService,
		CircleMountManager $circleMountManager,
		ICloudIdManager $cloudIdManager,
		MountRequest $mountRequest,
		FederatedUserService $federatedUserService,
		ConfigService $configService
	) {
		$this->clientService = $clientService;
		$this->circleMountManager = $circleMountManager;
		$this->cloudIdManager = $cloudIdManager;
		$this->mountRequest = $mountRequest;
		$this->federatedUserService = $federatedUserService;
		$this->configService = $configService;
	}


	/**
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 *
	 * @return IMountPoint[]
	 * @throws RequestBuilderException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($user->getUID());
		$items = $this->mountRequest->getForUser($federatedUser);

		$mounts = [];
//		$mounts[$mount->getMountPoint()] = $mount;

		foreach ($items as $item) {
			try {
//				if ($share->getMountPoint() !== '-') {
//					$this->fixDuplicateFile($user->getUID(), $share);
				$mounts[] = $this->generateCircleMount($item, $loader);
//				}
			} catch (Exception $e) {
			}
		}
		\OC::$server->getLogger()->log(3, '## ' . json_encode($mounts));

//		$mounts[] = $this->generateMount2();

		return $mounts;
	}


	/**
	 * @param Mount $mount
	 * @param IStorageFactory $storageFactory
	 *
	 * @return CircleMount
	 * @throws InitiatorNotFoundException
	 * @throws MountPointConstructionException
	 */
	public function generateCircleMount(Mount $mount, IStorageFactory $storageFactory): CircleMount {
//		$protocol = 'https';
//		if ($this->configService->isLocalNonSSL()) {
//			$protocol = 'http';
//		}

		$initiator = $mount->getInitiator();
		// TODO: right now, limited to Local Nextcloud User
		if ($initiator->getUserType() !== Member::TYPE_USER
			|| !$this->configService->isLocalInstance($initiator->getInstance())) {
			throw new InitiatorNotFoundException();
		}

		$data = $mount->toMount();
		$data['manager'] = $this->circleMountManager;
		$data['gsShareId'] = $mount->getId();
		$data['cloudId'] = $this->cloudIdManager->getCloudId($data['owner'], $data['remote']);
		$data['certificateManager'] = OC::$server->getCertificateManager($initiator->getUserId());
		$data['HttpClientService'] = $this->clientService;

		return new CircleMount(
			$mount,
			self::EXTERNAL_STORAGE,
			$data,
			$this->circleMountManager,
			$storageFactory
		);
	}


	/**
	 * @param GSShare $share
	 * @param string $userId
	 * @param IStorageFactory $storageFactory
	 *
	 * @return CircleMount
	 * @throws Exception
	 */
	public function generateMount(
		GSShare $share, string $userId, IStorageFactory $storageFactory
	) {
		$protocol = 'https';
		if ($this->configService->isLocalNonSSL()) {
			$protocol = 'http';
		}

		$data = $share->toMount($userId, $protocol);
		$data['manager'] = $this->circleMountManager;
		$data['gsShareId'] = $share->getId();
		$data['cloudId'] = $this->cloudIdManager->getCloudId($data['owner'], $data['remote']);
		$data['certificateManager'] = OC::$server->getCertificateManager($userId);
		$data['HttpClientService'] = OC::$server->getHTTPClientService();

		return new CircleMount(
			self::EXTERNAL_STORAGE,
			$share->getMountPoint($userId),
			$data,
			$this->circleMountManager,
			$storageFactory
		);
	}


	/**
	 * @param string $userId
	 * @param GSShare $share
	 *
	 * @throws OC\User\NoUserException
	 * @throws NotPermittedException
	 */
	private function fixDuplicateFile(string $userId, GSShare $share) {
		$fs = \OC::$server->getRootFolder()
						  ->getUserFolder($userId);

		try {
			$fs->get($share->getMountPoint());
		} catch (NotFoundException $e) {
			return;
		}

		$info = pathinfo($share->getMountPoint());
		$filename = $this->get('dirname', $info) . '/' . $this->get('filename', $info);
		$extension = $this->get('extension', $info);
		$extension = ($extension === '') ? '' : '.' . $extension;

		$n = 2;
		while (true) {
			$path = $filename . " ($n)" . $extension;
			try {
				$fs->get($path);
			} catch (NotFoundException $e) {
				$share->setMountPoint($path);
				$mountPoint = new GSShareMountpoint($share->getId(), $userId, $path);
				$this->gsSharesRequest->updateShareMountPoint($mountPoint);

				return;
			}

			$n++;
		}
	}

}

