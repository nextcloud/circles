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

namespace OCA\Circles\Controller;

use OC\AppFramework\Http;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\FederatedService;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\SharesService;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;

class FederatedController extends BaseController {

	/** @var string */
	protected $userId;

	/** @var IL10N */
	protected $l10n;

	/** @var ConfigService */
	protected $configService;

	/** @var CirclesService */
	protected $circlesService;

	/** @var MembersService */
	protected $membersService;

	/** @var SharesService */
	protected $sharesService;

	/** @var FederatedService */
	protected $federatedService;

	/** @var MiscService */
	protected $miscService;


	/**
	 * requestedLink()
	 *
	 * Called when a remote circle want to create a link.
	 * The function check if it is possible first; then create a link- object
	 * and sent it to be saved in the database.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 * @param string $uniqueId
	 * @param string $sourceName
	 * @param string $linkTo
	 * @param string $address
	 *
	 * @return DataResponse
	 */
	public function requestedLink($token, $uniqueId, $sourceName, $linkTo, $address) {

		if ($uniqueId === '' || !$this->configService->isFederatedAllowed()) {
			return $this->federatedFail('federated_not_allowed');
		}

		$circle = $this->circlesService->infoCircleByName($linkTo);
		if ($circle === null) {
			return $this->federatedFail('circle_does_not_exist');
		}

		if ($circle->getUniqueId() === $uniqueId) {
			return $this->federatedFail('duplicate_unique_id');
		}

		if ($this->federatedService->getLink($circle->getId(), $uniqueId) !== null) {
			return $this->federatedFail('duplicate_link');
		}

		$link = new FederatedLink();
		$link->setToken($token)
			 ->setUniqueId($uniqueId)
			 ->setRemoteCircleName($sourceName)
			 ->setAddress($address);

		if ($this->federatedService->initiateLink($circle, $link)) {
			return $this->federatedSuccess(
				['status' => $link->getStatus(), 'uniqueId' => $circle->getUniqueId()], $link
			);
		} else {
			return $this->federatedFail('link_failed');
		}
	}


	/**
	 * initFederatedDelivery()
	 *
	 * Note: this function will close the request mid-run from the client but will still
	 * running its process.
	 * Called by locally, the function will get the SharingFrame by its uniqueId from the database,
	 * assign him some Headers and will deliver it to each remotes linked to the circle the Payload
	 * belongs to. A status response is sent to free the client process before starting to
	 * broadcast the item to other federated links.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param $circleId
	 * @param $uniqueId
	 *
	 * @return DataResponse
	 */
	public function initFederatedDelivery($circleId, $uniqueId) {

		if ($uniqueId === '' || !$this->configService->isFederatedAllowed()) {
			return $this->federatedFail('federated_not_allowed');
		}

		$frame = $this->sharesService->getFrameFromUniqueId($circleId, $uniqueId);
		if ($frame === null) {
			return $this->federatedFail('unknown_share');
		}

		if ($frame->getCloudId() !== null) {
			return $this->federatedFail('share_already_delivered');
		}

		// We don't want to keep the connection up
		$this->asyncAndLeaveClientOutOfThis('done');

		$this->federatedService->updateFrameWithCloudId($frame);
		$this->federatedService->sendRemoteShare($frame);

		exit();
	}


	/**
	 * receiveFederatedDelivery()
	 *
	 * Note: this function will close the request mid-run from the client but will still
	 * running its process.
	 * Called by a remote circle to broadcast a Share item, the function will save the item
	 * in the database and broadcast it locally. A status response is sent to the remote to free
	 * the remote process before starting to broadcast the item to other federated links.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param $token
	 * @param $uniqueId
	 * @param $item
	 *
	 * @return DataResponse
	 */
	public function receiveFederatedDelivery($token, $uniqueId, $item) {

		if ($uniqueId === '' || !$this->configService->isFederatedAllowed()) {
			return $this->federatedFail('federated_not_allowed');
		}

		$frame = SharingFrame::fromJSON($item);
		if (!$this->federatedService->receiveFrame($token, $uniqueId, $frame)) {
			return $this->federatedFail('shares_is_already_known');
		}

		$this->asyncAndLeaveClientOutOfThis('done');

		$this->federatedService->sendRemoteShare($frame);
		exit();
	}

	/**
	 * Hacky way to async the rest of the process without keeping client on hold.
	 *
	 * @param string $result
	 */
	private function asyncAndLeaveClientOutOfThis($result = '') {
		if (ob_get_contents() !== false) {
			ob_end_clean();
		}

		header('Connection: close');
		ignore_user_abort();
		ob_start();
		echo($result);
		$size = ob_get_length();
		header('Content-Length: '.$size);
		ob_end_flush();
		flush();
	}

	/**
	 * send a positive response to a request with an array of data, and confirm
	 * the identity of the link with a token
	 *
	 * @param array $data
	 * @param FederatedLink $link
	 *
	 * @return DataResponse
	 */
	private function federatedSuccess($data, $link) {
		return new DataResponse(
			array_merge($data, ['token' => $link->getToken()]), Http::STATUS_OK
		);

	}

	/**
	 * send a negative response to a request, with a reason of the failure.
	 *
	 * @param string $reason
	 *
	 * @return DataResponse
	 */
	private function federatedFail($reason) {
		return new DataResponse(
			[
				'status' => FederatedLink::STATUS_ERROR,
				'reason' => $reason
			],
			Http::STATUS_OK
		);
	}
}