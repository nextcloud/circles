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
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\RemoteEventException;
use OCA\Circles\Exceptions\ViewerNotFoundException;
use OCA\Circles\IMember;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Remote\RemoteEvent;
use OCA\Circles\RemoteEvents\CircleCreate;


/**
 * Class CircleService
 *
 * @package OCA\Circles\Service
 */
class CircleService {


	use TArrayTools;
	use TStringTools;


	/** @var CircleRequest */
	private $circleRequest;

	private $currentUserService;

	/** @var MemberService */
	private $memberService;

	/** @var RemoteEventService */
	private $remoteEventService;

	/** @var Member */
	private $viewer = null;


	/**
	 * CircleService constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param CurrentUserService $currentUserService
	 * @param RemoteEventService $remoteEventService
	 * @param MemberService $memberService
	 */
	public function __construct(
		CircleRequest $circleRequest, CurrentUserService $currentUserService,
		RemoteEventService $remoteEventService,
		MemberService $memberService
	) {
		$this->circleRequest = $circleRequest;
		$this->currentUserService = $currentUserService;
		$this->remoteEventService = $remoteEventService;
		$this->memberService = $memberService;
	}

//
//	/**
//	 * @param string $userId
//	 */
//	public function setLocalViewer(string $userId): void {
//		$this->viewer = new Viewer($userId, Member::TYPE_USER, '');
//	}
//
//	public function setViewer(Member $viewer): void {
//		$this->viewer = $viewer;
//	}
//
//	/**
//	 * @return Member|null
//	 */
//	public function getViewer(): ?Member {
//		return $this->viewer;
//	}


	/**
	 * @param string $name
	 * @param IMember|null $owner
	 *
	 * @return Circle
	 * @throws RemoteEventException
	 */
	public function create(string $name, ?IMember $owner = null): Circle {
		if (is_null($owner)) {
			$owner = $this->currentUserService->getCurrentUser();
		}

		$circle = new Circle();
		$circle->setName($name);
		$circle->setId($this->token(Circle::ID_LENGTH));

		$member = new Member();
		$member->importFromCurrentUser($owner);
		$member->setId($this->token(Circle::ID_LENGTH))
			   ->setCircleId($circle->getId())
			   ->setLevel(Member::LEVEL_OWNER)
			   ->setStatus(Member::STATUS_MEMBER);
		$circle->setOwner($member);

//		$circle->setOwner($owner)
//			   ->setViewer($owner);

		$event = new RemoteEvent(CircleCreate::class, true);
		$event->setCircle($circle);

		$this->remoteEventService->newEvent($event);

		return $circle;
	}


	public function saveCircle(Circle $circle): void {
		$circle->setId($this->token(Circle::ID_LENGTH));
		$this->circleRequest->save($circle);
	}


	/**
	 * @param Member|null $filter
	 *
	 * @return Circle[]
	 * @throws ViewerNotFoundException
	 */
	public function getCircles(?Member $filter = null): array {
		$this->currentUserService->mustHaveCurrentUser();

		return $this->circleRequest->getCircles($filter, $this->currentUserService->getCurrentUser());
	}


	/**
	 * @param string $circleId
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function getCircle(string $circleId): Circle {
		return $this->circleRequest->getCircle($circleId, $this->getViewer());
	}

}

