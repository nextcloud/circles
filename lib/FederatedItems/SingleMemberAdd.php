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
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedItemNotFoundException;
use OCA\Circles\Exceptions\FederatedItemRemoteException;
use OCA\Circles\Exceptions\FederatedItemServerException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\MembersLimitException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberCheckNotRequired;
use OCA\Circles\IFederatedItemMemberRequired;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\StatusCode;
use OCP\IUserManager;

/**
 * Class SingleMemberAdd
 *
 * @package OCA\Circles\FederatedItems
 */
class SingleMemberAdd implements
	IFederatedItem,
	IFederatedItemAsyncProcess,
	IFederatedItemHighSeverity,
	IFederatedItemMemberRequired,
	IFederatedItemMemberCheckNotRequired {
	use TDeserialize;


	use TStringTools;
	use TNCLogger;


	/** @var IUserManager */
	protected $userManager;

	/** @var MemberRequest */
	protected $memberRequest;

	/** @var FederatedUserService */
	protected $federatedUserService;

	/** @var RemoteStreamService */
	protected $remoteStreamService;

	/** @var CircleService */
	protected $circleService;

	/** @var MemberService */
	protected $memberService;

	/** @var MembershipService */
	protected $membershipService;

	/** @var EventService */
	protected $eventService;

	/** @var ConfigService */
	protected $configService;


	/**
	 * SingleMemberAdd constructor.
	 *
	 * @param IUserManager $userManager
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param RemoteStreamService $remoteStreamService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param MembershipService $membershipService
	 * @param EventService $eventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		MemberRequest $memberRequest,
		FederatedUserService $federatedUserService,
		RemoteStreamService $remoteStreamService,
		CircleService $circleService,
		MemberService $memberService,
		MembershipService $membershipService,
		EventService $eventService,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->remoteStreamService = $remoteStreamService;
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
	 * @throws FederatedItemNotFoundException
	 * @throws FederatedItemServerException
	 * @throws FederatedItemRemoteException
	 * @throws FederatedItemException
	 * @throws RequestBuilderException
	 */
	public function verify(FederatedEvent $event): void {
		$member = $event->getMember();
		$circle = $event->getCircle();
		$initiator = $circle->getInitiator();

		$initiatorHelper = new MemberHelper($initiator);
		if (!$circle->isConfig(Circle::CFG_FRIEND)) {
			$initiatorHelper->mustBeModerator();
		}

		$member = $this->generateMember($event, $circle, $member);

		$event->setMembers([$member]);
		$event->setOutcome($this->serialize($member));

		$this->eventService->memberPreparing($event);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws InvalidIdException
	 * @throws RemoteNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function manage(FederatedEvent $event): void {
		$member = $event->getMember();
		if (!$this->memberService->insertOrUpdate($member)) {
			return;
		}

		if ($member->getStatus() === Member::STATUS_INVITED) {
			$this->eventService->memberInviting($event);
		} else {
			$this->eventService->memberAdding($event);
		}

		$this->membershipService->updatePopulation($event->getCircle());

//		//
//		// TODO: verifiez comment se passe le cached name sur un member_add
//		//
//		$cachedName = $member->getCachedName();
//		$password = $event->getData()
//						  ->g('password');
//
//		$shares = $this->generateUnknownSharesLinks($circle, $member, $password);
//		$result = [
//			'unknownShares' => $shares,
//			'cachedName'    => $cachedName
//		];
//
//		if ($member->getType() === DeprecatedMember::TYPE_CONTACT
//			&& $this->configService->isLocalInstance($member->getInstance())) {
//			$result['contact'] = $this->miscService->getInfosFromContact($member);
//		}
//
//		$event->setResult(new SimpleDataStore($result));
//		$this->eventsService->onMemberNew($circle, $member);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$member = $event->getMember();
		if ($member->getStatus() === Member::STATUS_INVITED) {
			$this->eventService->memberInvited($event, $results);
		} else {
			$this->eventService->memberAdded($event, $results);
		}

//		$password = $cachedName = '';
//		$circle = $member = null;
//		$links = [];
//		$recipients = [];
//		foreach ($events as $event) {
//			$data = $event->getData();
//			if ($data->gBool('passwordByMail') !== false) {
//				$password = $data->g('password');
//			}
//			$circle = $event->getDeprecatedCircle();
//			$member = $event->getMember();
//			$result = $event->getResult();
//			if ($result->g('cachedName') !== '') {
//				$cachedName = $result->g('cachedName');
//			}
//
//			$links = array_merge($links, $result->gArray('unknownShares'));
//			$contact = $result->gArray('contact');
//			if (!empty($contact)) {
//				$recipients = $contact['emails'];
//			}
//		}
//
//		if (empty($links) || $circle === null || $member === null) {
//			return;
//		}
//
//		if ($cachedName !== '') {
//			$member->setCachedName($cachedName);
//			$this->membersService->updateMember($member);
//		}
//
//		if ($member->getType() === DeprecatedMember::TYPE_MAIL
//			|| $member->getType() === DeprecatedMember::TYPE_CONTACT) {
//			if ($member->getType() === DeprecatedMember::TYPE_MAIL) {
//				$recipients = [$member->getUserId()];
//			}
//
//			foreach ($recipients as $recipient) {
//				$this->memberIsMailbox($circle, $recipient, $links, $password);
//			}
//		}
	}


	/**
	 * @param FederatedEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @return Member
	 * @throws CircleNotFoundException
	 * @throws FederatedItemBadRequestException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws MembersLimitException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 * @throws RequestBuilderException
	 */
	protected function generateMember(FederatedEvent $event, Circle $circle, Member $member): Member {
		try {
			if ($member->getSingleId() !== '') {
				$userId = $member->getSingleId() . '@' . $member->getInstance();
				$federatedUser = $this->federatedUserService->getFederatedUser($userId, Member::TYPE_SINGLE);
			} else {
				$userId = $member->getUserId() . '@' . $member->getInstance();
				$federatedUser = $this->federatedUserService->getFederatedUser(
					$userId,
					$member->getUserType()
				);
			}
		} catch (MemberNotFoundException $e) {
			throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[120], 120);
		}

		$allowedTypes = $this->configService->getAppValueInt(ConfigService::ALLOWED_TYPES);
		if ($federatedUser->getUserType() < Member::TYPE_APP
			&& ($allowedTypes & $federatedUser->getUserType()) === 0) {
			throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[132], 132);
		}

		if ($federatedUser->getBasedOn()->isConfig(Circle::CFG_ROOT)) {
			throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[125], 125);
		}

		if ($circle->isConfig(Circle::CFG_LOCAL)
			&& $federatedUser->getUserType() === Member::TYPE_CIRCLE
			&& !$federatedUser->getBasedOn()->isConfig(Circle::CFG_LOCAL)) {
			throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[131], 131);
		}

		if ($member->getSingleId() === $circle->getSingleId()) {
			throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[128], 128);
		}

		if (!$this->configService->isLocalInstance($member->getInstance())) {
			if ($circle->isConfig(Circle::CFG_LOCAL)) {
				throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[126], 126);
			}

			if (!$circle->isConfig(Circle::CFG_FEDERATED)) {
				$remoteInstance = $this->remoteStreamService->getCachedRemoteInstance($member->getInstance());
				if ($remoteInstance->getType() !== RemoteInstance::TYPE_GLOBALSCALE) {
					throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[127], 127);
				}
			}
		}

		$member->importFromIFederatedUser($federatedUser);
		$member->setCircleId($circle->getSingleId());
		$member->setCircle($circle);

		$this->confirmPatron($event, $member);
		$this->manageMemberStatus($circle, $member);

		$this->circleService->confirmCircleNotFull($circle);

		// The idea is that adding the member during the self::verify() will help during the broadcasting
		// of the event to Federated RemoteInstance for their first member.
		$this->memberRequest->insertOrUpdate($member);

		return $member;
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
			$member->setId($knownMember->getId());

			if ($knownMember->getLevel() === Member::LEVEL_NONE) {
				switch ($knownMember->getStatus()) {
					case Member::STATUS_BLOCKED:
						if ($circle->isConfig(Circle::CFG_INVITE)) {
							$member->setStatus(Member::STATUS_INVITED);
						}

						return;

					case Member::STATUS_REQUEST:
						$member->setLevel(Member::LEVEL_MEMBER);
						$member->setStatus(Member::STATUS_MEMBER);

						return;

					case Member::STATUS_INVITED:
						throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[123], 123);
				}
			}

			throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[122], 122);
		} catch (MemberNotFoundException $e) {
			$member->setId($this->token(ManagedModel::ID_LENGTH));

			if ($circle->isConfig(Circle::CFG_INVITE)
				&& $member->getUserType() !== Member::TYPE_MAIL
				&& $member->getUserType() !== Member::TYPE_CONTACT) {
				$member->setStatus(Member::STATUS_INVITED);
			} else {
				$member->setLevel(Member::LEVEL_MEMBER);
				$member->setStatus(Member::STATUS_MEMBER);
			}
		}
	}


	/**
	 * @param FederatedEvent $event
	 * @param Member $member
	 *
	 * @throws FederatedItemBadRequestException
	 * @throws FederatedUserException
	 * @throws RemoteNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	private function confirmPatron(FederatedEvent $event, Member $member): void {
		if (!$member->hasInvitedBy()) {
			throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[129], 129);
		}

		$patron = $member->getInvitedBy();
		if ($patron->getInstance() !== $event->getSender()) {
			throw new FederatedItemBadRequestException(StatusCode::$MEMBER_ADD[130], 130);
		}

		$this->federatedUserService->confirmSingleIdUniqueness($patron);
	}
}
