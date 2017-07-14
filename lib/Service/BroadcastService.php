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
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Exceptions\BroadcasterIsNotCompatible;
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;


class BroadcastService {

	/** @var string */
	private $userId;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var MiscService */
	private $miscService;


	/**
	 * SharesService constructor.
	 *
	 * @param string $userId
	 * @param ConfigService $configService
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		ConfigService $configService,
		CirclesRequest $circlesRequest,
		MembersRequest $membersRequest,
		MiscService $miscService
	) {
		$this->userId = (string)$userId;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->miscService = $miscService;
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
	public function broadcastFrame($broadcast, SharingFrame $frame) {

		if ($broadcast === null) {
			return;
		}

		$broadcaster = \OC::$server->query((string)$broadcast);
		if (!($broadcaster instanceof IBroadcaster)) {
			throw new BroadcasterIsNotCompatible();
		}

		$broadcaster->init();
		$broadcaster->createShareToCircle($frame);
		$users = $this->membersRequest->forceGetMembers($frame->getCircleId(), Member::LEVEL_MEMBER, true);
		foreach ($users AS $user) {
			$broadcaster->createShareToUser($frame, $user->getUserId());
		}

	}


}