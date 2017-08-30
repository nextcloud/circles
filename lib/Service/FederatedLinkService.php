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
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
use OCA\Circles\Exceptions\FederatedCircleStatusUpdateException;
use OCA\Circles\Exceptions\FederatedLinkCreationException;
use OCA\Circles\Exceptions\FederatedLinkDoesNotExistException;
use OCA\Circles\Exceptions\FederatedLinkUpdateException;
use OCA\Circles\Exceptions\FederatedRemoteCircleDoesNotExistException;
use OCA\Circles\Exceptions\FederatedRemoteDoesNotAllowException;
use OCA\Circles\Exceptions\FederatedRemoteIsDownException;
use OCA\Circles\Exceptions\MemberIsNotAdminException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IL10N;

class FederatedLinkService {

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


	/**
	 * linkCircle();
	 *
	 * link to a circle.
	 * Function will check if settings allow Federated links between circles, and the format of
	 * the link ($remote). If no exception, a request to the remote circle will be initiated
	 * using requestLinkWithCircle()
	 *
	 * $remote format: <circle_name>@<remote_host>
	 *
	 * @param string $circleUniqueId
	 * @param string $remote
	 *
	 * @throws Exception
	 * @throws FederatedCircleLinkFormatException
	 * @throws CircleTypeNotValidException
	 *
	 * @return FederatedLink
	 */
	public function linkCircle($circleUniqueId, $remote) {

		if (!$this->configService->isFederatedCirclesAllowed()) {
			throw new FederatedCircleNotAllowedException(
				$this->l10n->t("Federated circles are not allowed on this Nextcloud")
			);
		}

		if (strpos($remote, '@') === false) {
			throw new FederatedCircleLinkFormatException(
				$this->l10n->t("Federated link does not have a valid format")
			);
		}

		try {
			return $this->requestLinkWithCircle($circleUniqueId, $remote);
		} catch (Exception $e) {
			throw $e;
		}
	}


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

		$status = (int)$status;
		$link = null;
		try {

			$link = $this->federatedLinksRequest->getLinkFromId($linkUniqueId);
			$circle = $this->circlesRequest->getCircle($link->getCircleId(), $this->userId);
			$circle->hasToBeFederated();
			$circle->getHigherViewer()
				   ->hasToBeAdmin();
			$link->hasToBeValidStatusUpdate($status);

			if (!$this->eventOnLinkStatus($circle, $link, $status)) {
				return $this->federatedLinksRequest->getLinksFromCircle($circle->getUniqueId());
			}

		} catch (Exception $e) {
			throw $e;
		}

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
	 * @param Circle $circle
	 * @param FederatedLink $link
	 * @param $status
	 *
	 * @return bool
	 */
	private function eventOnLinkStatus(Circle $circle, FederatedLink $link, $status) {
		if ($link->getStatus() === $status) {
			return false;
		}

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
	 * requestLinkWithCircle()
	 *
	 * Using CircleId, function will get more infos from the database.
	 * Will check if author is at least admin and initiate a FederatedLink, save it
	 * in the database and send a request to the remote circle using requestLink()
	 * If any issue, entry is removed from the database.
	 *
	 * @param string $circleUniqueId
	 * @param string $remote
	 *
	 * @return FederatedLink
	 * @throws Exception
	 */
	private function requestLinkWithCircle($circleUniqueId, $remote) {

		$link = null;
		try {
			$circle = $this->circlesService->detailsCircle($circleUniqueId);
			$circle->getHigherViewer()
				   ->hasToBeAdmin();
			$circle->hasToBeFederated();
			$circle->cantBePersonal();

			$link = $this->generateNewLinkWithRemoteCircle($circle->getUniqueId(), $remote);
			$this->requestLink($circle, $link);
		} catch (Exception $e) {
			$this->federatedLinksRequest->delete($link);
			throw $e;
		}

		return $link;
	}


	/**
	 * @param $circleUniqueId
	 * @param $remote
	 *
	 * @return FederatedLink
	 */
	private function generateNewLinkWithRemoteCircle($circleUniqueId, $remote) {

		$link = new FederatedLink();
		list($remoteCircle, $remoteAddress) = explode('@', $remote, 2);

		$link->setCircleId($circleUniqueId)
			 ->setLocalAddress($this->configService->getLocalAddress())
			 ->setAddress($remoteAddress)
			 ->setRemoteCircleName($remoteCircle)
			 ->setStatus(FederatedLink::STATUS_LINK_SETUP)
			 ->generateToken();

		$this->federatedLinksRequest->create($link);

		return $link;
	}


	/**
	 * @param string $remote
	 *
	 * @return string
	 */
	private function generateLinkRemoteURL($remote) {
		return $this->configService->generateRemoteHost($remote) . Application::REMOTE_URL_LINK;
	}


	/**
	 * requestLink()
	 *
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 *
	 * @return boolean
	 * @throws Exception
	 */
	private function requestLink(Circle $circle, FederatedLink &$link) {
		$args = array_merge(self::generateLinkData($link), ['sourceName' => $circle->getName()]);

		try {
			$client = $this->clientService->newClient();
			$body = self::generateClientBodyData($args);
			$response = $client->put($this->generateLinkRemoteURL($link->getAddress()), $body);
			$result = $this->parseRequestLinkResult($response);

			$reason = ((key_exists('reason', $result)) ? $result['reason'] : '');
			$this->eventOnRequestLink($circle, $link, $result['status'], $reason);

			$link->setUniqueId($result['uniqueId']);
			$this->federatedLinksRequest->update($link);

			return true;
		} catch (Exception $e) {
			throw $e;
		}
	}


	private function parseRequestLinkResult(IResponse $response) {
		$result = json_decode($response->getBody(), true);
		if ($result === null) {
			throw new FederatedRemoteIsDownException(
				$this->l10n->t('The remote host is down or the Circles app is not installed on it')
			);
		}

		return $result;
	}


	/**
	 * eventOnRequestLink();
	 *
	 * Called by requestLink() will update status and event
	 * Will also manage errors returned by the remote link
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 * @param int $status
	 * @param string $reason
	 *
	 * @throws Exception
	 */
	private function eventOnRequestLink(Circle $circle, FederatedLink &$link, $status, $reason) {

		switch ($status) {
			case FederatedLink::STATUS_LINK_UP:
				$link->setStatus(FederatedLink::STATUS_LINK_UP);
				$this->eventsService->onLinkUp($circle, $link);
				break;

			case  FederatedLink::STATUS_LINK_REQUESTED:
				$link->setStatus(FederatedLink::STATUS_REQUEST_SENT);
				$this->eventsService->onLinkRequestSent($circle, $link);
				break;

			default:
				$this->parseRequestLinkError($reason);
		}
	}


	/**
	 * parseRequestLinkError();
	 *
	 * Will parse the error reason returned by requestLink() and throw an Exception
	 *
	 * @param $reason
	 *
	 * @throws Exception
	 * @throws FederatedRemoteCircleDoesNotExistException
	 * @throws FederatedRemoteDoesNotAllowException
	 */
	private function parseRequestLinkError($reason) {

		$convert = [
			'federated_not_allowed' => $this->l10n->t(
				'Federated circles are not allowed on the remote Nextcloud'
			),
			'circle_links_disable'  => $this->l10n->t('Remote circle does not accept federated links'),
			'duplicate_unique_id'   => $this->l10n->t('Trying to link a circle to itself'),
			'duplicate_link'        => $this->l10n->t('This link exists already'),
			'circle_does_not_exist' => $this->l10n->t('The requested remote circle does not exist')
		];

		if (key_exists($reason, $convert)) {
			throw new FederatedRemoteDoesNotAllowException($convert[$reason]);
		}
		throw new Exception($reason);
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
	public function updateLinkRemote(FederatedLink &$link) {

		try {
			$client = $this->clientService->newClient();
			$body = self::generateClientBodyData(self::generateLinkData($link));
			$response = $client->post($this->generateLinkRemoteURL($link->getAddress()), $body);
			$result = parseRequestLinkResult($response);

			if ($result['status'] === -1) {
				throw new FederatedLinkUpdateException($result['reason']);
			}

			return true;
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * Create a new link into database and assign the correct status.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 *
	 * @throws Exception
	 */
	public function initiateLink(Circle $circle, FederatedLink &$link) {

		try {
			$this->checkLinkRequestValidity($circle, $link);
			$link->setCircleId($circle->getUniqueId());

			if ($circle->getSetting('allow_links_auto') === 'true') {
				$link->setStatus(FederatedLink::STATUS_LINK_UP);
				$this->eventsService->onLinkUp($circle, $link);
			} else {
				$link->setStatus(FederatedLink::STATUS_LINK_REQUESTED);
				$this->eventsService->onLinkRequestReceived($circle, $link);
			}

			$this->federatedLinksRequest->create($link);
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param Circle $circle
	 * @param FederatedLink $link
	 *
	 * @throws FederatedLinkCreationException
	 */
	private function checkLinkRequestValidity($circle, $link) {
		if ($circle->getUniqueId(true) === $link->getUniqueId(true)) {
			throw new FederatedLinkCreationException('duplicate_unique_id');
		}

		try {
			$this->federatedLinksRequest->getLinkFromCircle(
				$circle->getUniqueId(), $link->getUniqueId(true)
			);
			throw new FederatedLinkCreationException('duplicate_link');
		} catch (FederatedLinkDoesNotExistException $e) {
		}

		if ($circle->getSetting('allow_links') !== 'true') {
			throw new FederatedLinkCreationException('circle_links_disable');
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
	 *
	 * @return array
	 */
	private static function generateLinkData(FederatedLink $link) {
		return [
			'apiVersion' => Circles::version(),
			'token'      => $link->getToken(true),
			'uniqueId'   => $link->getCircleId(true),
			'linkTo'     => $link->getRemoteCircleName(),
			'address'    => $link->getLocalAddress()
		];
	}


	/**
	 * @param array $args
	 *
	 * @return array
	 */
	private static function generateClientBodyData($args) {
		return [
			'body'            => $args,
			'timeout'         => 5,
			'connect_timeout' => 5,
		];
	}


}