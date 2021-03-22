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


use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;


/**
 * Class FederatedUserService
 *
 * @package OCA\Circles\Service
 */
class FederatedUserService {


	use TArrayTools;
	use TStringTools;
	use TNC22Logger;


	/** @var IUserSession */
	private $userSession;

	/** @var IUserManager */
	private $userManager;

	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var RemoteService */
	private $remoteService;

	/** @var ConfigService */
	private $configService;


	/** @var FederatedUser */
	private $currentUser = null;

	/** @var FederatedUser */
	private $currentApp = null;

	/** @var RemoteInstance */
	private $remoteInstance = null;

	/** @var bool */
	private $bypass = false;


	/**
	 * FederatedUserService constructor.
	 *
	 * @param IUserSession $userSession
	 * @param IUserManager $userManager
	 * @param MembershipRequest $membershipRequest
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param RemoteService $remoteService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserSession $userSession, IUserManager $userManager, MembershipRequest $membershipRequest,
		CircleRequest $circleRequest, MemberRequest $memberRequest, RemoteService $remoteService,
		ConfigService $configService
	) {
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->membershipRequest = $membershipRequest;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->remoteService = $remoteService;
		$this->configService = $configService;
	}


	/**
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	public function initCurrentUser() {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}

		$this->setLocalCurrentUser($user);
	}


	/**
	 * @param IUser|null $user
	 *
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	public function setLocalCurrentUser(?IUser $user): void {
		if ($user === null) {
			return;
		}

		$this->currentUser = $this->getLocalFederatedUser($user->getUID());
	}


	/**
	 * @param string $appId
	 *
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	public function setLocalCurrentApp(string $appId): void {
		$this->currentApp = $this->getAppInitiator($appId);
	}


	/**
	 * set a CurrentUser, based on a IFederatedUser.
	 * CurrentUser is mainly used to manage rights when requesting the database.
	 *
	 * @param IFederatedUser $federatedUser
	 *
	 * @throws FederatedUserException
	 */
	public function setCurrentUser(IFederatedUser $federatedUser): void {
		if (!($federatedUser instanceof FederatedUser)) {
			$tmp = new FederatedUser();
			$tmp->importFromIFederatedUser($federatedUser);
			$federatedUser = $tmp;
		}

		$this->confirmFederatedUser($federatedUser);

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
		return !is_null($this->currentUser);
	}

	/**
	 * @throws InitiatorNotFoundException
	 */
	public function mustHaveCurrentUser(): void {
		if ($this->bypass) {
			return;
		}
		if (!$this->hasCurrentUser() && !$this->hasRemoteInstance()) {
			throw new InitiatorNotFoundException('Invalid initiator');
		}
	}

	/**
	 * @param bool $bypass
	 */
	public function bypassCurrentUserCondition(bool $bypass): void {
		$this->bypass = $bypass;
	}


	/**
	 * @return FederatedUser|null
	 */
	public function getCurrentApp(): ?FederatedUser {
		return $this->currentApp;
	}

	/**
	 * @return bool
	 */
	public function hasCurrentApp(): bool {
		return !is_null($this->currentApp);
	}


	/**
	 * set a RemoteInstance, mostly from a remote request (RemoteController)
	 * Used to limit rights in some request in the local database.
	 *
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
	 * @return bool
	 */
	public function hasRemoteInstance(): bool {
		return !is_null($this->remoteInstance);
	}


	/**
	 * Get the full FederatedUser for a local user.
	 * Will generate the SingleId if none exist
	 *
	 * @param string $userId
	 *
	 * @return FederatedUser
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 * @throws FederatedUserException
	 */
	public function getLocalFederatedUser(string $userId): FederatedUser {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new FederatedUserNotFoundException('user ' . $userId . ' not found');
		}

		$federatedUser = new FederatedUser();
		$federatedUser->set($user->getUID());
		$this->fillSingleCircleId($federatedUser);

		return $federatedUser;
	}


	/**
	 * Get the full FederatedUser for a local user.
	 * Will generate the SingleId if none exist
	 *
	 * @param string $appId
	 *
	 * @return FederatedUser
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 * @throws FederatedUserException
	 */
	public function getAppInitiator(string $appId): FederatedUser {
		$federatedUser = new FederatedUser();
		$federatedUser->set($appId, '', Member::TYPE_APP);
		$this->fillSingleCircleId($federatedUser);

		return $federatedUser;
	}


	/**
	 * some ./occ commands allows to add an Initiator, or force the PoV from the local circles' owner
	 *
	 * TODO: manage non-user type ?
	 *
	 * @param string $userId
	 * @param string $circleId
	 * @param bool $bypass
	 *
	 * @throws CircleNotFoundException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	public function commandLineInitiator(string $userId, string $circleId = '', bool $bypass = false): void {
		if ($userId !== '') {
			$this->setCurrentUser($this->getFederatedUser($userId));

			return;
		}

		if ($circleId !== '') {
			$localCircle = $this->circleRequest->getCircle($circleId, null, null, 0);
			if ($this->configService->isLocalInstance($localCircle->getInstance())) {
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
	 * Works like getFederatedUser, but returns a member.
	 * Allow to specify a level: handle@instance,level
	 *
	 * Used for filters when searching for Circles
	 * TODO: Used outside of ./occ circles:manage:list ?
	 *
	 * @param string $userId
	 * @param int $level
	 *
	 * @return Member
	 * @throws CircleNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidItemException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	public function getFederatedMember(string $userId, int $level = Member::LEVEL_MEMBER): Member {
		$userId = trim($userId, ',');
		if (strpos($userId, ',') !== false) {
			list($userId, $level) = explode(',', $userId);
		}

		$federatedUser = $this->getFederatedUser($userId);
		$member = new Member();
		$member->importFromIFederatedUser($federatedUser);
		$member->setLevel((int)$level);

		return $member;
	}


	/**
	 * get a valid FederatedUser, based on the federatedId (userId@instance) its the type.
	 * If instance is local, get the local valid FederatedUser
	 * If instance is not local, get the remote valid FederatedUser
	 *
	 * @param string $federatedId
	 * @param int $userType
	 *
	 * @return FederatedUser
	 * @throws CircleNotFoundException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	public function getFederatedUser(string $federatedId, int $userType = Member::TYPE_USER): FederatedUser {
		list($singleId, $instance) = $this->extractIdAndInstance($federatedId);
		switch ($userType) {
			case Member::TYPE_SINGLE:
				return $this->getFederatedUser_SingleId($singleId, $instance);
			case Member::TYPE_USER:
				return $this->getFederatedUser_User($singleId, $instance);
		}

		throw new UserTypeNotFoundException();
	}


	/**
	 * Generate a FederatedUser based on local data.
	 * WARNING: There is no confirmation that the returned FederatedUser exists or is valid at this point.
	 * Use getFederatedUser() instead if a valid and confirmed FederatedUser is needed.
	 *
	 * if $federatedId is a known SingleId, will returns data from the local database.
	 * if $federatedId is a local username, will returns data from the local database.
	 * Otherwise, the FederatedUser will not contains a SingleId.
	 *
	 * @param string $federatedId
	 * @param int $type
	 *
	 * @return FederatedUser
	 */
	public function generateFederatedUser(string $federatedId, int $type = 0): FederatedUser {
		try {
			return $this->getFederatedUser($federatedId, $type);
		} catch (Exception $e) {
		}

		list($userId, $instance) = $this->extractIdAndInstance($federatedId);
		$federatedUser = new FederatedUser();
		$federatedUser->set($userId, $instance, $type);

		return $federatedUser;
	}


	/**
	 * @param string $userId
	 * @param string $instance
	 *
	 * @return FederatedUser
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws FederatedItemException
	 */
	private function getFederatedUser_User(string $userId, string $instance): FederatedUser {
		if ($this->configService->isLocalInstance($instance)) {
			return $this->getLocalFederatedUser($userId);
		} else {
			$federatedUser =
				$this->remoteService->getFederatedUserFromInstance($userId, $instance, Member::TYPE_USER);
			$this->confirmLocalSingleId($federatedUser);

			return $federatedUser;
		}
	}


	/**
	 * @param string $singleId
	 * @param string $instance
	 *
	 * @return FederatedUser
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws MemberNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function getFederatedUser_SingleId(string $singleId, string $instance): FederatedUser {
		if (strlen($singleId) !== ManagedModel::ID_LENGTH) {
			throw new MemberNotFoundException();
		}

		if ($this->configService->isLocalInstance($instance)) {
			return $this->circleRequest->getFederatedUserBySingleId($singleId);
		} else {
			$federatedUser =
				$this->remoteService->getFederatedUserFromInstance($singleId, $instance, Member::TYPE_SINGLE);
			$this->confirmLocalSingleId($federatedUser);

			return $federatedUser;
		}
	}





//	/**
//	 * // TODO: do we need to have this working on remote instance ?
//	 *
//	 * @param string $circleId
//	 * @param string $instance
//	 *
//	 * @return FederatedUser
//	 * @throws CircleNotFoundException
//	 * @throws FederatedUserException
//	 * @throws FederatedUserNotFoundException
//	 * @throws OwnerNotFoundException
//	 * @throws RemoteInstanceException
//	 * @throws RemoteNotFoundException
//	 * @throws RemoteResourceNotFoundException
//	 * @throws UnknownRemoteException
//	 */
//	private function getFederatedUser_Circle(string $circleId, string $instance): FederatedUser {
//		// TODO: check remote instance for existing circle
//		if ($this->configService->isLocalInstance($instance)) {
//			$circle = $this->circleRequest->getCircle($circleId);
//			$federatedUser = new FederatedUser();
//			$federatedUser->set($circleId, $circle->getInstance(), Member::TYPE_CIRCLE);
//			$federatedUser->setSingleId($circleId);
//		} else {
//			$federatedUser =
//				$this->remoteService->getFederatedUserFromInstance($circleId, $instance, Member::TYPE_CIRCLE);
//			$this->confirmLocalSingleId($federatedUser);
//		}
//
//		return $federatedUser;
//	}


	/**
	 * extract userID and instance from a federatedId
	 *
	 * @param string $federatedId
	 *
	 * @return array
	 */
	private function extractIdAndInstance(string $federatedId): array {
		$federatedId = trim($federatedId, '@');
		if (strpos($federatedId, '@') === false) {
			$userId = $federatedId;
			$instance = $this->configService->getFrontalInstance();
		} else {
			list($userId, $instance) = explode('@', $federatedId);
		}

		return [$userId, $instance];
	}


	/**
	 * @param FederatedUser $federatedUser
	 *
	 * @throws FederatedUserException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	private function fillSingleCircleId(FederatedUser $federatedUser): void {
		if ($federatedUser->getSingleId() !== '') {
			return;
		}

		$circle = $this->getSingleCircle($federatedUser);
		$federatedUser->setSingleId($circle->getId());
		$circle->setOwner(null);
		$federatedUser->setBasedOn($circle);
	}


	/**
	 * get the Single Circle from a local user
	 *
	 * @param FederatedUser $federatedUser
	 *
	 * @return Circle
	 * @throws InvalidIdException
	 * @throws FederatedUserException
	 * @throws SingleCircleNotFoundException
	 */
	private function getSingleCircle(FederatedUser $federatedUser): Circle {
		if (!$this->configService->isLocalInstance($federatedUser->getInstance())) {
			throw new FederatedUserException('FederatedUser must be local');
		}

		try {
			return $this->circleRequest->getSingleCircle($federatedUser);
		} catch (SingleCircleNotFoundException $e) {
			$circle = new Circle();
			$id = $this->token(ManagedModel::ID_LENGTH);

			$prefix = ($federatedUser->getUserType() === Member::TYPE_APP) ? 'app' : 'user';
			$circle->setName($prefix . ':' . $federatedUser->getUserId() . ':' . $id)
				   ->setId($id)
			->setSource($federatedUser->getUserType());

			if ($federatedUser->getUserType() === Member::TYPE_APP) {
				$circle->setConfig(Circle::CFG_SINGLE | Circle::CFG_ROOT);
			} else {
				$circle->setConfig(Circle::CFG_SINGLE);
			}
			$this->circleRequest->save($circle);

			$owner = new Member();
			$owner->importFromIFederatedUser($federatedUser);
			$owner->setLevel(Member::LEVEL_OWNER)
				  ->setCircleId($id)
				  ->setSingleId($id)
				  ->setId($id)
				  ->setDisplayName($owner->getUserId())
				  ->setStatus('Member');
			$this->memberRequest->save($owner);
		}

		return $this->circleRequest->getSingleCircle($federatedUser);
	}


	/**
	 * Confirm that all field of a FederatedUser are filled.
	 *
	 * @param FederatedUser $federatedUser
	 *
	 * @throws FederatedUserException
	 */
	private function confirmFederatedUser(FederatedUser $federatedUser): void {
		if ($federatedUser->getUserId() === ''
			|| $federatedUser->getSingleId() === ''
			|| $federatedUser->getUserType() === 0
			|| $federatedUser->getInstance() === '') {
			$this->debug('FederatedUser is not empty', ['federatedUser' => $federatedUser]);
			throw new FederatedUserException('FederatedUser is not complete');
		}
	}

	/**
	 * Confirm that the singleId of a FederatedUser is unique and not used to any other member of the
	 * database.
	 *
	 * @param FederatedUser $federatedUser
	 *
	 * @throws FederatedUserException
	 */
	public function confirmLocalSingleId(FederatedUser $federatedUser): void {
		$members = $this->memberRequest->getMembersBySingleId($federatedUser->getSingleId());

		foreach ($members as $member) {
			if (!$federatedUser->compareWith($member)) {
				$this->debug(
					'uniqueness of SingleId could not be confirmed',
					['federatedUser' => $federatedUser, 'localMember' => $member]
				);
				throw new FederatedUserException('uniqueness of SingleId could not be confirmed');
			}
		}
	}


//	/**
//	 * @param FederatedUser $federatedUser
//	 *
//	 * @return Membership[]
//	 */
//	public function generateMemberships(FederatedUser $federatedUser): array {
//		$circles = $this->circleRequest->getCircles(null, $federatedUser);
//		$memberships = [];
//		foreach ($circles as $circle) {
//			$initiator = $circle->getInitiator();
//			if (!$initiator->isMember()) {
//				continue;
//			}
//
//			$memberships[] = new Membership(
//				$initiator->getId(), $circle->getId(), $federatedUser->getSingleId(), $initiator->getLevel()
//			);
//
////			$newUser = new CurrentUser($circle->getId(), Member::TYPE_CIRCLE, '');
////			$circles = $this->circleRequest->getCircles(null, $currentUser);
//		}
//
//		return $memberships;
//	}
//
//
//	/**
//	 * @param FederatedUser|null $federatedUser
//	 */
//	public function updateMemberships(?FederatedUser $federatedUser = null) {
//		if (is_null($federatedUser)) {
//			$federatedUser = $this->getCurrentUser();
//		} else {
//			$federatedUser->setMemberships($this->membershipRequest->getMemberships($federatedUser));
//		}
//
//		if (is_null($federatedUser)) {
//			return;
//		}
//
//		$last = $this->generateMemberships($federatedUser);
//
//		echo 'known: ' . json_encode($federatedUser->getMemberships()) . "\n";
//		echo 'last: ' . json_encode($last) . "\n";
//
////
////		$circles = $this->circleRequest->getCircles(null, $viewer);
////		foreach ($circles as $circle) {
////			$viewer = $circle->getViewer();
////			if (!$viewer->isMember()) {
////				continue;
////			}
////
////			echo 'new member: ' . json_encode($viewer) . "\n";
//////			$this->federatedUserService->updateMembership($circle);
////		}
//
//
//	}


}

