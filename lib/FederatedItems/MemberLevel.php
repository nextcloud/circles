<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
		ConfigService $configService
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
