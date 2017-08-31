<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\FederatedLinksRequest;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\FederatedCircleLinkFormatException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
use OCA\Circles\Exceptions\FederatedLinkCreationException;
use OCA\Circles\Exceptions\FederatedLinkDoesNotExistException;
use OCA\Circles\Exceptions\FederatedRemoteDoesNotAllowException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCP\Http\Client\IClientService;
use OCP\IL10N;

class FederatedLinkCreationService {

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

	/** @var BroadcastService */
	private $federatedLinkService;

	/** @var FederatedLinksRequest */
	private $federatedLinksRequest;

	/** @var EventsService */
	private $eventsService;

	/** @var IClientService */
	private $clientService;

	/** @var MiscService */
	private $miscService;


	/**
	 * FederatedLinkCreationService constructor.
	 *
	 * @param string $UserId
	 * @param IL10N $l10n
	 * @param CirclesRequest $circlesRequest
	 * @param ConfigService $configService
	 * @param CirclesService $circlesService
	 * @param BroadcastService $broadcastService
	 * @param FederatedLinkService $federatedService
	 * @param FederatedLinksRequest $federatedLinksRequest
	 * @param EventsService $eventsService
	 * @param IClientService $clientService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$UserId, IL10N $l10n, CirclesRequest $circlesRequest, ConfigService $configService,
		CirclesService $circlesService, BroadcastService $broadcastService,
		FederatedLinkService $federatedService,
		FederatedLinksRequest $federatedLinksRequest, EventsService $eventsService,
		IClientService $clientService, MiscService $miscService
	) {
		$this->userId = $UserId;
		$this->l10n = $l10n;
		$this->circlesRequest = $circlesRequest;
		$this->configService = $configService;
		$this->circlesService = $circlesService;
		$this->broadcastService = $broadcastService;
		$this->federatedLinkService = $federatedService;
		$this->federatedLinksRequest = $federatedLinksRequest;
		$this->eventsService = $eventsService;

		$this->clientService = $clientService;
		$this->miscService = $miscService;
	}


	/**
	 * createLinkWithRemoteCircle();
	 *
	 * link to a circle.
	 * Function will check if settings allow Federated links between circles, and the format of
	 * the link ($remote). If no exception, a request to the remote circle will be initiated
	 * using requestLinkWithRemoteCircle()
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
	public function createLinkWithRemoteCircle($circleUniqueId, $remote) {

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
			return $this->requestLinkWithRemoteCircle($circleUniqueId, $remote);
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
	public function requestedLinkFromRemoteCircle(Circle $circle, FederatedLink &$link) {

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
	 * requestLinkWithRemoteCircle()
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
	private function requestLinkWithRemoteCircle($circleUniqueId, $remote) {

		$link = null;
		try {
			$circle = $this->circlesService->detailsCircle($circleUniqueId);
			$circle->getHigherViewer()
				   ->hasToBeAdmin();
			$circle->hasToBeFederated();
			$circle->cantBePersonal();

			$link = $this->generateNewLink($circle->getUniqueId(), $remote);
			$this->forceRequestNewLink($circle, $link);
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
	private function generateNewLink($circleUniqueId, $remote) {

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
	 * requestLink()
	 *
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 *
	 * @return boolean
	 * @throws Exception
	 */
	private function forceRequestNewLink(Circle $circle, FederatedLink &$link) {
		try {
			$client = $this->clientService->newClient();
			$args = ['sourceName' => $circle->getName()];
			$url = $this->federatedLinkService->generateLinkRemoteURL($link->getAddress());

			$response = $client->put($url, FederatedLinkService::generateClientBodyData($link, $args));
			$result = $this->federatedLinkService->parseClientRequestResult($response);

			$reason = ((key_exists('reason', $result)) ? $result['reason'] : '');
			$this->eventOnRequestLink($circle, $link, $result['status'], $reason);

			$link->setUniqueId($result['uniqueId']);
			$this->federatedLinksRequest->update($link);

			return true;
		} catch (Exception $e) {
			throw $e;
		}
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


}