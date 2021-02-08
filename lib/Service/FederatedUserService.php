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
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCP\IUserManager;


/**
 * Class FederatedUserService
 *
 * @package OCA\Circles\Service
 */
class FederatedUserService {


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


	/** @var FederatedUser */
	private $currentUser = null;

	/** @var RemoteInstance */
	private $remoteInstance = null;

	/** @var bool */
	private $bypass = false;


	/**
	 * FederatedUserService constructor.
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
	public function setLocalInitiator(string $userId): void {
		$this->currentUser = $this->createLocalFederatedUser($userId);
	}

	/**
	 * @param IFederatedUser $federatedUser
	 *
	 * @throws CircleNotFoundException
	 */
	public function setCurrentUser(IFederatedUser $federatedUser): void {
		if (!($federatedUser instanceof FederatedUser)) {
			$tmp = new FederatedUser();
			$tmp->importFromIFederatedUser($federatedUser);
			$federatedUser = $tmp;
		}

		$this->fillSingleCircleId($federatedUser);
		$this->currentUser = $federatedUser;
	}

	/**
	 * @return FederatedUser|null
	 */
	public function getCurrentUser(): ?FederatedUser {
		return $this->currentUser;
	}

	/**
	 * @return bool
	 */
	public function hasCurrentUser(): bool {
		return ($this->currentUser !== null);
	}

	/**
	 * @throws InitiatorNotFoundException
	 */
	public function mustHaveCurrentUser(): void {
		if ($this->bypass) {
			return;
		}
		if (!$this->hasCurrentUser()) {
			throw new InitiatorNotFoundException();
		}
	}

	/**
	 * @param bool $bypass
	 */
	public function bypassCurrentUserCondition(bool $bypass): void {
		$this->bypass = $bypass;
	}


	/**
	 * @param RemoteInstance $remoteInstance
	 */
	public function setRemoteInstance(RemoteInstance $remoteInstance): void {
		$this->remoteInstance = $remoteInstance;
	}

	/**
	 * @return RemoteInstance|null
	 */
	public function getRemoteInstance(): ?RemoteInstance {
		return $this->remoteInstance;
	}


	/**
	 * @param string $userId
	 *
	 * @return FederatedUser
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 */
	public function createLocalFederatedUser(string $userId): FederatedUser {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new NoUserException('user ' . $userId . ' not found');
		}

		$federatedUser = new FederatedUser();
		$federatedUser->set($user->getUID());
		$this->fillSingleCircleId($federatedUser);

		return $federatedUser;
	}


	/**
	 * @param string $federatedId
	 * @param int $userType
	 *
	 * @return FederatedUser
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 * @throws UserTypeNotFoundException
	 */
	public function createFederatedUser(string $federatedId, int $userType = Member::TYPE_USER
	): FederatedUser {
		switch ($userType) {
			case Member::TYPE_USER:
				return $this->createFederatedUserTypeUser($federatedId);
		}

		throw new UserTypeNotFoundException();
	}

	/**
	 * @param string $userId
	 *
	 * @return FederatedUser
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 */
	public function createFederatedUserTypeUser(string $userId): FederatedUser {
		$userId = trim($userId, '@');
		if (strpos($userId, '@') === false) {
			$instance = $this->configService->getLocalInstance();
		} else {
			list($userId, $instance) = explode('@', $userId);
		}

		if ($this->configService->isLocalInstance($instance)) {
			return $this->createLocalFederatedUser($userId);
		} else {
			$federatedUser = new FederatedUser();
			$federatedUser->set($userId, $instance, Member::TYPE_USER);

			return $federatedUser;
		}
	}


	/**
	 * some ./occ commands allows to add an Initiator
	 * TODO: manage non-user type
	 *
	 * @param string $userId
	 * @param string $circleId
	 * @param bool $bypass
	 *
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 * @throws OwnerNotFoundException
	 */
	public function commandLineInitiator(string $userId, string $circleId = '', bool $bypass = false) {
		if ($userId !== '') {
			$this->setCurrentUser($this->createFederatedUserTypeUser($userId));

			return;
		}

		if ($circleId !== '') {
			$localCircle = $this->circleRequest->getCircle($circleId);
			if ($this->configService->isLocalInstance($localCircle->getInstance())) {
				// TODO: manage NO_OWNER circles
				$this->setCurrentUser($localCircle->getOwner());

				return;
			}
		}

		if (!$bypass) {
			throw new CircleNotFoundException(
				'This Circle is not managed from this instance, please use --initiator'
			);
		}

		$this->bypassCurrentUserCondition($bypass);
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

		$federatedUser = $this->createFederatedUserTypeUser($userId);
		$member = new Member();
		$member->importFromIFederatedUser($federatedUser);
		$member->setLevel((int)$level);

		return $member;
	}


	/**
	 * @param FederatedUser $federatedUser
	 *
	 * @throws CircleNotFoundException
	 */
	private function fillSingleCircleId(FederatedUser $federatedUser): void {
		if ($federatedUser->getSingleId() !== '') {
			return;
		}

		// only if currentUser is from LocalInstance
		if ($this->configService->isLocalInstance($federatedUser->getInstance())) {
			$circle = $this->getSingleCircle($federatedUser);
			$federatedUser->setSingleId($circle->getId());
		}
	}


	/**
	 * @param FederatedUser $federatedUser
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function getSingleCircle(FederatedUser $federatedUser): Circle {
		try {
			return $this->circleRequest->getInitiatorCircle($federatedUser);
		} catch (CircleNotFoundException $e) {
			$circle = new Circle();
			$id = $this->token(ManagedModel::ID_LENGTH);

			$circle->setName('single:' . $federatedUser->getUserId() . ':' . $id)
				   ->setId($id)
				   ->setConfig(Circle::CFG_SINGLE);
			$this->circleRequest->save($circle);

			$owner = new Member();
			$owner->importFromIFederatedUser($federatedUser);
			$owner->setLevel(Member::LEVEL_OWNER)
				  ->setCircleId($id)
				  ->setId($id)
				  ->setCachedName($owner->getUserId())
				  ->setStatus('Member');
			$this->memberRequest->save($owner);
		}

		return $this->circleRequest->getInitiatorCircle($federatedUser);
	}


	/**
	 * @param FederatedUser $federatedUser
	 *
	 * @return Membership[]
	 */
	public function generateMemberships(FederatedUser $federatedUser): array {
		$circles = $this->circleRequest->getCircles(null, $federatedUser);
		$memberships = [];
		foreach ($circles as $circle) {
			$initiator = $circle->getInitiator();
			if (!$initiator->isMember()) {
				continue;
			}

			$memberships[] = new Membership(
				$initiator->getId(), $circle->getId(), $federatedUser->getSingleId(), $initiator->getLevel()
			);

//			$newUser = new CurrentUser($circle->getId(), Member::TYPE_CIRCLE, '');
//			$circles = $this->circleRequest->getCircles(null, $currentUser);
		}

		return $memberships;
	}


	/**
	 * @param FederatedUser|null $federatedUser
	 */
	public function updateMemberships(?FederatedUser $federatedUser = null) {
		if (is_null($federatedUser)) {
			$federatedUser = $this->getCurrentUser();
		} else {
			$federatedUser->setMemberships($this->membershipRequest->getMemberships($federatedUser));
		}

		if (is_null($federatedUser)) {
			return;
		}

		$last = $this->generateMemberships($federatedUser);

		echo 'known: ' . json_encode($federatedUser->getMemberships()) . "\n";
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
////			$this->federatedUserService->updateMembership($circle);
//		}


	}

}

