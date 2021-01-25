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


use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Logger;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\ViewerNotFoundException;
use OCA\Circles\IMember;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\CurrentUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;


/**
 * Class ViewerService
 *
 * @package OCA\Circles\Service
 */
class CurrentUserService {


	use TArrayTools;
	use TStringTools;
	use TNC21Logger;


	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;


	/** @var CurrentUser */
	private $currentUser = null;

	/** @var bool */
	private $bypass = false;


	/**
	 * ViewerService constructor.
	 *
	 * @param MembershipRequest $membershipRequest
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 */
	public function __construct(
		MembershipRequest $membershipRequest, CircleRequest $circleRequest, MemberRequest $memberRequest
	) {
		$this->membershipRequest = $membershipRequest;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
	}


	/**
	 * @param string $userId
	 *
	 * @throws CircleNotFoundException
	 */
	public function setLocalViewer(string $userId): void {
		$this->currentUser = new CurrentUser($userId, Member::TYPE_USER, '');
		$this->fillSingleCircleId($this->currentUser);
	}

	/**
	 * @param IMember $currentUser
	 *
	 * @throws CircleNotFoundException
	 */
	public function setCurrentUser(IMember $currentUser): void {
		$this->currentUser = $currentUser;
		$this->fillSingleCircleId($this->currentUser);
	}

	/**
	 * @return CurrentUser|null
	 */
	public function getCurrentUser(): ?CurrentUser {
		return $this->currentUser;
	}

	/**
	 * @return bool
	 */
	public function hasCurrentUser(): bool {
		return ($this->currentUser !== null);
	}

	/**
	 * @throws ViewerNotFoundException
	 */
	public function mustHaveCurrentUser(): void {
		if ($this->bypass) {
			return;
		}

		if (!$this->hasCurrentUser()) {
			throw new ViewerNotFoundException();
		}
	}

	/**
	 * @param bool $bypass
	 */
	public function bypassCurrentUserCondition(bool $bypass): void {
		$this->bypass = $bypass;
	}


	/**
	 * @param CurrentUser $currentUser
	 *
	 * @throws CircleNotFoundException
	 */
	private function fillSingleCircleId(CurrentUser $currentUser): void {
		if ($currentUser->getId() !== '') {
			return;
		}

		$circle = $this->getSingleCircle($currentUser);
		$currentUser->setId($circle->getId());
	}


	/**
	 * @param CurrentUser $currentUser
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function getSingleCircle(CurrentUser $currentUser): Circle {
		try {
			return $this->circleRequest->getViewerCircle($currentUser);
		} catch (CircleNotFoundException $e) {
			$circle = new Circle();
			$id = $this->token(Circle::ID_LENGTH);

			$circle->setName('single:' . $currentUser->getUserId() . ':' . $id)
				   ->setId($id)
				   ->setConfig(Circle::CFG_SINGLE);
			$this->circleRequest->save($circle);

			$owner = new Member();
			$owner->importFromCurrentUser($currentUser)
				  ->setLevel(Member::LEVEL_OWNER)
				  ->setCircleId($id)
				  ->setId($id)
				  ->setCachedName($owner->getUserId())
				  ->setStatus('Member');
			$this->memberRequest->save($owner);
		}

		return $this->circleRequest->getViewerCircle($currentUser);
	}


	/**
	 * @param CurrentUser $currentUser
	 *
	 * @return Membership[]
	 */
	public function generateMemberships(CurrentUser $currentUser): array {
		$circles = $this->circleRequest->getCircles(null, $currentUser);
		$memberships = [];
		foreach ($circles as $circle) {
			$viewer = $circle->getViewer();
			if (!$viewer->isMember()) {
				continue;
			}

			$viewer = $circle->getViewer();
			$memberships[] = new Membership(
				$viewer->getId(), $circle->getId(), $currentUser->getId(), $viewer->getLevel()
			);

//			$newUser = new CurrentUser($circle->getId(), Member::TYPE_CIRCLE, '');
//			$circles = $this->circleRequest->getCircles(null, $currentUser);
		}

		return $memberships;
	}


	/**
	 * @param CurrentUser|null $currentUser
	 */
	public function updateMemberships(?CurrentUser $currentUser = null) {
		if (is_null($currentUser)) {
			$currentUser = $this->getCurrentUser();
		} else {
			$currentUser->setMemberships($this->membershipRequest->getMemberships($currentUser));
		}

		if (is_null($currentUser)) {
			return;
		}

		$last = $this->generateMemberships($currentUser);

		echo 'known: ' . json_encode($currentUser->getMemberships()) . "\n";
		echo 'last: ' . json_encode($last) . "\n";

//
//		$circles = $this->circleRequest->getCircles(null, $viewer);
//		foreach ($circles as $circle) {
//			$viewer = $circle->getViewer();
//			if (!$viewer->isMember()) {
//				continue;
//			}
//
//			echo 'new member: ' . json_encode($viewer) . "\n";
////			$this->currentUserService->updateMembership($circle);
//		}


	}


}

