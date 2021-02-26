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


use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Logger;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\MemberAdd;
use OCA\Circles\FederatedItems\MemberLevel;
use OCA\Circles\FederatedItems\MemberRemove;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;


/**
 * Class MemberService
 *
 * @package OCA\Circles\Service
 */
class MemberService {


	use TArrayTools;
	use TStringTools;
	use TNC21Logger;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

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
		CircleRequest $circleRequest, MemberRequest $memberRequest,
		FederatedUserService $federatedUserService, FederatedEventService $federatedEventService,
		RemoteStreamService $remoteStreamService
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
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
	 *
	 * @return Member
	 * @throws InitiatorNotFoundException
	 * @throws MemberLevelException
	 */
	public function getMember(string $memberId, string $circleId = ''): Member {
		$this->federatedUserService->mustHaveCurrentUser();

		try {
			$member =
				$this->memberRequest->getMember($memberId, $this->federatedUserService->getCurrentUser());
			if ($circleId !== '' && $member->getCircle()->getId() !== $circleId) {
				throw new MemberNotFoundException();
			}

			// TODO: useless ?
//			$circle = $this->circleRequest->getCircle(
//				$member->getCircleId(), $this->federatedUserService->getCurrentUser()
//			);

//			if (!$circle->getInitiator()->isMember()) {
//				throw new MemberLevelException();
//			}

			return $member;
		} catch (Exception $e) {
			$this->e($e, ['id' => $memberId, 'initiator' => $this->federatedUserService->getCurrentUser()]);
			throw new MemberLevelException('insufficient rights');
		}
	}


	/**
	 * @param string $circleId
	 *
	 * @return Member[]
	 * @throws InitiatorNotFoundException
	 */
	public function getMembers(string $circleId): array {
		$this->federatedUserService->mustHaveCurrentUser();

		return $this->memberRequest->getMembers(
			$circleId,
			$this->federatedUserService->getCurrentUser(),
			$this->federatedUserService->getRemoteInstance()
		);
	}


	/**
	 * @param string $circleId
	 * @param IFederatedUser $member
	 *
	 * @return SimpleDataStore
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws FederatedItemException
	 * @throws FederatedEventDSyncException
	 */
	public function addMember(string $circleId, IFederatedUser $member): SimpleDataStore {
		$this->federatedUserService->mustHaveCurrentUser();
		$circle = $this->circleRequest->getCircle($circleId, $this->federatedUserService->getCurrentUser());

		if ($member instanceof FederatedUser) {
			$tmp = new Member();
			$tmp->importFromIFederatedUser($member);
			$member = $tmp;
		}

		$event = new FederatedEvent(MemberAdd::class);
		$event->setCircle($circle);
		$event->setMember($member);

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $memberId
	 *
	 * @return SimpleDataStore
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws FederatedEventDSyncException
	 */
	public function removeMember(string $memberId): SimpleDataStore {
		$this->federatedUserService->mustHaveCurrentUser();
		$member = $this->memberRequest->getMember($memberId, $this->federatedUserService->getCurrentUser());

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
	 * @return SimpleDataStore
	 * @throws FederatedEventException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws FederatedItemException
	 * @throws FederatedEventDSyncException
	 */
	public function memberLevel(string $memberId, int $level): SimpleDataStore {
		$this->federatedUserService->mustHaveCurrentUser();
		$member = $this->memberRequest->getMember($memberId, $this->federatedUserService->getCurrentUser());

		$event = new FederatedEvent(MemberLevel::class);
		$event->setCircle($member->getCircle());
		$event->setMember($member);
		$event->setData(new SimpleDataStore(['level' => $level]));

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}

}

