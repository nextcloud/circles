<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

namespace OCA\Circles\Service;


use Exception;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\FederatedLinksRequest;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\FederatedCircleLinkFormatException;
use OCA\Circles\Exceptions\FederatedCircleStatusUpdateException;
use OCA\Circles\Exceptions\FederatedLinkCreationException;
use OCA\Circles\Exceptions\FederatedLinkDoesNotExistException;
use OCA\Circles\Exceptions\FederatedLinkUpdateException;
use OCA\Circles\Exceptions\FederatedRemoteIsDownException;
use OCA\Circles\Exceptions\MemberIsNotAdminException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IL10N;

class FederatedLinkService {

	const REMOTE_URL_LINK = '/index.php/apps/circles/v1/link';

	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesService */
	private $circlesService;

	/** @var BroadcastService */
	private $broadcastService;

	/** @var FederatedLinksRequest */
	private $federatedLinksRequest;

	/** @var EventsService */
	private $eventsService;

	/** @var IClientService */
	private $clientService;

	/** @var MiscService */
	private $miscService;


	/**
	 * FederatedLinkService constructor.
	 *
	 * @param string $UserId
	 * @param IL10N $l10n
	 * @param CirclesRequest $circlesRequest
	 * @param ConfigService $configService
	 * @param CirclesService $circlesService
	 * @param BroadcastService $broadcastService
	 * @param FederatedLinksRequest $federatedLinksRequest
	 * @param EventsService $eventsService
	 * @param IClientService $clientService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$UserId, IL10N $l10n, CirclesRequest $circlesRequest, ConfigService $configService,
		CirclesService $circlesService, BroadcastService $broadcastService,
		FederatedLinksRequest $federatedLinksRequest, EventsService $eventsService,
		IClientService $clientService, MiscService $miscService
	) {
		$this->userId = $UserId;
		$this->l10n = $l10n;
		$this->circlesRequest = $circlesRequest;
		$this->configService = $configService;
		$this->circlesService = $circlesService;
		$this->broadcastService = $broadcastService;
		$this->federatedLinksRequest = $federatedLinksRequest;
		$this->eventsService = $eventsService;

		$this->clientService = $clientService;
		$this->miscService = $miscService;
	}

//

	/**
	 * linkStatus()
	 *
	 * Update the status of a link.
	 * Function will check if user can edit the status, will update it and send the update to
	 * remote
	 *
	 * @param string $linkUniqueId
	 * @param int $status
	 *
	 * @throws Exception
	 * @throws FederatedCircleLinkFormatException
	 * @throws CircleTypeNotValidException
	 * @throws MemberIsNotAdminException
	 *
	 * @return FederatedLink[]
	 */
	public function linkStatus($linkUniqueId, $status) {

		try {
			$link = $this->federatedLinksRequest->getLinkFromId($linkUniqueId);
			$circle = $this->circlesRequest->getCircle($link->getCircleId(), $this->userId);
			$circle->hasToBeFederated();
			$circle->getHigherViewer()
				   ->hasToBeAdmin();
			$link->hasToBeValidStatusUpdate($status);

			if ($link->getStatus() !== $status) {
				$this->updateLinkStatus($link, $circle, $status);
			}

			return $this->federatedLinksRequest->getLinksFromCircle($circle->getUniqueId());

		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param FederatedLink $link
	 * @param Circle $circle
	 * @param int $status
	 *
	 * @return FederatedLink[]
	 * @throws Exception
	 */
	private function updateLinkStatus(FederatedLink $link, Circle $circle, $status) {

		$this->eventOnUpdateLinkStatus($link, $circle, $status);

		$link->setStatus($status);
		$link->setCircleId($circle->getUniqueId(true));

		try {
			$this->updateLinkRemote($link);
		} catch (Exception $e) {
			if ($status !== FederatedLink::STATUS_LINK_REMOVE) {
				throw $e;
			}
		}

		$this->federatedLinksRequest->update($link);

		return $this->federatedLinksRequest->getLinksFromCircle($circle->getUniqueId());
	}


	/**
	 * eventOnLinkStatus();
	 *
	 * Called by linkStatus() to manage events when status is changing.
	 * If status does not need update, returns false;
	 *
	 * @param FederatedLink $link
	 * @param Circle $circle
	 * @param int $status
	 *
	 * @return bool
	 */
	private function eventOnUpdateLinkStatus(FederatedLink $link, Circle $circle, $status) {

		if ($status === FederatedLink::STATUS_LINK_REMOVE) {
			$this->eventsService->onLinkRemove($circle, $link);
		}

		if ($status === FederatedLink::STATUS_LINK_UP) {
			$this->eventsService->onLinkRequestAccepting($circle, $link);
			$this->eventsService->onLinkUp($circle, $link);
		}

		return true;
	}


	/**
	 * @param string $remote
	 *
	 * @return string
	 */
	public function generateLinkRemoteURL($remote) {
		return $this->configService->generateRemoteHost($remote) . self::REMOTE_URL_LINK;
	}


	public function parseClientRequestResult(IResponse $response) {
		$result = json_decode($response->getBody(), true);
		if ($result === null) {
			throw new FederatedRemoteIsDownException(
				$this->l10n->t('The remote host is down or the Circles app is not installed on it')
			);
		}

		return $result;
	}


	/**
	 * @param string $token
	 * @param string $uniqueId
	 * @param int $status
	 *
	 * @return FederatedLink
	 * @throws Exception
	 */
	public function updateLinkFromRemote($token, $uniqueId, $status) {
		try {
			$link = $this->federatedLinksRequest->getLinkFromToken($token, $uniqueId);
			$circle = $this->circlesRequest->forceGetCircle($link->getCircleId());
			$circle->hasToBeFederated();

			$this->checkUpdateLinkFromRemote($status);
			$this->checkUpdateLinkFromRemoteLinkUp($circle, $link, $status);
			$this->checkUpdateLinkFromRemoteLinkRemove($circle, $link, $status);

			if ($link->getStatus() !== $status) {
				$this->federatedLinksRequest->update($link);
			}

			return $link;
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * checkUpdateLinkFromRemote();
	 *
	 * will throw exception is the status sent by remote is not correct
	 *
	 * @param int $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function checkUpdateLinkFromRemote($status) {
		$status = (int)$status;
		if ($status !== FederatedLink::STATUS_LINK_UP
			&& $status !== FederatedLink::STATUS_LINK_REMOVE
		) {
			throw new FederatedCircleStatusUpdateException(
				$this->l10n->t('Cannot proceed with this status update')
			);
		}
	}


	/**
	 * checkUpdateLinkFromRemoteLinkUp()
	 *
	 * in case of a request of status update from remote for a link up, we check the current
	 * status of the link locally.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 * @param int $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function checkUpdateLinkFromRemoteLinkUp(Circle $circle, FederatedLink $link, $status) {
		if ((int)$status !== FederatedLink::STATUS_LINK_UP) {
			return;
		}

		if ($link->getStatus() !== FederatedLink::STATUS_REQUEST_SENT) {
			throw new FederatedCircleStatusUpdateException(
				$this->l10n->t('Cannot proceed with this status update')
			);
		}

		$this->eventsService->onLinkRequestAccepted($circle, $link);
		$this->eventsService->onLinkUp($circle, $link);
		$link->setStatus($status);
	}


	/**
	 * updateLinkRemote()
	 *
	 * Send a request to the remote of the link to update its status.
	 *
	 * @param FederatedLink $link
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function updateLinkRemote(FederatedLink &$link) {

		try {
			$client = $this->clientService->newClient();
			$body = self::generateClientBodyData($link);
			$response = $client->post($this->generateLinkRemoteURL($link->getAddress()), $body);
			$result = $this->parseClientRequestResult($response);

			if ($result['status'] === -1) {
				throw new FederatedLinkUpdateException($result['reason']);
			}

			return true;
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * checkUpdateLinkFromRemoteLinkRemove();
	 *
	 * in case of a request of status update from remote for a link down, we check the current
	 * status of the link locally
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 * @param int $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function checkUpdateLinkFromRemoteLinkRemove(Circle $circle, FederatedLink $link, $status) {

		if ((int)$status !== FederatedLink::STATUS_LINK_REMOVE) {
			return;
		}

		$curStatus = $link->getStatus();
		$this->checkUpdateLinkFromRemoteLinkRequestSent($circle, $link);
		$this->checkUpdateLinkFromRemoteLinkRequested($circle, $link);
		$this->checkUpdateLinkFromRemoteLinkDown($circle, $link);

		if ($curStatus !== $link->getStatus()) {
			return;
		}

		throw new FederatedCircleStatusUpdateException(
			$this->l10n->t('Cannot proceed with this status update')
		);
	}


	/**
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	private function checkUpdateLinkFromRemoteLinkRequestSent(Circle $circle, FederatedLink &$link) {

		if ($link->getStatus() !== FederatedLink::STATUS_REQUEST_SENT) {
			return;
		}

		$link->setStatus(FederatedLink::STATUS_REQUEST_DECLINED);
		$this->eventsService->onLinkRequestRejected($circle, $link);
	}


	/**
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	private function checkUpdateLinkFromRemoteLinkRequested(Circle $circle, FederatedLink &$link) {

		if ($link->getStatus() !== FederatedLink::STATUS_LINK_REQUESTED) {
			return;
		}

		$link->setStatus(FederatedLink::STATUS_LINK_REMOVE);
		$this->eventsService->onLinkRequestCanceled($circle, $link);
	}


	/**
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	private function checkUpdateLinkFromRemoteLinkDown(Circle $circle, FederatedLink &$link) {

		if ($link->getStatus() < FederatedLink::STATUS_LINK_DOWN) {
			return;
		}

		$link->setStatus(FederatedLink::STATUS_LINK_DOWN);
		$this->eventsService->onLinkDown($circle, $link);
	}


	/**
	 * @param FederatedLink $link
	 * @param array $options
	 *
	 * @return array
	 */
	public static function generateClientBodyData(FederatedLink $link, $options = []) {
		$args = array_merge(
			$options, [
						'apiVersion' => Circles::version(),
						'token'      => $link->getToken(true),
						'uniqueId'   => $link->getCircleId(true),
						'linkTo'     => $link->getRemoteCircleName(),
						'address'    => $link->getLocalAddress(),
						'status'     => $link->getStatus()
					]
		);

		return [
			'body'            => ['data' => $args],
			'timeout'         => 5,
			'connect_timeout' => 5,
		];
	}


}