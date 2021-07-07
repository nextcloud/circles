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


namespace OCA\Circles;

use ArtificialOwl\MySmallPhpTools\Exceptions\InvalidItemException;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCP\IUserSession;

/**
 * Class CirclesManager
 *
 * @package OCA\Circles
 */
class CirclesManager {


	/** @var CirclesQueryHelper */
	private $circlesQueryHelper;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;


	/**
	 * CirclesManager constructor.
	 *
	 * @param IUserSession $userSession
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param CirclesQueryHelper $circlesQueryHelper
	 */
	public function __construct(
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		MemberService $memberService,
		CirclesQueryHelper $circlesQueryHelper
	) {
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->circlesQueryHelper = $circlesQueryHelper;
	}


	/**
	 * @param string $federatedId
	 * @param int $type
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
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	public function getFederatedUser(string $federatedId, int $type = Member::TYPE_SINGLE): FederatedUser {
		return $this->federatedUserService->getFederatedUser($federatedId, $type);
	}


	/**
	 * @throws FederatedUserNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 * @throws InvalidIdException
	 * @throws FederatedUserException
	 */
	public function startSession(?FederatedUser $federatedUser = null): void {
		if (is_null($federatedUser)) {
			$this->federatedUserService->initCurrentUser();
		} else {
			$this->federatedUserService->setCurrentUser($federatedUser);
		}
	}

	/**
	 *
	 */
	public function startSuperSession(): void {
		$this->federatedUserService->unsetCurrentUser();
		$this->federatedUserService->bypassCurrentUserCondition(true);
	}


	/**
	 * $userId - userId to emulate as initiator (can be empty)
	 * $userType - specify if userIs not a singleId
	 * $circleId - if no userId specified, will use the owner of the Circle as initiator
	 *
	 * @param string $userId
	 * @param int $userType
	 * @param string $circleId
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
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	public function startOccSession(
		string $userId,
		int $userType = Member::TYPE_SINGLE,
		string $circleId = ''
	): void {
		$this->federatedUserService->commandLineInitiator($userId, $userType, $circleId);
	}


	/**
	 *
	 */
	public function stopSession(): void {
		$this->federatedUserService->unsetCurrentUser();
		$this->federatedUserService->bypassCurrentUserCondition(false);
	}


	/**
	 * @return IFederatedUser
	 */
	public function getCurrentFederatedUser(): IFederatedUser {
		return $this->federatedUserService->getCurrentUser();
	}


	/**
	 * @return CirclesQueryHelper
	 */
	public function getQueryHelper(): CirclesQueryHelper {
		return $this->circlesQueryHelper;
	}


	/**
	 * @param string $name
	 * @param FederatedUser|null $owner
	 * @param bool $personal
	 * @param bool $local
	 *
	 * @return Circle
	 * @throws FederatedEventException
	 * @throws InitiatorNotConfirmedException
	 * @throws FederatedItemException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidItemException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function createCircle(
		string $name,
		?FederatedUser $owner = null,
		bool $personal = false,
		bool $local = false
	): Circle {
		$outcome = $this->circleService->create($name, $owner, $personal, $local);
		$circle = new Circle();
		$circle->import($outcome);

		return $circle;
	}


	/**
	 * @param string $singleId
	 *
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function destroyCircle(string $singleId): void {
		$this->circleService->destroy($singleId);
	}


	/**
	 * returns Circles available, based on current session
	 *
	 * @return Circle[]
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getCircles(): array {
		return $this->circleService->getCircles();
	}


	/**
	 * @param string $singleId
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getCircle(string $singleId): Circle {
		return $this->circleService->getCircle($singleId);
	}



	/**
	 * WIP
	 *
	 * @return Circle[]
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
//	public function getAllCircles(): array {
//		$this->federatedUserService->bypassCurrentUserCondition(true);
//		$this->circleService->getCircles();
//	}


	/**
	 * WIP
	 *
	 * @param string $circleId
	 * @param string $singleId
	 *
	 * @return Member
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws RequestBuilderException
	 */
//	public function getMember(string $circleId, string $singleId): Member {
//		$this->federatedUserService->bypassCurrentUserCondition(true);
//		$this->memberService->getMemberById($circleId, $singleId);
//	}


	/**
	 * WIP
	 *
	 * @param string $memberId
	 *
	 * @return Member
	 */
//	public function getMemberById(string $memberId): Member {
}
