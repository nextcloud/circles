<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\External;

use Doctrine\DBAL\Driver\Exception;
use OC\Files\Filesystem;
use OCA\FederatedFileSharing\Events\FederatedShareAddedEvent;
use OCA\Files_Sharing\Helper;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Files;
use OCP\Files\Events\InvalidateMountCacheEvent;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Notification\IManager;
use OCP\OCS\IDiscoveryService;
use OCP\Share;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Files_SharingRemoteShare from ResponseDefinitions
 */
class Manager {
	public const STORAGE = '\OCA\Files_Sharing\External\Storage';

	public function __construct(private IDBConnection $connection, \OC\Files\Mount\Manager $mountManager, private IStorageFactory $storageLoader, private IClientService $clientService, private IManager $notificationManager, private IDiscoveryService $discoveryService, private ICloudFederationProviderManager $cloudFederationProviderManager, private ICloudFederationFactory $cloudFederationFactory, private IGroupManager $groupManager, private IUserManager $userManager, IUserSession $userSession, private IEventDispatcher $eventDispatcher, private LoggerInterface $logger)
 {
 }

	/**
	 * add new server-to-server share
	 *
	 * @param string $remote
	 * @param string $token
	 * @param string $password
	 * @param string $name
	 * @param string $owner
	 * @param int $shareType
	 * @param boolean $accepted
	 * @param string $user
	 * @param string $remoteId
	 * @param int $parent
	 * @return Mount|null
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function addShare($remote, $token, $password, $name, $owner, $shareType, $accepted = false, $user = null, $remoteId = '', $parent = -1)
 {
 }

	public function getShare(int $id, ?string $user = null): array|false
 {
 }

	/**
	 * Get share by token
	 *
	 * @param string $token
	 * @return array|false
	 */
	public function getShareByToken(string $token): array|false
 {
 }

	/**
	 * accept server-to-server share
	 *
	 * @param int $id
	 * @return bool True if the share could be accepted, false otherwise
	 */
	public function acceptShare(int $id, ?string $user = null)
 {
 }

	/**
	 * decline server-to-server share
	 *
	 * @param int $id
	 * @return bool True if the share could be declined, false otherwise
	 */
	public function declineShare(int $id, ?string $user = null)
 {
 }

	public function processNotification(int $remoteShare, ?string $user = null): void
 {
 }

	/**
	 * try send accept message to ocm end-point
	 *
	 * @param string $remoteDomain
	 * @param string $token
	 * @param string $remoteId id of the share
	 * @param string $feedback
	 * @return array|false
	 */
	protected function tryOCMEndPoint($remoteDomain, $token, $remoteId, $feedback)
 {
 }


	/**
	 * remove '/user/files' from the path and trailing slashes
	 *
	 * @param string $path
	 * @return string
	 */
	protected function stripPath($path)
 {
 }

	public function getMount($data, ?string $user = null)
 {
 }

	/**
	 * @param array $data
	 * @return Mount
	 */
	protected function mountShare($data, ?string $user = null)
 {
 }

	/**
	 * @return \OC\Files\Mount\Manager
	 */
	public function getMountManager()
 {
 }

	/**
	 * @param string $source
	 * @param string $target
	 * @return bool
	 */
	public function setMountPoint($source, $target)
 {
 }

	public function removeShare($mountPoint): bool
 {
 }

	/**
	 * remove re-shares from share table and mapping in the federated_reshares table
	 *
	 * @param $mountPointId
	 */
	protected function removeReShares($mountPointId)
 {
 }

	/**
	 * remove all shares for user $uid if the user was deleted
	 *
	 * @param string $uid
	 */
	public function removeUserShares($uid): bool
 {
 }

	public function removeGroupShares($gid): bool
 {
 }

	/**
	 * return a list of shares which are not yet accepted by the user
	 *
	 * @return list<Files_SharingRemoteShare> list of open server-to-server shares
	 */
	public function getOpenShares()
 {
 }

	/**
	 * return a list of shares which are accepted by the user
	 *
	 * @return list<Files_SharingRemoteShare> list of accepted server-to-server shares
	 */
	public function getAcceptedShares()
 {
 }
}
