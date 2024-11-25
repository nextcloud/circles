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
use OCA\Circles\Exceptions\ContactAddressBookNotFoundException;
use OCA\Circles\Exceptions\ContactFormatException;
use OCA\Circles\Exceptions\ContactNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
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
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Probes\DataProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;

/**
 * Class CirclesManager
 *
 * @package OCA\Circles
 */
class CirclesManager {
	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var MembershipService */
	private $membershipService;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesQueryHelper */
	private $circlesQueryHelper;

	private bool $forceSync = false;

	/**
	 * CirclesManager constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param MembershipService $membershipService
	 * @param ConfigService $configService
	 * @param CirclesQueryHelper $circlesQueryHelper
	 */
	public function __construct(
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		MemberService $memberService,
		MembershipService $membershipService,
		ConfigService $configService,
		CirclesQueryHelper $circlesQueryHelper
	) {
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->membershipService = $membershipService;
		$this->configService = $configService;
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
	 * @param string $userId
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
	public function getLocalFederatedUser(string $userId): FederatedUser {
		return $this->getFederatedUser($userId, Member::TYPE_USER);
	}


	/**
	 * @throws FederatedUserNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 * @throws InvalidIdException
	 * @throws FederatedUserException
	 */
	public function startSession(?FederatedUser $federatedUser = null, bool $forceSync = false): void {
		$this->forceSync = $forceSync;
		if (is_null($federatedUser)) {
			$this->federatedUserService->initCurrentUser();
		} else {
			$this->federatedUserService->setCurrentUser($federatedUser);
		}
	}

	/**
	 *
	 */
	public function startSuperSession(bool $forceSync = false): void {
		$this->forceSync = $forceSync;
		$this->federatedUserService->unsetCurrentUser();
		$this->federatedUserService->bypassCurrentUserCondition(true);
	}


	/**
	 * @param string $appId
	 * @param int $appSerial
	 *
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	public function startAppSession(string $appId, int $appSerial = Member::APP_DEFAULT): void {
		$this->federatedUserService->setLocalCurrentApp($appId, $appSerial);
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
		$this->forceSync = false;
	}


	/**
	 * @return IFederatedUser
	 * @throws FederatedUserNotFoundException
	 */
	public function getCurrentFederatedUser(): IFederatedUser {
		$current = $this->federatedUserService->getCurrentUser();
		if (is_null($current)) {
			throw new FederatedUserNotFoundException();
		}

		return $current;
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
		$this->circleService->destroy($singleId, $this->forceSync);
	}


	/**
	 * WARNING: This method is not using Cached Memberships meaning that the request can be heavy and should
	 * only be used if probeCircles() does not fit your need.
	 *
	 * Always prefer probeCircles();
	 *
	 * returns available Circles to the current session.
	 *
	 * @see probeCircles()
	 *
	 * @return Circle[]
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getCircles(?CircleProbe $probe = null, bool $refreshCache = false): array {
		if (is_null($probe)) {
			$probe = new CircleProbe();
			$probe->filterHiddenCircles()
				  ->filterBackendCircles();
		}

		return $this->circleService->getCircles($probe, !$refreshCache);
	}


	/**
	 * @param string $singleId
	 * @param CircleProbe|null $probe
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getCircle(string $singleId, ?CircleProbe $probe = null): Circle {
		return $this->circleService->getCircle($singleId, $probe);
	}


	/**
	 * @param Circle $circle
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
	public function updateConfig(Circle $circle): void {
		$this->circleService->updateConfig($circle->getSingleId(), $circle->getConfig());
	}


	/**
	 * @param string $circleId
	 * @param bool $enabled
	 *
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function flagAsAppManaged(string $circleId, bool $enabled = true): void {
		$this->federatedUserService->confirmSuperSession();
		$this->federatedUserService->setOwnerAsCurrentUser($circleId);

		$probe = new CircleProbe();
		$probe->includeSystemCircles();

		$localCircle = $this->circleService->getCircle($circleId, $probe);
		if (!$this->configService->isLocalInstance($localCircle->getInstance())) {
			throw new CircleNotFoundException('This Circle is not managed from this instance');
		}

		$config = $localCircle->getConfig();
		if ($enabled) {
			$config |= Circle::CFG_APP;
		} else {
			$config &= ~Circle::CFG_APP;
		}

		$this->circleService->updateConfig($circleId, $config);
	}


	/**
	 * @param string $circleId
	 * @param FederatedUser $federatedUser
	 *
	 * @return Member
	 * @throws CircleNotFoundException
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidIdException
	 * @throws InvalidItemException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function addMember(string $circleId, FederatedUser $federatedUser): Member {
		$outcome = $this->memberService->addMember($circleId, $federatedUser, $this->forceSync);
		$member = new Member();
		$member->import($outcome);

		return $member;
	}


	/**
	 * @param string $memberId
	 * @param int $level
	 *
	 * @return Member
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidItemException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function levelMember(string $memberId, int $level): Member {
		$outcome = $this->memberService->memberLevel($memberId, $level);
		$member = new Member();
		$member->import($outcome);

		return $member;
	}


	/**
	 * @param string $memberId
	 *
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function removeMember(string $memberId): void {
		$this->memberService->removeMember($memberId, $this->forceSync);
	}


	/**
	 * @param string $circleId
	 * @param string $singleId
	 * @param bool $detailed
	 *
	 * @return Membership
	 * @throws MembershipNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getLink(string $circleId, string $singleId, bool $detailed = false): Membership {
		return $this->membershipService->getMembership($circleId, $singleId, $detailed);
	}


	/**
	 * @param IEntity $circle
	 *
	 * @return string
	 */
	public function getDefinition(IEntity $circle): string {
		return $this->circleService->getDefinition($circle);
	}


	/**
	 * Returns data about Circles based on cached Memberships.
	 * Meaning that only Circles the current user is a member will be returned.
	 *
	 * CircleProbe is used to filter Circles to be returned by the method.
	 * DataProbe is used to add details to returned Circles.
	 *
	 * @param CircleProbe|null $circleProbe
	 * @param DataProbe|null $dataProbe
	 *
	 * @return array
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function probeCircles(?CircleProbe $circleProbe = null, ?DataProbe $dataProbe = null): array {
		if (is_null($circleProbe)) {
			$circleProbe = new CircleProbe();
			$circleProbe->filterHiddenCircles()
						->filterBackendCircles();
		}

		return $this->circleService->probeCircles($circleProbe, $dataProbe);
	}


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
