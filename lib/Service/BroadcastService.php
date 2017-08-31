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
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Exceptions\BroadcasterIsNotCompatibleException;
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Circle;
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
	 * BroadcastService constructor.
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
		$this->userId = $userId;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
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

		try {
			$broadcaster = \OC::$server->query((string)$frame->getHeader('broadcast'));
			if (!($broadcaster instanceof IBroadcaster)) {
				throw new BroadcasterIsNotCompatibleException();
			}

			$circle = $this->circlesRequest->forceGetCircle(
				$frame->getCircle()
					  ->getUniqueId()
			);

			$broadcaster->init();

			if ($circle->getType() !== Circle::CIRCLES_PERSONAL) {
				$broadcaster->createShareToCircle($frame, $circle);
			}

			$members = $this->membersRequest->forceGetMembers(
				$circle->getUniqueId(), Member::LEVEL_MEMBER, true
			);

			foreach ($members AS $member) {
				$this->parseMember($member);

				if ($member->isBroadcasting()) {
					$broadcaster->createShareToMember($frame, $member);
				}
			}
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param Member $member
	 */
	private function parseMember(Member &$member) {
		$this->parseMemberFromContact($member);
	}


	/**
	 * on Type Contact, we convert the type to MAIL and retreive the first mail of the list.
	 * If no email, we set the member as not broadcasting.
	 *
	 * @param Member $member
	 */
	private function parseMemberFromContact(Member &$member) {

		if ($member->getType() !== Member::TYPE_CONTACT) {
			return;
		}

		$contact = MiscService::getContactData($member->getUserId());
		if (!key_exists('EMAIL', $contact)) {
			$member->broadcasting(false);

			return;
		}

		$member->setType(Member::TYPE_MAIL);
		$member->setUserId(array_shift($contact['EMAIL']));
	}


}