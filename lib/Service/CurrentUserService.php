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
use OC\User\NoUserException;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Exceptions\ViewerNotFoundException;
use OCA\Circles\IMember;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\CurrentUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCP\IUserManager;


/**
 * Class ViewerService
 *
 * @package OCA\Circles\Service
 */
class CurrentUserService {


	use TArrayTools;
	use TStringTools;
	use TNC21Logger;


	/** @var IUserManager */
	private $userManager;

	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var ConfigService */
	private $configService;


	/** @var CurrentUser */
	private $currentUser = null;

	/** @var bool */
	private $bypass = false;


	/**
	 * ViewerService constructor.
	 *
	 * @param IUserManager $userManager
	 * @param MembershipRequest $membershipRequest
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager, MembershipRequest $membershipRequest, CircleRequest $circleRequest,
		MemberRequest $memberRequest, ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->membershipRequest = $membershipRequest;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->configService = $configService;
	}


	/**
	 * @param string $userId
	 *
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 */
	public function setLocalViewer(string $userId): void {
		$this->currentUser = $this->createLocalCurrentUser($userId);
	}

	/**
	 * @param IMember $currentUser
	 *
	 * @throws CircleNotFoundException
	 */
	public function setCurrentUser(IMember $currentUser): void {
		if ($currentUser instanceof Member) {
			$tmp = new CurrentUser();
			$tmp->importFromIMember($currentUser);
			$currentUser = $tmp;
		}

//		if ($currentUser->getInstance() === '') {
//			$currentUser->setInstance($this->configService->getLocalInstance());
//		}

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
	 * @param string $userId
	 *
	 * @return CurrentUser
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 */
	public function createLocalCurrentUser(string $userId): CurrentUser {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new NoUserException('user ' . $userId . ' not found');
		}

		$currentUser = new CurrentUser($user->getUID());
		$this->fillSingleCircleId($currentUser);

		return $currentUser;
	}


	/**
	 * @param string $userId
	 * @param int $userType
	 *
	 * @return CurrentUser
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 * @throws UserTypeNotFoundException
	 */
	public function createCurrentUser(string $userId, int $userType = Member::TYPE_USER): CurrentUser {
		switch ($userType) {
			case Member::TYPE_USER:
				return $this->createCurrentUserTypeUser($userId);
		}

		throw new UserTypeNotFoundException();
	}

	/**
	 * @param string $userId
	 *
	 * @return CurrentUser
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 */
	public function createCurrentUserTypeUser(string $userId): CurrentUser {
		$userId = trim($userId, '@');
		if (strpos($userId, '@') === false) {
			$instance = $this->configService->getLocalInstance();
		} else {
			list($userId, $instance) = explode('@', $userId);
		}

		if ($this->configService->isLocalInstance($instance)) {
			return $this->createLocalCurrentUser($userId);
		} else {
			return new CurrentUser($userId, $instance, Member::TYPE_USER);
		}
	}




	/**
	 * some ./occ commands allows to add a Viewer
	 *
	 * @param string $userId
	 * @param bool $bypass
	 *
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 */
	public function commandLineViewer(string $userId, bool $bypass = false) {
		if ($userId !== '') {
			$currentUser = $this->createCurrentUserTypeUser($userId);
			$this->setCurrentUser($currentUser);
		} elseif ($bypass) {
			$this->bypassCurrentUserCondition(true);
		}
	}


	/**
	 * TODO: Is it needed outside of CirclesList ?
	 *
	 * @param string $userId
	 * @param int $level
	 *
	 * @return Member
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 */
	public function createFilterMember(string $userId, int $level = Member::LEVEL_MEMBER): Member {
		$userId = trim($userId, ',');
		if (strpos($userId, ',') !== false) {
			list($userId, $level) = explode(',', $userId);
		}

		$currentUser = $this->createCurrentUserTypeUser($userId);
		$member = new Member();
		$member->importFromIMember($currentUser)
			   ->setLevel((int)$level);

		return $member;
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

		// only if currentUser is from LocalInstance
		if ($this->configService->isLocalInstance($currentUser->getInstance())) {
			$circle = $this->getSingleCircle($currentUser);
			$currentUser->setId($circle->getId());
		}
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
			$owner->importFromIMember($currentUser)
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

