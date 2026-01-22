<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\External;

use OC\Files\Filesystem;
use OC\Files\SetupManager;
use OC\User\NoUserException;
use OCA\FederatedFileSharing\Events\FederatedShareAddedEvent;
use OCA\Files_Sharing\Helper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Files\Events\InvalidateMountCacheEvent;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\ICertificateManager;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager;
use OCP\OCS\IDiscoveryService;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class Manager {
	public function __construct(private IDBConnection $connection, private \OC\Files\Mount\Manager $mountManager, private IStorageFactory $storageLoader, private IClientService $clientService, private IManager $notificationManager, private IDiscoveryService $discoveryService, private ICloudFederationProviderManager $cloudFederationProviderManager, private ICloudFederationFactory $cloudFederationFactory, private IGroupManager $groupManager, IUserSession $userSession, private IEventDispatcher $eventDispatcher, private LoggerInterface $logger, private IRootFolder $rootFolder, private SetupManager $setupManager, private ICertificateManager $certificateManager, private ExternalShareMapper $externalShareMapper)
 {
 }

	/**
	 * Add new server-to-server share.
	 *
	 * @throws Exception
	 * @throws NotPermittedException
	 * @throws NoUserException
	 */
	public function addShare(ExternalShare $externalShare, IUser|IGroup|null $shareWith = null): ?Mount
 {
 }

	public function getShare(string $id, ?IUser $user = null): ExternalShare|false
 {
 }

	public function getShareByToken(string $token): ExternalShare|false
 {
 }

	/**
	 * Accept server-to-server share.
	 *
	 * @return bool True if the share could be accepted, false otherwise
	 */
	public function acceptShare(ExternalShare $externalShare, ?IUser $user = null): bool
 {
 }

	/**
	 * Decline server-to-server share
	 *
	 * @return bool True if the share could be declined, false otherwise
	 */
	public function declineShare(ExternalShare $externalShare, ?Iuser $user = null): bool
 {
 }

	public function processNotification(ExternalShare $remoteShare, ?IUser $user = null): void
 {
 }

	/**
	 * Try to send accept message to ocm end-point
	 *
	 * @param 'accept'|'decline' $feedback
	 * @return array|false
	 */
	protected function tryOCMEndPoint(ExternalShare $externalShare, string $feedback)
 {
 }

	/**
	 * remove '/user/files' from the path and trailing slashes
	 */
	protected function stripPath(string $path): string
 {
 }

	public function getMount(array $data, ?IUser $user = null): Mount
 {
 }

	protected function mountShare(array $data, ?IUser $user = null): Mount
 {
 }

	public function getMountManager(): \OC\Files\Mount\Manager
 {
 }

	public function setMountPoint(string $source, string $target): bool
 {
 }

	public function removeShare(string $mountPoint): bool
 {
 }

	/**
	 * Remove re-shares from share table and mapping in the federated_reshares table
	 */
	protected function removeReShares(string $mountPointId): void
 {
 }

	/**
	 * Remove all shares for user $uid if the user was deleted.
	 */
	public function removeUserShares(IUser $user): bool
 {
 }

	public function removeGroupShares(IGroup $group): bool
 {
 }

	/**
	 * Return a list of shares which are not yet accepted by the user.
	 *
	 * @return list<ExternalShare> list of open server-to-server shares
	 */
	public function getOpenShares(): array
 {
 }

	/**
	 * Return a list of shares which are accepted by the user.
	 *
	 * @return list<ExternalShare> list of accepted server-to-server shares
	 */
	public function getAcceptedShares(): array
 {
 }
}
