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


use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Exceptions\BroadcasterIsNotCompatible;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;


class SharesService {

	/** @var string */
	private $userId;
	
	/** @var ConfigService */
	private $configService;

	/** @var CirclesRequest */
	private $circlesRequest;

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
	 * @param FederatedService $federatedService
	 * @param MiscService $miscService
	 */
	public function __construct(
		string $userId,
		ConfigService $configService,
		CirclesRequest $circlesRequest,
		FederatedService $federatedService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->circlesRequest = $circlesRequest;
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
	 * @return bool
	 * @throws MemberDoesNotExistException
	 */
	public function createFrame(SharingFrame $frame, string $broadcast = null) {

		$circle = $this->circlesRequest->getDetails($frame->getCircleId(), $this->userId);
		if ($circle->getUser()
				   ->getLevel() < Member::LEVEL_MEMBER
		) {
			throw new MemberDoesNotExistException();
		}

		$frame->setAuthor($this->userId);
		$frame->generateUniqueId();
		$frame->setCircleName($circle->getName());

		$this->circlesRequest->saveFrame($frame);
		$this->broadcastFrame($broadcast, $frame);

		if ($this->configService->isFederatedAllowed()) {
			$this->federatedService->initiateRemoteShare($frame->getUniqueId());
		}

		return true;
	}


	public function getFrameFromUniqueId($uniqueId) {
		if ($uniqueId === null || $uniqueId === '') {
			return null;
		}

		return $this->circlesRequest->getFrame($uniqueId);
	}


	/**
	 * broadcast the SharingFrame item using a IBroadcaster.
	 * The broadcast is usually set by the app that created the SharingFrame item.
	 *
	 * @param string $broadcast
	 * @param SharingFrame $frame
	 *
	 * @throws BroadcasterIsNotCompatible
	 */
	private function broadcastFrame(string $broadcast, SharingFrame $frame) {

		if ($broadcast === null) {
			return;
		}

		$broadcaster = \OC::$server->query($broadcast);
		if (!($broadcaster instanceof IBroadcaster)) {
			throw new BroadcasterIsNotCompatible();
		}

		$broadcaster->init();
		$users = $this->circlesRequest->getMembers($frame->getCircleId(), Member::LEVEL_MEMBER);
		foreach ($users AS $user) {
			$broadcaster->broadcast($user->getUserId(), $frame);
		}

	}

}