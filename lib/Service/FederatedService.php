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
use OC\Http\Client\ClientService;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\FederatedLinksRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\FederatedCircleLinkFormatException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
use OCA\Circles\Exceptions\CircleTypeNotValid;
use OCA\Circles\Exceptions\FederatedCircleStatusUpdateException;
use OCA\Circles\Exceptions\FederatedRemoteCircleDoesNotExistException;
use OCA\Circles\Exceptions\FederatedRemoteDoesNotAllowException;
use OCA\Circles\Exceptions\FrameAlreadyExistException;
use OCA\Circles\Exceptions\LinkCreationException;
use OCA\Circles\Exceptions\MemberIsNotAdminException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\SharingFrame;
use OCP\IL10N;

class FederatedService {


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

	/** @var string */
	private $serverHost;

	/** @var ClientService */
	private $clientService;

	/** @var MiscService */
	private $miscService;

	/** @var bool */
	private $localTest = false;

	/**
	 * CirclesService constructor.
	 *
	 * @param $userId
	 * @param IL10N $l10n
	 * @param CirclesRequest $circlesRequest
	 * @param ConfigService $configService
	 * @param CirclesService $circlesService
	 * @param BroadcastService $broadcastService
	 * @param FederatedLinksRequest $federatedLinksRequest
	 * @param string $serverHost
	 * @param ClientService $clientService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IL10N $l10n,
		CirclesRequest $circlesRequest,
		ConfigService $configService,
		CirclesService $circlesService,
		BroadcastService $broadcastService,
		FederatedLinksRequest $federatedLinksRequest,
		$serverHost,
		ClientService $clientService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->circlesRequest = $circlesRequest;
		$this->configService = $configService;
		$this->circlesService = $circlesService;
		$this->broadcastService = $broadcastService;
		$this->federatedLinksRequest = $federatedLinksRequest;
		$this->serverHost = (string)$serverHost;
		$this->clientService = $clientService;
		$this->miscService = $miscService;
	}


	/**
	 * linkCircle()
	 *
	 * link to a circle.
	 * Function will check if settings allow Federated links between circles, and the format of
	 * the link ($remote). If no exception, a request to the remote circle will be initiated
	 * using requestLinkWithCircle()
	 *
	 * $remote format: <circle_name>@<remote_host>
	 *
	 * @param int $circleId
	 * @param string $remote
	 *
	 * @throws Exception
	 * @throws FederatedCircleLinkFormatException
	 * @throws CircleTypeNotValid
	 * @throws MemberIsNotAdminException
	 *
	 * @return FederatedLink
	 */
	public function linkCircle($circleId, $remote) {

		if (!$this->configService->isFederatedAllowed()) {
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
			return $this->requestLinkWithCircle($circleId, $remote);
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
	 * @param int $linkId
	 * @param int $status
	 *
	 * @throws Exception
	 * @throws FederatedCircleLinkFormatException
	 * @throws CircleTypeNotValid
	 * @throws MemberIsNotAdminException
	 *
	 * @return FederatedLink[]
	 */
	public function linkStatus($linkId, $status) {

		$status = (int)$status;
		$link = null;
		try {

			$link = $this->circlesRequest->getLinkFromId($linkId);
			$circle = $this->circlesRequest->getCircleFromId($link->getCircleId(), $this->userId);
			$circle->hasToBeFederated();

			$link->hasToBeValidStatusUpdate($status);
			$link->setStatus($status);

			$this->federatedLinksRequest->update($link);

		} catch (Exception $e) {
			throw $e;
		}

		try {
			$link->setUniqueId($circle->getUniqueId());
			$this->updateLinkRemote($link);
		} catch (Exception $e) {
		}

		return $this->circlesRequest->getLinksFromCircle($circle->getId());
	}


	/**
	 * requestLinkWithCircle()
	 *
	 * Using CircleId, function will get more infos from the database.
	 * Will check if author is at least admin and initiate a FederatedLink, save it
	 * in the database and send a request to the remote circle using requestLink()
	 * If any issue, entry is removed from the database.
	 *
	 * @param integer $circleId
	 * @param string $remote
	 *
	 * @return FederatedLink
	 * @throws Exception
	 */
	private function requestLinkWithCircle($circleId, $remote) {

		$link = null;
		try {
			list($remoteCircle, $remoteAddress) = explode('@', $remote, 2);

			$circle = $this->circlesService->detailsCircle($circleId);
			$circle->getUser()
				   ->hasToBeAdmin();
			$circle->hasToBeFederated();
			$circle->cantBePersonal();

			$link = new FederatedLink();
			$link->setCircleId($circleId)
				 ->setLocalAddress($this->serverHost)
				 ->setAddress($remoteAddress)
				 ->setRemoteCircleName($remoteCircle)
				 ->setStatus(FederatedLink::STATUS_LINK_SETUP)
				 ->generateToken();

			$this->federatedLinksRequest->create($link);
			$this->requestLink($circle, $link);

		} catch (Exception $e) {
			if ($link !== null) {
				$this->federatedLinksRequest->delete($link);
			}
			throw $e;
		}

		return $link;
	}


	/**
	 * @param string $remote
	 *
	 * @return string
	 */
	private function generateLinkRemoteURL($remote) {
		if ($this->localTest === false && strpos($remote, 'https') !== 0) {
			$remote = 'https://' . $remote;
		}

		return rtrim($remote, '/') . '/index.php/apps/circles/v1/link';
	}


	/**
	 * @param string $remote
	 *
	 * @return string
	 */
	private function generatePayloadDeliveryURL($remote) {
		if ($this->localTest === false && strpos($remote, 'https') !== 0) {
			$remote = 'https://' . $remote;
		}

		return rtrim($remote, '/') . '/index.php/apps/circles/v1/payload';
	}


	public function allowNonSSLLink() {
		$this->localTest = true;
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
	private function requestLink(Circle $circle, FederatedLink & $link) {
		$args = [
			'apiVersion' => Circles::API_VERSION,
			'token'      => $link->getToken(),
			'uniqueId'   => $circle->getUniqueId(),
			'sourceName' => $circle->getName(),
			'linkTo'     => $link->getRemoteCircleName(),
			'address'    => $link->getLocalAddress()
		];

		$client = $this->clientService->newClient();

		try {
			$request = $client->put(
				$this->generateLinkRemoteURL($link->getAddress()), [
																	 'body'            => $args,
																	 'timeout'         => 10,
																	 'connect_timeout' => 10,
																 ]
			);

			$result = json_decode($request->getBody(), true);

			if ($result['status'] === FederatedLink::STATUS_LINK_UP) {
				$link->setStatus(FederatedLink::STATUS_LINK_UP);
			} else if ($result['status'] === FederatedLink::STATUS_LINK_REQUESTED) {
				$link->setStatus(FederatedLink::STATUS_REQUEST_SENT);
			} else {
				$this->parsingRequestLinkResult($result);
			}

			$link->setUniqueId($result['uniqueId']);
			$this->federatedLinksRequest->uniqueness($link);
			$this->federatedLinksRequest->update($link);

			return true;
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param $token
	 * @param $uniqueId
	 * @param $status
	 *
	 * @return FederatedLink
	 * @throws Exception
	 */
	public function updateLinkFromRemote($token, $uniqueId, $status) {
		try {
			$link = $this->circlesRequest->getLinkFromToken($token, $uniqueId);
			$circle = $this->circlesRequest->getCircleFromId($link->getCircleId());
			$circle->hasToBeFederated();


			$this->checkUpdateLinkFromRemote($link, $status);
			$this->checkUpdateLinkFromRemoteLinkUp($link, $status);
			$this->checkUpdateLinkFromRemoteLinkRemove($link, $status);

			if ($link->getStatus() !== $status) {
				$this->federatedLinksRequest->update($link);
			}

			return $link;
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * @param FederatedLink $link
	 * @param $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function checkUpdateLinkFromRemote(FederatedLink &$link, $status) {
		$status = (int)$status;
		if ($status === FederatedLink::STATUS_LINK_UP
			|| $status === FederatedLink::STATUS_LINK_REMOVE
		) {
			return;
		}

		throw new FederatedCircleStatusUpdateException(
			$this->l10n->t('Cannot proceed with this status update')
		);
	}


	/**
	 * @param FederatedLink $link
	 * @param $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function checkUpdateLinkFromRemoteLinkUp(FederatedLink &$link, $status) {
		if ((int)$status !== FederatedLink::STATUS_LINK_UP) {
			return;
		}

		if ($link->getStatus() !== FederatedLink::STATUS_REQUEST_SENT) {
			throw new FederatedCircleStatusUpdateException(
				$this->l10n->t('Cannot proceed with this status update')
			);
		}

		$link->setStatus($status);
	}


	/**
	 * @param FederatedLink $link
	 * @param $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function checkUpdateLinkFromRemoteLinkRemove(FederatedLink &$link, $status) {
		if ((int)$status !== FederatedLink::STATUS_LINK_REMOVE) {
			return;
		}

		if ($link->getStatus() === FederatedLink::STATUS_REQUEST_SENT) {
			$link->setStatus(FederatedLink::STATUS_REQUEST_DECLINED);

			return;
		} else if ($link->getStatus() === FederatedLink::STATUS_LINK_REQUESTED) {
			$link->setStatus(FederatedLink::STATUS_LINK_REMOVE);

			return;
		} else if ($link->getStatus() > FederatedLink::STATUS_LINK_DOWN) {

			$link->setStatus(FederatedLink::STATUS_LINK_DOWN);

			return;
		}
		throw new FederatedCircleStatusUpdateException(
			$this->l10n->t('Cannot proceed with this status update')
		);
	}


	/**
	 * updateLinkRemote()
	 *
	 * Send a request to the remote of the link to update its status.
	 *
	 * @param FederatedLink $link
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public function updateLinkRemote(FederatedLink & $link) {
		$args = [
			'apiVersion' => Circles::API_VERSION,
			'token'      => $link->getToken(),
			'uniqueId'   => $link->getUniqueId(),
			'status'     => $link->getStatus()
		];

		$client = $this->clientService->newClient();

		try {
			$client->post(
				$this->generateLinkRemoteURL($link->getAddress()), [
																	 'body'            => $args,
																	 'timeout'         => 10,
																	 'connect_timeout' => 10,
																 ]
			);

			return true;
		} catch (Exception $e) {
			throw $e;
		}
	}


	private function parsingRequestLinkResult($result) {

		if ($result['reason'] === 'federated_not_allowed') {
			throw new FederatedRemoteDoesNotAllowException(
				$this->l10n->t('Federated circles are not allowed on the remote Nextcloud')
			);
		}

		if ($result['reason'] === 'circle_links_disable') {
			throw new FederatedRemoteDoesNotAllowException(
				$this->l10n->t('The remote circle does not accept Federated Links')
			);
		}

		if ($result['reason'] === 'duplicate_unique_id') {
			throw new FederatedRemoteDoesNotAllowException(
				$this->l10n->t('It seems that you are trying to link a circle to itself')
			);
		}

		if ($result['reason'] === 'duplicate_link') {
			throw new FederatedRemoteDoesNotAllowException(
				$this->l10n->t('This link exists already')
			);
		}

		if ($result['reason'] === 'circle_does_not_exist') {
			throw new FederatedRemoteCircleDoesNotExistException(
				$this->l10n->t('The requested remote circle does not exist')
			);
		}

		throw new Exception($result['reason']);
	}


	/**
	 * Create a new link into database and assign the correct status.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 *
	 * @throws Exception
	 */
	public function initiateLink(Circle $circle, FederatedLink & $link) {

		try {
			$this->checkLinkRequestValidity($circle, $link);
			$link->setCircleId($circle->getId());

			if ($circle->getSetting('allow_links_auto') === 'true') {
				$link->setStatus(FederatedLink::STATUS_LINK_UP);
			} else {
				$link->setStatus(FederatedLink::STATUS_LINK_REQUESTED);
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
	 * @throws LinkCreationException
	 */
	private function checkLinkRequestValidity($circle, $link) {
		if ($circle->getUniqueId() === $link->getUniqueId()) {
			throw new LinkCreationException('duplicate_unique_id');
		}

		if ($this->getLink($circle->getId(), $link->getUniqueId()) !== null) {
			throw new LinkCreationException('duplicate_link');
		}

		if ($circle->getSetting('allow_links') !== 'true') {
			throw new LinkCreationException('circle_links_disable');
		}
	}


	/**
	 * @param string $token
	 * @param string $uniqueId
	 * @param SharingFrame $frame
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function receiveFrame($token, $uniqueId, SharingFrame & $frame) {
		try {

			$link = $this->circlesRequest->getLinkFromToken((string)$token, (string)$uniqueId);
		} catch (Exception $e) {
			throw $e;
		}

		if ($this->circlesRequest->getFrame($link->getCircleId(), $frame->getUniqueId())) {
			$this->miscService->log("Frame already exist");
			throw new FrameAlreadyExistException('shares_is_already_known');
		}

		try {
			$circle = $this->circlesRequest->getCircleFromId($link->getCircleId());
		} catch (CircleDoesNotExistException $e) {
			throw new CircleDoesNotExistException('unknown_circle');
		}

		$frame->setCircleId($link->getCircleId());
		$frame->setCircleName($circle->getName());

		$this->circlesRequest->saveFrame($frame);
		$this->broadcastService->broadcastFrame($frame->getHeader('broadcast'), $frame);

		return true;
	}

	/**
	 * @param integer $circleId
	 * @param string $uniqueId
	 *
	 * @return FederatedLink
	 */
	public function getLink($circleId, $uniqueId) {
		return $this->federatedLinksRequest->getFromUniqueId($circleId, $uniqueId);
	}


	/**
	 * @param integer $circleId
	 *
	 * @return FederatedLink[]
	 */
	public function getLinks($circleId) {
		return $this->federatedLinksRequest->getLinked($circleId);
	}


	/**
	 * @param int $circleId
	 * @param string $uniqueId
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function initiateRemoteShare($circleId, $uniqueId) {
		$args = [
			'apiVersion' => Circles::API_VERSION,
			'circleId'   => (int)$circleId,
			'uniqueId'   => (string)$uniqueId
		];

		$client = $this->clientService->newClient();
		try {
			$request = $client->post(
				$this->generatePayloadDeliveryURL($this->serverHost), [
																		'body'            => $args,
																		'timeout'         => 10,
																		'connect_timeout' => 10,
																	]
			);

			$result = json_decode($request->getBody(), true);
			$this->miscService->log(
				"initiateRemoteShare result: " . $uniqueId . '  ----  ' . var_export($result, true)
			);

			return true;
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param SharingFrame $frame
	 *
	 * @throws Exception
	 */
	public function sendRemoteShare(SharingFrame $frame) {

		$circle = $this->circlesRequest->getCircleFromId($frame->getCircleId());
		if ($circle === null) {
			throw new Exception('unknown_circle');
		}

		$links = $this->getLinks($frame->getCircleId());
		foreach ($links AS $link) {

			$args = [
				'apiVersion' => Circles::API_VERSION,
				'token'      => $link->getToken(),
				'uniqueId'   => $circle->getUniqueId(),
				'item'       => json_encode($frame)
			];

			$client = $this->clientService->newClient();
			try {
				$client->put(
					$this->generatePayloadDeliveryURL($link->getAddress()), [
																			  'body'            => $args,
																			  'timeout'         => 10,
																			  'connect_timeout' => 10,
																		  ]
				);
			} catch (Exception $e) {
				throw $e;
			}
		}
	}


	/**
	 * generateHeaders()
	 *
	 * Generate new headers for the current Payload, and save them in the SharingFrame.
	 *
	 * @param SharingFrame $frame
	 */
	public function updateFrameWithCloudId(SharingFrame $frame) {
		$frame->setCloudId($this->serverHost);
		$this->circlesRequest->updateFrame($frame);
	}

}