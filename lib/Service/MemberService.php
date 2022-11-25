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

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\ContactAddressBookNotFoundException;
use OCA\Circles\Exceptions\ContactFormatException;
use OCA\Circles\Exceptions\ContactNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
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
use OCA\Circles\FederatedItems\MassiveMemberAdd;
use OCA\Circles\FederatedItems\MemberLevel;
use OCA\Circles\FederatedItems\MemberRemove;
use OCA\Circles\FederatedItems\SingleMemberAdd;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\MemberProbe;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;

/**
 * Class MemberService
 *
 * @package OCA\Circles\Service
 */
class MemberService {
	use TArrayTools;
	use TStringTools;
	use TNCLogger;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var MembershipService */
	private $membershipService;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var RemoteStreamService */
	private $remoteStreamService;


	/**
	 * MemberService constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param FederatedEventService $federatedEventService
	 * @param RemoteStreamService $remoteStreamService
	 */
	public function __construct(
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		FederatedUserService $federatedUserService,
		MembershipService $membershipService,
		FederatedEventService $federatedEventService,
		RemoteStreamService $remoteStreamService
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->membershipService = $membershipService;
		$this->federatedEventService = $federatedEventService;
		$this->remoteStreamService = $remoteStreamService;
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
	 * @param string $memberId
	 * @param string $circleId
	 * @param bool $canBeVisitor
	 *
	 * @return Member
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getMemberById(
		string $memberId,
		string $circleId = '',
		?MemberProbe $probe = null
	): Member {
		$this->federatedUserService->mustHaveCurrentUser();

		$member = $this->memberRequest->getMemberById(
			$memberId,
			$this->federatedUserService->getCurrentUser(),
			$probe
		);

		if ($circleId !== '' && $member->getCircle()->getSingleId() !== $circleId) {
			throw new MemberNotFoundException();
		}

		return $member;
	}


	/**
	 * @param string $circleId
	 *
	 * @return Member[]
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getMembers(string $circleId): array {
		$this->federatedUserService->mustHaveCurrentUser();

		$probe = new MemberProbe();
		if ($this->federatedUserService->hasRemoteInstance()) {
			$probe->setFilterRemoteInstance($this->federatedUserService->getRemoteInstance());
		}
		$probe->initiatorAsDirectMember();
		$probe->mustBeMember();

		return $this->memberRequest->getMembers(
			$circleId,
			$this->federatedUserService->getCurrentUser(),
			$probe
		);
	}


	/**
	 * @param string $circleId
	 * @param FederatedUser $federatedUser
	 *
	 * @return array
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
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	public function addMember(string $circleId, FederatedUser $federatedUser): array {
		$this->federatedUserService->mustHaveCurrentUser();
		$circle = $this->circleRequest->getCircle($circleId, $this->federatedUserService->getCurrentUser());

		$member = new Member();
		$member->importFromIFederatedUser($federatedUser);

		$this->federatedUserService->setMemberPatron($member);

		$event = new FederatedEvent(SingleMemberAdd::class);
		$event->setCircle($circle);
		$event->setMember($member);

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 * @param IFederatedUser[] $members
	 *
	 * @return FederatedUser[]
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RemoteInstanceException
	 * @throws RequestBuilderException
	 */
	public function addMembers(string $circleId, array $federatedUsers): array {
		$this->federatedUserService->mustHaveCurrentUser();
		$circle = $this->circleRequest->getCircle($circleId, $this->federatedUserService->getCurrentUser());

		if ($this->federatedUserService->isInitiatedByOcc()) {
			$patron = $this->federatedUserService->getAppInitiator('occ', Member::APP_OCC);
		} else {
			$patron = $this->federatedUserService->getCurrentUser();
		}

		$members = array_map(
			function (FederatedUser $federatedUser) use ($patron) {
				$member = new Member();
				$member->importFromIFederatedUser($federatedUser);
				$member->setInvitedBy($patron);

				return $member;
			}, $federatedUsers
		);

		$event = new FederatedEvent(MassiveMemberAdd::class);
		$event->setCircle($circle);
		$event->setMembers($members);
		$event->setParams(new SimpleDataStore(['federatedUsers' => $members]));

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $memberId
	 *
	 * @return array
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RequestBuilderException
	 */
	public function removeMember(string $memberId): array {
		$this->federatedUserService->mustHaveCurrentUser();
		$member = $this->memberRequest->getMemberById(
			$memberId,
			$this->federatedUserService->getCurrentUser()
		);

		$event = new FederatedEvent(MemberRemove::class);
		$event->setCircle($member->getCircle());
		$event->setMember($member);

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}

	/**
	 * @param string $memberId
	 * @param int $level
	 *
	 * @return array
	 * @throws FederatedEventException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws FederatedItemException
	 * @throws RequestBuilderException
	 */
	public function memberLevel(string $memberId, int $level): array {
		$this->federatedUserService->mustHaveCurrentUser();
		$member =
			$this->memberRequest->getMemberById($memberId, $this->federatedUserService->getCurrentUser());

		$event = new FederatedEvent(MemberLevel::class);
		$event->setCircle($member->getCircle());
		$event->setMember($member);
		$event->setParams(new SimpleDataStore(['level' => $level]));

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param Member $member
	 *
	 * @return bool
	 * @throws InvalidIdException
	 * @throws RemoteNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function insertOrUpdate(Member $member): bool {
		try {
			$this->federatedUserService->confirmSingleIdUniqueness($member);

			$member->setNoteObj('invitedBy', $member->getInvitedBy());

			$this->memberRequest->insertOrUpdate($member);
			$this->membershipService->onUpdate($member->getSingleId());
		} catch (FederatedUserException $e) {
			$this->e($e);

			return false;
		}

		return true;
	}
}
