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
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MemberIsOwnerException;
use OCA\Circles\Exceptions\ModeratorIsNotHighEnoughException;
use OCA\Circles\Model\GlobalScale\GSEvent;

/**
 * Class MemberDelete
 *
 * @package OCA\Circles\GlobalScale
 */
class MemberRemove extends AGlobalScaleEvent {
	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 *
	 * @throws MemberDoesNotExistException
	 * @throws MemberIsNotModeratorException
	 * @throws MemberIsOwnerException
	 * @throws ModeratorIsNotHighEnoughException
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws GlobalScaleDSyncException
	 * @throws GlobalScaleEventException
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		parent::verify($event, $localCheck, true);

		$circle = $event->getDeprecatedCircle();
		$member = $event->getMember();

		$member->hasToBeMemberOrAlmost();
		$member->cantBeOwner();

		if (!$event->isForced()) {
			$circle->getHigherViewer()
				   ->hasToBeModerator();
			$circle->getHigherViewer()
				   ->hasToBeHigherLevel($member->getLevel());
		}
	}


	/**
	 * @param GSEvent[] $events
	 */
	public function result(array $events): void {
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws MemberAlreadyExistsException
	 */
	public function manage(GSEvent $event): void {
		$circle = $event->getDeprecatedCircle();
		$member = $event->getMember();

		$this->eventsService->onMemberLeaving($circle, $member);
		$this->membersRequest->removeMember($member);

		$this->gsSharesRequest->removeGSSharesFromMember($member);
		$this->fileSharesRequest->removeSharesFromMember($member);
		$this->tokensRequest->removeTokensFromMember($member);
	}
}
