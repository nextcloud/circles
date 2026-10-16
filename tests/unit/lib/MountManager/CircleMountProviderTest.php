<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\MountManager;

use OC\Files\Storage\StorageFactory;
use OCA\Circles\Db\MountPointRequest;
use OCA\Circles\Db\MountRequest;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Mount;
use OCA\Circles\MountManager\CircleMountProvider;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Files_Sharing\External\Manager as ExternalShareManager;
use OCA\Files_Sharing\External\Storage as ExternalStorage;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\MountProviderArgs;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\Http\Client\IClientService;
use OCP\IUser;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CircleMountProviderTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private MountRequest&MockObject $mountRequest;
	private FederatedUserService&MockObject $federatedUserService;
	private IUser&MockObject $user;
	private CircleMountProvider $provider;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->mountRequest = $this->createMock(MountRequest::class);
		$this->federatedUserService = $this->createMock(FederatedUserService::class);
		$configService = $this->createMock(ConfigService::class);
		$configService->method('isLocalInstance')
			->willReturn(true);
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')
			->willReturn('alice');

		$this->provider = new CircleMountProvider(
			$this->createMock(IClientService::class),
			$this->rootFolder,
			Server::get(ICloudIdManager::class),
			$this->mountRequest,
			$this->createMock(MountPointRequest::class),
			$this->federatedUserService,
			$configService,
			$this->createMock(LoggerInterface::class),
			$this->createMock(ExternalShareManager::class),
		);
	}

	private function createTeamMount(): Mount {
		$localUser = new FederatedUser();
		$localUser->setUserId('alice')
			->setUserType(Member::TYPE_USER)
			->setInstance('');
		$initiator = new Member();
		$initiator->setUserId('alice')
			->setInheritedBy($localUser);

		$remoteInstance = new RemoteInstance();
		$remoteInstance->setRoot('https://remote.example');
		$owner = new Member();
		$owner->setUserId('bob')
			->setRemoteInstance($remoteInstance);

		$mount = new Mount();
		$mount->setMountId('mount-id')
			->setOriginalMountPoint('/team-share')
			->setToken('token')
			->setOwner($owner)
			->setInitiator($initiator);

		return $mount;
	}

	private function mockFreeMountPoint(): void {
		$cache = $this->createMock(ICache::class);
		$cache->method('inCache')
			->willReturn(false);
		$storage = $this->createMock(IStorage::class);
		$storage->method('getCache')
			->willReturn($cache);
		$homeMount = $this->createMock(IMountPoint::class);
		$homeMount->method('getStorage')
			->willReturn($storage);
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getMountPoint')
			->willReturn($homeMount);
		$this->rootFolder->method('getUserFolder')
			->with('alice')
			->willReturn($userFolder);

		$federatedUser = new FederatedUser();
		$federatedUser->setSingleId('alice-single-id');
		$this->federatedUserService->method('getLocalFederatedUser')
			->with('alice')
			->willReturn($federatedUser);
	}

	public function testGenerateCircleMountCreatesExternalStorageForUser(): void {
		$discoveryService = $this->createMock(IOCMDiscoveryService::class);
		$discoveryService->method('discover')
			->willThrowException(new OCMProviderException());
		$this->overwriteService(IOCMDiscoveryService::class, $discoveryService);
		$mount = $this->createTeamMount();

		$circleMount = $this->provider->generateCircleMount($mount, new StorageFactory(), $this->user);

		$this->assertSame($this->user, $mount->toMount()['recipient']);
		$this->assertTrue($circleMount->getStorage()->instanceOfStorage(ExternalStorage::class));
	}

	public function testGetMountsForUserPassesUserAsRecipient(): void {
		$this->mockFreeMountPoint();
		$mount = $this->createTeamMount();
		$this->mountRequest->method('getForUser')
			->willReturn([$mount]);

		$mounts = $this->provider->getMountsForUser($this->user, new StorageFactory());

		$this->assertCount(1, $mounts);
		$this->assertSame($this->user, $mount->toMount()['recipient']);
	}

	public function testGetMountsForPathPassesUserAsRecipient(): void {
		$this->mockFreeMountPoint();
		$mount = $this->createTeamMount();
		$this->mountRequest->method('getForUser')
			->willReturn([$mount]);
		$mountInfo = $this->createMock(ICachedMountInfo::class);
		$mountInfo->method('getUser')
			->willReturn($this->user);
		$mountInfo->method('getMountPoint')
			->willReturn('/alice/files/team-share/');

		$mounts = $this->provider->getMountsForPath(
			'/alice/files/team-share',
			false,
			[new MountProviderArgs($mountInfo, $this->createMock(ICacheEntry::class))],
			new StorageFactory(),
		);

		$this->assertCount(1, $mounts);
		$this->assertSame($this->user, $mount->toMount()['recipient']);
	}
}
