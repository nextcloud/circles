<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing;

use OCA\FederatedFileSharing\Events\FederatedShareAddedEvent;
use OCP\AppFramework\Http;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\HintException;
use OCP\Http\Client\IClientService;
use OCP\OCS\IDiscoveryService;
use Psr\Log\LoggerInterface;

class Notifications {
	public const RESPONSE_FORMAT = 'json'; // default response format for ocs calls

	public function __construct(
		private AddressHandler $addressHandler,
		private IClientService $httpClientService,
		private IDiscoveryService $discoveryService,
		private IJobList $jobList,
		private ICloudFederationProviderManager $federationProviderManager,
		private ICloudFederationFactory $cloudFederationFactory,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * send server-to-server share to remote server
	 *
	 * @param string $token
	 * @param string $shareWith
	 * @param string $name
	 * @param string $remoteId
	 * @param string $owner
	 * @param string $ownerFederatedId
	 * @param string $sharedBy
	 * @param string $sharedByFederatedId
	 * @param int $shareType (can be a remote user or group share)
	 * @return bool
	 * @throws HintException
	 * @throws \OC\ServerNotAvailableException
	 */
	public function sendRemoteShare($token, $shareWith, $name, $remoteId, $owner, $ownerFederatedId, $sharedBy, $sharedByFederatedId, $shareType)
 {
 }

	/**
	 * ask owner to re-share the file with the given user
	 *
	 * @param string $token
	 * @param string $id remote Id
	 * @param string $shareId internal share Id
	 * @param string $remote remote address of the owner
	 * @param string $shareWith
	 * @param int $permission
	 * @param string $filename
	 * @return array|false
	 * @throws HintException
	 * @throws \OC\ServerNotAvailableException
	 */
	public function requestReShare($token, $id, $shareId, $remote, $shareWith, $permission, $filename, $shareType)
 {
 }

	/**
	 * send server-to-server unshare to remote server
	 *
	 * @param string $remote url
	 * @param string $id share id
	 * @param string $token
	 * @return bool
	 */
	public function sendRemoteUnShare($remote, $id, $token)
 {
 }

	/**
	 * send server-to-server unshare to remote server
	 *
	 * @param string $remote url
	 * @param string $id share id
	 * @param string $token
	 * @return bool
	 */
	public function sendRevokeShare($remote, $id, $token)
 {
 }

	/**
	 * send notification to remote server if the permissions was changed
	 *
	 * @param string $remote
	 * @param string $remoteId
	 * @param string $token
	 * @param int $permissions
	 * @return bool
	 */
	public function sendPermissionChange($remote, $remoteId, $token, $permissions)
 {
 }

	/**
	 * forward accept reShare to remote server
	 *
	 * @param string $remote
	 * @param string $remoteId
	 * @param string $token
	 */
	public function sendAcceptShare($remote, $remoteId, $token)
 {
 }

	/**
	 * forward decline reShare to remote server
	 *
	 * @param string $remote
	 * @param string $remoteId
	 * @param string $token
	 */
	public function sendDeclineShare($remote, $remoteId, $token)
 {
 }

	/**
	 * inform remote server whether server-to-server share was accepted/declined
	 *
	 * @param string $remote
	 * @param string $token
	 * @param string $remoteId Share id on the remote host
	 * @param string $action possible actions: accept, decline, unshare, revoke, permissions
	 * @param array $data
	 * @param int $try
	 * @return boolean
	 */
	public function sendUpdateToRemote($remote, $remoteId, $token, $action, $data = [], $try = 0)
 {
 }


	/**
	 * return current timestamp
	 *
	 * @return int
	 */
	protected function getTimestamp()
 {
 }

	/**
	 * try http post with the given protocol, if no protocol is given we pick
	 * the secure one (https)
	 *
	 * @param string $remoteDomain
	 * @param string $urlSuffix
	 * @param array $fields post parameters
	 * @param string $action define the action (possible values: share, reshare, accept, decline, unshare, revoke, permissions)
	 * @return array
	 * @throws \Exception
	 */
	protected function tryHttpPostToShareEndpoint($remoteDomain, $urlSuffix, array $fields, $action = 'share')
 {
 }

	/**
	 * try old federated sharing API if the OCM api doesn't work
	 *
	 * @param $remoteDomain
	 * @param $urlSuffix
	 * @param array $fields
	 * @return mixed
	 * @throws \Exception
	 */
	protected function tryLegacyEndPoint($remoteDomain, $urlSuffix, array $fields)
 {
 }

	/**
	 * send action regarding federated sharing to the remote server using the OCM API
	 *
	 * @param $remoteDomain
	 * @param $fields
	 * @param $action
	 *
	 * @return array|false
	 */
	protected function tryOCMEndPoint($remoteDomain, $fields, $action)
 {
 }
}
