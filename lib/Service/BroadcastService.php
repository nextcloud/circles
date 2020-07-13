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


use daita\MySmallPhpTools\Model\SimpleDataStore;
use Exception;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\SharingFrame;


class BroadcastService {


	/** @var string */
	private $userId;

	/** @var GSUpstreamService */
	private $gsUpstreamService;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var MiscService */
	private $miscService;


	/**
	 * BroadcastService constructor.
	 *
	 * @param string $userId
	 * @param ConfigService $configService
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param GSUpstreamService $gsUpstreamService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		ConfigService $configService,
		CirclesRequest $circlesRequest,
		MembersRequest $membersRequest,
		GSUpstreamService $gsUpstreamService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->gsUpstreamService = $gsUpstreamService;
		$this->miscService = $miscService;
	}


	/**
	 * broadcast the SharingFrame item using a IBroadcaster.
	 * The broadcast is usually set by the app that created the SharingFrame item.
	 *
	 * If the circle is not a Personal Circle, we first call createShareToCircle()
	 * Then for each members of the circle, we call createShareToUser()
	 * If the circle is a Personal Circle, we don't send data about the SharingFrame but null.
	 *
	 * @param SharingFrame $frame
	 *
	 * @throws Exception
	 */
	public function broadcastFrame(SharingFrame $frame) {
		if ($frame->getHeader('broadcast') === null) {
			return;
		}

		$event = new GSEvent(GSEvent::FILE_SHARE, true);
		$event->setAsync(true);
		$event->setSeverity(GSEvent::SEVERITY_HIGH);
		$event->setCircle($frame->getCircle());
		$event->setData(new SimpleDataStore(['frame' => json_decode(json_encode($frame), true)]));

		$this->gsUpstreamService->newEvent($event);
	}

}

