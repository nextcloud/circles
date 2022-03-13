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


namespace OCA\Circles\FederatedItems;

use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use Exception;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\MembersLimitException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemInitiatorMembershipNotRequired;
use OCA\Circles\IFederatedItemMemberCheckNotRequired;
use OCA\Circles\IFederatedItemMemberOptional;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\StatusCode;
use OCP\IUserManager;

/**
 * Class CircleJoin
 *
 * @package OCA\Circles\GlobalScale
 */
class CircleJoin implements
	IFederatedItem,
	IFederatedItemInitiatorMembershipNotRequired,
	IFederatedItemAsyncProcess,
	IFederatedItemHighSeverity,
	IFederatedItemMemberCheckNotRequired,
	IFederatedItemMemberOptional {
	use TStringTools;
	use TNCLogger;
	use TDeserialize;


	/** @var IUserManager */
	private $userManager;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var MembershipService */
	private $membershipService;

	/** @var EventService */
	private $eventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CircleJoin constructor.
	 *
	 * @param IUserManager $userManager
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param MembershipService $membershipService
	 * @param EventService $eventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		MemberRequest $memberRequest,
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		MemberService $memberService,
		MembershipService $membershipService,
		EventService $eventService,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->membershipService = $membershipService;
		$this->eventService = $eventService;
		$this->configService = $configService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemBadRequestException
	 * @throws MembersLimitException
	 * @throws RequestBuilderException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$initiator = $circle->getInitiator();

		$member = new Member();
		$member->importFromIFederatedUser($initiator);
		$member->setCircleId($circle->getSingleId());
		if ($initiator->hasInvitedBy()) {
			$member->setInvitedBy($initiator->getInvitedBy());
		}

		$this->manageMemberStatus($circle, $member);

		$this->circleService->confirmCircleNotFull($circle);

		$event->setMember($member)
			  ->setOutcome($this->serialize($member));

		return;

//
//
//		$federatedId = $member->getUserId() . '@' . $member->getInstance();
//		try {
//			$federatedUser =
//				$this->federatedUserService->getFederatedUser($federatedId, $member->getUserType());
//			throw new MemberNotFoundException(
//				ucfirst(Member::$DEF_TYPE[$member->getUserType()]) . ' \'%s\' not found',
//				['member' => $member->getUserId() . '@' . $member->getInstance()]
//			);
//		}

//		$member->importFromIFederatedUser($federatedUser);
//
//		try {
//			$knownMember = $this->memberRequest->searchMember($member);
//			// TODO: maybe member is requesting access
//			throw new MemberAlreadyExistsException(
//				ucfirst(Member::$DEF_TYPE[$member->getUserType()]) . ' %s is already a member',
//				['member' => $member->getUserId() . '@' . $member->getInstance()]
//			);
//		} catch (MemberNotFoundException $e) {
//		}

//		$member->setId($this->uuid(ManagedModel::ID_LENGTH));
//
//		// TODO: check Config on Circle to know if we set Level to 1 or just send an invitation
//		$member->setLevel(Member::LEVEL_MEMBER);
//		$member->setStatus(Member::STATUS_MEMBER);
//		$event->setDataOutcome(['member' => $member]);
//
//		// TODO: Managing cached name
//		//		$member->setCachedName($eventMember->getCachedName());
//		$this->circleService->confirmCircleNotFull($circle);
//
//		// TODO: check if it is a member or a mail or a circle and fix the returned message
//
//		return;


//		$member = $this->membersRequest->getFreshNewMember(
//			$circle->getUniqueId(), $ident, $eventMember->getType(), $eventMember->getInstance()
//		);
//		$member->hasToBeInviteAble()
//
//		$this->membersService->addMemberBasedOnItsType($circle, $member);
//
//		$password = '';
//		$sendPasswordByMail = false;
//		if ($this->configService->enforcePasswordProtection($circle)) {
//			if ($circle->getSetting('password_single_enabled') === 'true') {
//				$password = $circle->getPasswordSingle();
//			} else {
//				$sendPasswordByMail = true;
//				$password = $this->miscService->token(15);
//			}
//		}
//
//		$event->setData(
//			new SimpleDataStore(
//				[
//					'password'       => $password,
//					'passwordByMail' => $sendPasswordByMail
//				]
//			)
//		);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedUserException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws RemoteNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function manage(FederatedEvent $event): void {
		$member = $event->getMember();
		if (!$this->memberService->insertOrUpdate($member)) {
			return;
		}

		if ($member->getStatus() === Member::STATUS_REQUEST) {
			$this->eventService->memberRequesting($event);
		} else {
			$this->membershipService->onUpdate($member->getSingleId());
			$this->eventService->memberJoining($event);
		}

		$this->membershipService->updatePopulation($event->getCircle());
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$member = $event->getMember();
		if ($member->getStatus() === Member::STATUS_REQUEST) {
			$this->eventService->memberRequested($event, $results);
		} else {
			$this->eventService->memberJoined($event, $results);
		}
	}


	/**
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FederatedItemBadRequestException
	 * @throws RequestBuilderException
	 */
	private function manageMemberStatus(Circle $circle, Member $member) {
		try {
			$knownMember = $this->memberRequest->searchMember($member);
			if ($knownMember->getLevel() === Member::LEVEL_NONE) {
				switch ($knownMember->getStatus()) {
					case Member::STATUS_BLOCKED:
						throw new Exception('Blocked');

					case Member::STATUS_REQUEST:
						throw new MemberAlreadyExistsException(StatusCode::$CIRCLE_JOIN[123], 123);

					case Member::STATUS_INVITED:
						$member->setId($knownMember->getId());
						$member->setLevel(Member::LEVEL_MEMBER);
						$member->setStatus(Member::STATUS_MEMBER);

						return;
				}
			}

			throw new MemberAlreadyExistsException(StatusCode::$CIRCLE_JOIN[122], 122);
		} catch (MemberNotFoundException $e) {
			if (!$circle->isConfig(Circle::CFG_OPEN)) {
				throw new FederatedItemBadRequestException(StatusCode::$CIRCLE_JOIN[124], 124);
			}

			$member->setId($this->token(ManagedModel::ID_LENGTH));

			if ($circle->isConfig(Circle::CFG_REQUEST)) {
				$member->setStatus(Member::STATUS_REQUEST);
			} else {
				$member->setLevel(Member::LEVEL_MEMBER);
				$member->setStatus(Member::STATUS_MEMBER);
			}
		}
	}
}
