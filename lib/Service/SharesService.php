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
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Frame;


class SharesService {

	/** @var string */
	private $userId;

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
	 * @param CirclesRequest $circlesRequest
	 * @param FederatedService $federatedService
	 * @param MiscService $miscService
	 */
	public function __construct(
		string $userId,
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
	 * @param Frame $frame
	 * @param string|null $broadcast
	 *
	 * @return bool
	 * @throws BroadcasterIsNotCompatible
	 */
	public function createFrame(Frame $frame, string $broadcast = null) {

		$frame->setAuthor($this->userId);

		// TODO: VERIFIER QUE L'UTILISATEUR EST BIEN MEMBRE
		$circle = $this->circlesRequest->getDetails($frame->getCircleId(), $this->userId);
		$frame->setCircleName($circle->getName());

		$this->circlesRequest->createShare($frame);
		$this->broadcastItem($broadcast, $frame);

		$this->federatedService->initiateRemoteShare($frame->getUniqueId());

		return true;
	}


	/**
	 * broadcast the Share item using a IBroadcaster, usually set by the app that created the Share
	 * item.
	 *
	 * @param string $broadcast
	 * @param Frame $frame
	 *
	 * @throws BroadcasterIsNotCompatible
	 */
	private function broadcastItem(string $broadcast, Frame $frame) {

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


	public function shareItemToFederatedLinks(Frame $share, string $broadcast = null) {

		//$circles = $this->circlesRequest->getFederatedLinks($share->getCircle());
// TODO, envoyer une requete http sur le broadcaster local en precisant qu'il a deja ete broadcaste en local
		//broadcastItem()
	}



//	public function reshare($circleId, $source, $type, $shareid) {
//		$this->miscService->log(
//			"__reshare" . $circleId . ' ' . $source . ' ' . $type . ' ' . json_encode($item)
//		);
//
//		$share = new Share($source, $type);
//		$share->setCircleId($circleId);
//		$share->setItem($item);
//
//		$share->setAuthor($this->userId);
//
//		$this->circlesRequest->createShare($share);
//
//		return true;
//	}

}