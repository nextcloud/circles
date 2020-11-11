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

use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OC\AppFramework\Http;
use OCA\Circles\Db\SharesRequest;
use OCA\Circles\Db\TokensRequest;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharesToken;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\BroadcastService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\SharingFrameService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IURLGenerator;


/**
 * Class SharesController
 *
 * @package OCA\Circles\Controller
 */
class SharesController extends Controller {


	use TStringTools;


	/** @var TokensRequest */
	private $tokenRequest;

	/** @var SharesRequest */
	private $sharesRequest;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var MembersService */
	private $membersService;

	/** @var BroadcastService */
	private $broadcastService;

	/** @var SharingFrameService */
	private $sharingFrameService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * SharesController constructor.
	 *
	 * @param $appName
	 * @param IRequest $request
	 * @param TokensRequest $tokenRequest
	 * @param SharesRequest $sharesRequest
	 * @param IURLGenerator $urlGenerator
	 * @param MembersService $membersService
	 * @param BroadcastService $broadcastService
	 * @param SharingFrameService $sharingFrameService
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$appName, IRequest $request, TokensRequest $tokenRequest, SharesRequest $sharesRequest,
		IUrlGenerator $urlGenerator, MembersService $membersService,
		BroadcastService $broadcastService,
		SharingFrameService $sharingFrameService, ConfigService $configService,
		MiscService $miscService
	) {
		parent::__construct($appName, $request);

		$this->tokenRequest = $tokenRequest;
		$this->sharesRequest = $sharesRequest;
		$this->urlGenerator = $urlGenerator;
		$this->membersService = $membersService;
		$this->broadcastService = $broadcastService;
		$this->sharingFrameService = $sharingFrameService;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * Called by the JavaScript API when creating a new Share item that will be
	 * broadcasted to the circle itself, and any other circle linked to it.
	 *
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $circleUniqueId
	 * @param string $source
	 * @param string $type
	 * @param array $payload
	 *
	 * @return DataResponse
	 */
	public function create($circleUniqueId, $source, $type, $payload) {

		$this->miscService->log('Creating circle share: ' . $circleUniqueId, 0);

		try {
			$share = new SharingFrame($source, $type);
			$share->setPayload($payload);

			$this->sharingFrameService->createFrame($circleUniqueId, $share);
		} catch (\Exception $e) {
			$this->miscService->log('Failed to create circle - ' . $e->getMessage(), 3);

			return $this->fail(
				[
					'circle_id' => $circleUniqueId,
					'source'    => $source,
					'type'      => $type,
					'payload'   => $payload,
					'error'     => $e->getMessage()
				]
			);
		}

		$this->miscService->log('Created circle: share ' . $circleUniqueId, 0);

		return $this->success(
			[
				'circle_id' => $circleUniqueId,
				'source'    => $source,
				'type'      => $type,
				'payload'   => $payload
			]
		);
	}


	/**
	 * initShareDelivery()
	 *
	 * Note: this function will close the request mid-run from the client but will still
	 * running its process.
	 *
	 * Called by locally, the function will get the SharingFrame by its uniqueId from the database.
	 * After closing the socket, will broadcast the Frame locally and - if Federated Shares are
	 * enable - will deliver it to each remote circles linked to the circle the Payload belongs to.
	 *
	 * A status response is sent to free the client process before starting to broadcast the item
	 * to other federated links.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $circleId
	 * @param string $frameId
	 *
	 * @return DataResponse
	 * @throws Exception
	 */
	public function initShareDelivery($circleId, $frameId) {
		try {
			$frame = $this->sharingFrameService->getFrameFromUniqueId($circleId, $frameId);
		} catch (Exception $e) {
			return $this->fail($e->getMessage());
		}

		// We don't want to keep the connection up
		$this->miscService->asyncAndLeaveClientOutOfThis('done');

		$this->broadcastService->broadcastFrame($frame);

		// TODO - do not update cloudId to avoid duplicate, use it's own field and keep cloudId
		$this->sharingFrameService->updateFrameWithCloudId($frame);
		if ($this->configService->isFederatedCirclesAllowed()) {
			$this->sharingFrameService->forwardSharingFrame($frame);
		}

		exit();
	}


	/**
	 * @param SharesToken $shareToken
	 *
	 * @throws MemberDoesNotExistException
	 */
	private function checkContactMail(SharesToken $shareToken) {
		try {
			$this->membersService->getMember(
				$shareToken->getCircleId(), $shareToken->getUserId(), Member::TYPE_MAIL, true
			);

			return;
		} catch (Exception $e) {
		}

		try {
			$this->membersService->getMember(
				$shareToken->getCircleId(), $shareToken->getUserId(), Member::TYPE_CONTACT, true
			);

			return;
		} catch (Exception $e) {
		}

		throw new MemberDoesNotExistException();
	}

	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	private function fail($data) {
		$this->miscService->log(json_encode($data));

		return new DataResponse(
			array_merge($data, array('status' => 0)),
			Http::STATUS_NON_AUTHORATIVE_INFORMATION
		);
	}

	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	private function success($data) {
		return new DataResponse(
			array_merge($data, array('status' => 1)),
			Http::STATUS_CREATED
		);
	}

}

