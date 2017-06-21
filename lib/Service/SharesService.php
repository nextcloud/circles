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
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Exceptions\BroadcasterIsNotCompatible;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;


class SharesService {

	/** @var string */
	private $userId;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var BroadcastService */
	private $broadcastService;

	/** @var FederatedService */
	private $federatedService;

	/** @var MiscService */
	private $miscService;


	/**
	 * SharesService constructor.
	 *
	 * @param string $userId
	 * @param ConfigService $configService
	 * @param CirclesRequest $circlesRequest
	 * @param BroadcastService $broadcastService
	 * @param FederatedService $federatedService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		ConfigService $configService,
		CirclesRequest $circlesRequest,
		BroadcastService $broadcastService,
		FederatedService $federatedService,
		MiscService $miscService
	) {
		$this->userId = (string)$userId;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->broadcastService = $broadcastService;
		$this->federatedService = $federatedService;
		$this->miscService = $miscService;
	}


	/**
	 * createFrame()
	 *
	 * Save the Frame containing the Payload.
	 * The Payload will be shared locally, and spread it live if a Broadcaster is set.
	 * Function will also initiate the federated broadcast to linked circles.
	 *
	 * @param SharingFrame $frame
	 * @param string|null $broadcast
	 *
	 * @throws Exception
	 */
	public function createFrame(SharingFrame $frame, $broadcast = null) {

		$circle = $this->circlesRequest->getCircleFromId($frame->getCircleId(), $this->userId);
		if ($circle->getUser()
				   ->getLevel() < Member::LEVEL_MEMBER
		) {
			throw new MemberDoesNotExistException();
		}

		try {
			$this->generateHeaders($frame, $circle, $broadcast);
			$this->circlesRequest->saveFrame($frame);

			$this->broadcastService->broadcastFrame($frame->getHeader('broadcast'), $frame);

			if ($this->configService->isFederatedAllowed()) {
				$this->federatedService->initiateRemoteShare(
					$circle->getId(), $frame->getUniqueId()
				);
			}
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * Generate Headers and few more thing like UniqueId and Author.
	 * Check if the source is NOT Circles.
	 *
	 * @param SharingFrame $frame
	 * @param Circle $circle
	 * @param $broadcast
	 */
	private function generateHeaders(SharingFrame $frame, Circle $circle, $broadcast) {

		try {
			$frame->cannotBeFromCircles();

			$frame->setAuthor($this->userId);
			$frame->setHeader('author', $this->userId);
			$frame->setHeader('circleName', $circle->getName());
			$frame->setHeader('broadcast', (string)$broadcast);
			$frame->generateUniqueId();
			$frame->setCircleName($circle->getName());

		} catch (Exception $e) {
			throw new $e;
		}
	}

	/**
	 * @param int $circleId
	 * @param $uniqueId
	 *
	 * @return null|SharingFrame
	 */
	public function getFrameFromUniqueId($circleId, $uniqueId) {
		if ($uniqueId === null || $uniqueId === '') {
			return null;
		}

		return $this->circlesRequest->getFrame((int)$circleId, (string)$uniqueId);
	}


}