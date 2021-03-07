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


use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Logger;
use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\FederatedItemForbiddenException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemInitiatorMembershipNotRequired;
use OCA\Circles\IFederatedItemMemberOptional;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleEventService;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCP\IUserManager;


/**
 * Class CircleJoin
 *
 * @package OCA\Circles\GlobalScale
 */
class CircleJoin implements
	IFederatedItem,
	IFederatedItemInitiatorMembershipNotRequired,
	IFederatedItemMemberOptional {


	use TStringTools;
	use TNC21Logger;


	/** @var IUserManager */
	private $userManager;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var CircleService */
	private $circleService;

	/** @var CircleEventService */
	private $circleEventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CircleJoin constructor.
	 *
	 * @param IUserManager $userManager
	 * @param FederatedUserService $federatedUserService
	 * @param MemberRequest $memberRequest
	 * @param CircleService $circleService
	 * @param CircleEventService $circleEventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager, FederatedUserService $federatedUserService, MemberRequest $memberRequest,
		CircleService $circleService, CircleEventService $circleEventService, ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->federatedUserService = $federatedUserService;
		$this->memberRequest = $memberRequest;
		$this->circleService = $circleService;
		$this->circleEventService = $circleEventService;
		$this->configService = $configService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemBadRequestException
	 * @throws FederatedItemForbiddenException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$initiator = $circle->getInitiator();

		$initiatorHelper = new MemberHelper($initiator);
		$initiatorHelper->cannotBeMember();

		$member = new Member();
		$member->importFromIFederatedUser($initiator);

		try {
			$knownMember = $this->memberRequest->searchMember($member);
			// TODO: maybe member is already invited
			throw new MemberAlreadyExistsException(
				ucfirst(Member::$DEF_TYPE[$member->getUserType()]) . ' %s is already a member',
				['member' => $member->getUserId() . '@' . $member->getInstance()]
			);
		} catch (MemberNotFoundException $e) {

		}

		$member->setId($this->uuid(ManagedModel::ID_LENGTH));
		$member->setCircleId($circle->getId());

		// TODO: check Config on Circle to know if we set Level to 1 or just send a join request
		$member->setLevel(Member::LEVEL_MEMBER);
		$member->setStatus(Member::STATUS_MEMBER);
		$event->setOutcome(['member' => $member]);

		$event->setMember($member);

		// TODO: Managing cached name
		//		$member->setCachedName($eventMember->getCachedName());

		$this->circleService->confirmCircleNotFull($circle);

		// TODO: check if it is a member or a mail or a circle and fix the returned message

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
	 * @throws InvalidIdException
	 */
	public function manage(FederatedEvent $event): void {
		$member = $event->getMember();

		try {
			$this->memberRequest->getMember($member->getId());

			return;
		} catch (MemberNotFoundException $e) {
		}

		try {
			$federatedUser = new FederatedUser();
			$federatedUser->importFromIFederatedUser($member);
			$this->federatedUserService->confirmLocalSingleId($federatedUser);
		} catch (FederatedUserException $e) {
			$this->e($e, ['member' => $member]);

			return;
		}

		$this->memberRequest->save($member);

//		$this->circleEventService->onCircleJoined($event);

	}


	/**
	 * @param FederatedEvent[] $events
	 *
	 * @throws Exception
	 */
	public function result(array $events): void {
	}

}

