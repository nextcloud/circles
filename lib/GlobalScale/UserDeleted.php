<?php

declare(strict_types=1);


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


namespace OCA\Circles\GlobalScale;

use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\GlobalScaleDSyncException;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\DeprecatedMember;

/**
 * Class MemberDelete
 *
 * @package OCA\Circles\GlobalScale
 */
class UserDeleted extends AGlobalScaleEvent {
	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 *
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws GlobalScaleDSyncException
	 * @throws GlobalScaleEventException
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		parent::verify($event, $localCheck, true);

		$member = $event->getMember();
		$circles = $this->circlesRequest->getCircles($member->getUserId(), 0, '', DeprecatedMember::LEVEL_OWNER);

		$destroyedCircles = [];
		$promotedAdmins = [];
		foreach ($circles as $circle) {
			$members =
				$this->membersRequest->forceGetMembers($circle->getUniqueId(), DeprecatedMember::LEVEL_MEMBER);

			if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL || sizeof($members) === 1) {
				$destroyedCircles[] = $circle->getUniqueId();
				continue;
			}

			$promotedAdmins[] = $this->getOlderAdmin($members);
		}

		$event->getData()
			  ->sArray('destroyedCircles', $destroyedCircles)
			  ->sArray('promotedAdmins', $promotedAdmins);
	}


	/**
	 * @param GSEvent[] $events
	 */
	public function result(array $events): void {
	}


	/**
	 * @param GSEvent $event
	 */
	public function manage(GSEvent $event): void {
		$member = $event->getMember();

		$this->membersRequest->removeAllMembershipsFromUser($member);

		$data = $event->getData();
		$this->destroyCircles($data->gArray('destroyedCircles'));
		$this->promotedAdmins($data->gArray('promotedAdmins'));
	}


	/**
	 * @param DeprecatedMember[] $members
	 *
	 * @return string
	 */
	private function getOlderAdmin(array $members) {
		foreach ($members as $member) {
			if ($member->getLevel() === DeprecatedMember::LEVEL_ADMIN) {
				return $member->getMemberId();
			}
		}
		foreach ($members as $member) {
			if ($member->getLevel() === DeprecatedMember::LEVEL_MODERATOR) {
				return $member->getMemberId();
			}
		}
		foreach ($members as $member) {
			if ($member->getLevel() === DeprecatedMember::LEVEL_MEMBER) {
				return $member->getMemberId();
			}
		}
	}


	/**
	 * @param array $circleIds
	 */
	private function destroyCircles(array $circleIds) {
		foreach ($circleIds as $circleId) {
			$this->circlesRequest->destroyCircle($circleId);
			$this->membersRequest->removeAllFromCircle($circleId);
		}
	}


	/**
	 * @param array $memberIds
	 */
	private function promotedAdmins(array $memberIds) {
		foreach ($memberIds as $memberId) {
			try {
				$member = $this->membersRequest->forceGetMemberById($memberId);
				$member->setLevel(DeprecatedMember::LEVEL_OWNER);
				$this->membersRequest->updateMemberLevel($member);
			} catch (MemberDoesNotExistException $e) {
			}
		}
	}
}
