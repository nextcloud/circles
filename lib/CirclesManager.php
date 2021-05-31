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
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Model\Circle;
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


	/** @var IUserSession */
	private $userSession;

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
		IUserSession $userSession,
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		MemberService $memberService,
		CirclesQueryHelper $circlesQueryHelper
	) {
		$this->userSession = $userSession;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->circlesQueryHelper = $circlesQueryHelper;
	}


	/**
	 * WIP
	 *
	 * @return Circle
	 */
//	public function create(): Circle {
//	}


	/**
	 * WIP
	 *
	 * returns Circles available to Current User
	 *
	 * @return Circle[]
	 * @throws InitiatorNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
//	public function getCircles(bool $asMember = false): array {
//		$this->federatedUserService->initCurrentUser();
//		$this->circleService->getCircles();
//	}


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
	 * @param string $singleId
	 *
	 * @return Circle
	 */
//	public function getCircle(string $singleId): Circle {
//
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
//	}


	/**
	 * @return IFederatedUser
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	public function getCurrentFederatedUser(): IFederatedUser {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new FederatedUserNotFoundException('current user session not found');
		}

		return $this->federatedUserService->getLocalFederatedUser($user->getUID());
	}


	/**
	 * @param string $federatedId
	 * @param int $type
	 *
	 * @return IFederatedUser
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
	public function getFederatedUser(string $federatedId, int $type = Member::TYPE_SINGLE): IFederatedUser {
		return $this->federatedUserService->getFederatedUser($federatedId, $type);
	}


	/**
	 * @return CirclesQueryHelper
	 */
	public function getQueryHelper(): CirclesQueryHelper {
		return $this->circlesQueryHelper;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function extractCircle(array $data, string $prefix = ''): Circle {
		$circle = new Circle();
		$circle->importFromDatabase($data, $prefix);

		return $circle;
	}

}

