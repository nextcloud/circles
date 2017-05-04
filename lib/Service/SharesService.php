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
use OCA\Circles\Model\Share;


class SharesService {

	/** @var string */
	private $userId;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MiscService */
	private $miscService;


	/**
	 * SharesService constructor.
	 *
	 * @param string $userId
	 * @param CirclesRequest $circlesRequest
	 * @param MiscService $miscService
	 */
	public function __construct(
		string $userId,
		CirclesRequest $circlesRequest,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->circlesRequest = $circlesRequest;
		$this->miscService = $miscService;
	}

	public function new(
		int $circleId, string $source, string $type, array $item, string $broadcast = null
	) {
		$share = new Share($source, $type);
		$share->setCircleId($circleId);
		$share->setItem($item);
		$share->setAuthor($this->userId);

		$this->circlesRequest->createShare($share);

		if ($broadcast !== null) {
			$broadcaster = \OC::$server->query($broadcast);
			if (!($broadcaster instanceof IBroadcaster)) {
				throw new BroadcasterIsNotCompatible();
			}

			$broadcaster->init();

			$userId = 'cult';
			$broadcaster->broadcast($userId, $share);
		}

		return true;
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