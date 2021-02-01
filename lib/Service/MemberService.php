<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteEventException;
use OCA\Circles\Exceptions\ViewerNotFoundException;
use OCA\Circles\IMember;
use OCA\Circles\Model\CurrentUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Remote\RemoteEvent;
use OCA\Circles\RemoteEvents\MemberAdd;


/**
 * Class MemberService
 *
 * @package OCA\Circles\Service
 */
class MemberService {


	use TArrayTools;
	use TStringTools;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var CurrentUserService */
	private $currentUserService;

	/** @var RemoteEventService */
	private $remoteEventService;


	/**
	 * MemberService constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param CurrentUserService $currentUserService
	 * @param RemoteEventService $remoteEventService
	 */
	public function __construct(
		CircleRequest $circleRequest, MemberRequest $memberRequest, CurrentUserService $currentUserService,
		RemoteEventService $remoteEventService
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->currentUserService = $currentUserService;
		$this->remoteEventService = $remoteEventService;
	}

//
//	/**
//	 * @param Member $member
//	 *
//	 * @throws MemberAlreadyExistsException
//	 */
//	public function saveMember(Member $member) {
//		$member->setId($this->token(Member::ID_LENGTH));
//		$this->memberRequest->save($member);
//	}
//

	/**
	 * @param string $circleId
	 *
	 * @return Member[]
	 */
	public function getMembers(string $circleId): array {
		return $this->memberRequest->getMembers($circleId);
	}


	/**
	 * @param string $circleId
	 * @param IMember $member
	 *
	 * @throws CircleNotFoundException
	 * @throws ViewerNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteEventException
	 */
	public function addMember(string $circleId, IMember $member) {
		$this->currentUserService->mustHaveCurrentUser();
		$circle = $this->circleRequest->getCircle($circleId, $this->currentUserService->getCurrentUser());

		if ($member instanceof CurrentUser) {
			$tmp = new Member();
			$tmp->importFromIMember($member);
			$member = $tmp;
		}

//		$member->setId($this->token(Member::ID_LENGTH))
//			   ->setCircleId($circle->getId())

//
//		$member = new Member();
//		$member->importFromIMember($owner);
//		$member->setId($this->token(Member::ID_LENGTH))
//			   ->setCircleId($circle->getId())
//			   ->setLevel(Member::LEVEL_OWNER)
//			   ->setStatus(Member::STATUS_MEMBER);
//		$circle->setOwner($member);
//
		$event = new RemoteEvent(MemberAdd::class);
		$event->setCircle($circle);
		$event->setMember($member);
		$this->remoteEventService->newEvent($event);

//		return $circle;

//
//		// TODO: Use memberships to manage access level to a Circle !
//		if ($circle->getViewer()->getLevel() < Member::LEVEL_MODERATOR) {
//			throw new MemberLevelException('member have no rights to add a member to this circle');
//		}

	}

}

