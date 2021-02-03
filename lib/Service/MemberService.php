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
use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\FederatedItems\MemberAdd;
use OCA\Circles\FederatedItems\MemberLevel;
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


	/**
	 * MemberService constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param FederatedEventService $federatedEventService
	 */
	public function __construct(
		CircleRequest $circleRequest, MemberRequest $memberRequest,
		FederatedUserService $federatedUserService,
		FederatedEventService $federatedEventService
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->federatedEventService = $federatedEventService;
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
	 *
	 * @return Member
	 * @throws InitiatorNotFoundException
	 * @throws MemberLevelException
	 */
	public function getMember(string $memberId): Member {
		$this->federatedUserService->mustHaveCurrentUser();

		try {
			$member = $this->memberRequest->getMember($memberId);
			$circle = $this->circleRequest->getCircle(
				$member->getCircleId(), $this->federatedUserService->getCurrentUser()
			);
			if (!$circle->getInitiator()->isMember()) {
				throw new MemberLevelException();
			}

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
	 */
	public function getMembers(string $circleId): array {
		return $this->memberRequest->getMembers($circleId);
	}


	/**
	 * @param string $circleId
	 * @param IFederatedUser $member
	 *
	 * @throws CircleNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws FederatedEventException
	 * @throws InitiatorNotFoundException
	 * @throws InitiatorNotConfirmedException
	 */
	public function addMember(string $circleId, IFederatedUser $member) {
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
	}


	/**
	 * @param string $circleId
	 * @param string $memberId
	 * @param int $level
	 *
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws \OCA\Circles\Exceptions\RemoteNotFoundException
	 * @throws \OCA\Circles\Exceptions\RemoteResourceNotFoundException
	 * @throws \OCA\Circles\Exceptions\UnknownRemoteException
	 * @throws \daita\MySmallPhpTools\Exceptions\RequestNetworkException
	 * @throws \daita\MySmallPhpTools\Exceptions\SignatoryException
	 * @throws MemberNotFoundException
	 */
	public function memberLevel(string $memberId, int $level): void {
		$this->federatedUserService->mustHaveCurrentUser();

		$member = $this->memberRequest->getMember($memberId, $this->federatedUserService->getCurrentUser());
		echo json_encode($member, JSON_PRETTY_PRINT) . "\n";
		$event = new FederatedEvent(MemberLevel::class);
		$event->setCircle($member->getCircle());
		$event->setMember($member);

		$this->federatedEventService->newEvent($event);

	}


	/**
	 * @param string $levelString
	 *
	 * @return int
	 * @throws MemberLevelException
	 */
	public function parseLevelString(string $levelString): int {
		$levelString = ucfirst(strtolower($levelString));
		$level = array_search($levelString, Member::$DEF_LEVEL);

		if (!$level) {
			throw new MemberLevelException(
				'Available levels: ' . implode(', ', array_values(Member::$DEF_LEVEL))
			);
		}

		return (int)$level;
	}


}

