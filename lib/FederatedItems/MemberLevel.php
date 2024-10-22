<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems;

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberRequired;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Tools\Traits\TDeserialize;

/**
 * Class MemberLevel
 *
 * @package OCA\Circles\FederatedItems
 */
class MemberLevel implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemMemberRequired {
	use TDeserialize;


	/** @var MemberRequest */
	private $memberRequest;

	/** @var MembershipService */
	private $membershipService;

	/** @var EventService */
	private $eventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MemberAdd constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param MembershipService $membershipService
	 * @param EventService $eventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		MemberRequest $memberRequest,
		MembershipService $membershipService,
		EventService $eventService,
		ConfigService $configService,
	) {
		$this->memberRequest = $memberRequest;
		$this->membershipService = $membershipService;
		$this->eventService = $eventService;
		$this->configService = $configService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 * @throws FederatedItemBadRequestException
	 * @throws MemberLevelException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$member = $event->getMember();
		$initiator = $circle->getInitiator();
		$level = $event->getParams()->gInt('level');

		if ($circle->isConfig(Circle::CFG_SINGLE) || $circle->isConfig(Circle::CFG_PERSONAL)) {
			throw new FederatedItemBadRequestException('This level cannot be edited');
		}

		if ($level === 0) {
			// TODO check all level
			throw new FederatedItemBadRequestException('invalid level');
		}

		if ($member->getLevel() === $level) {
			throw new FederatedItemBadRequestException('This member already have the selected level');
		}

		$initiatorHelper = new MemberHelper($initiator);
		$initiatorHelper->mustBeModerator();

		if ($level === Member::LEVEL_OWNER) {
			$this->verifySwitchOwner($member, $initiator);
		} else {
			$this->verifyMemberLevel($member, $initiator, $level);
		}

		$event->getData()->sInt('level', $level);

		$outcomeMember = clone $member;
		$outcomeMember->setLevel($level);
		$event->setOutcome($this->serialize($outcomeMember));
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		$member = clone $event->getMember();
		$member->setLevel($event->getData()->gInt('level'));
		$this->memberRequest->updateLevel($member);

		if ($member->getLevel() === Member::LEVEL_OWNER) {
			$oldOwner = clone $event->getCircle()->getOwner();
			$oldOwner->setLevel(Member::LEVEL_ADMIN);
			$this->memberRequest->updateLevel($oldOwner);
			$this->membershipService->onUpdate($oldOwner->getSingleId());
		}

		$this->membershipService->onUpdate($member->getSingleId());

		$this->eventService->memberLevelEditing($event);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->memberLevelEdited($event, $results);
	}


	/**
	 * @param Member $member
	 * @param Member $initiator
	 * @param int $level
	 *
	 * @throws MemberLevelException
	 */
	private function verifyMemberLevel(Member $member, Member $initiator, int $level) {
		$initiatorHelper = new MemberHelper($initiator);
		$memberHelper = new MemberHelper($member);

		$memberHelper->mustBeMember();
		$memberHelper->cannotBeOwner();
		$initiatorHelper->mustBeModerator();

		switch ($this->configService->getAppValueInt(ConfigService::HARD_MODERATION)) {
			case 0:
				$initiatorHelper->mustHaveLevelAboveOrEqual($level);
				$initiatorHelper->mustBeHigherOrSameLevelThan($member);
				break;
			case 1:
				$initiatorHelper->mustHaveLevelAboveOrEqual($level);
				$initiatorHelper->mustBeHigherLevelThan($member);
				break;
			case 2:
				$initiatorHelper->mustHaveLevelAbove($level);
				$initiatorHelper->mustBeHigherLevelThan($member);
				break;
		}
	}

	/**
	 * @param Member $member
	 * @param Member $initiator
	 */
	private function verifySwitchOwner(Member $member, Member $initiator) {
		// TODO: check on NO_OWNER circle
		$initiatorHelper = new MemberHelper($initiator);
		$memberHelper = new MemberHelper($member);

		$initiatorHelper->mustBeOwner();
		$memberHelper->mustBeMember();
		$memberHelper->cannotBeOwner();
	}
}
