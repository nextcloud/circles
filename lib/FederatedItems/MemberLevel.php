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


use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemMemberRequired;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\MembershipService;


/**
 * Class MemberLevel
 *
 * @package OCA\Circles\FederatedItems
 */
class MemberLevel implements
	IFederatedItem,
	IFederatedItemMemberRequired {


	/** @var MemberRequest */
	private $memberRequest;

	/** @var MembershipService */
	private $membershipService;


	/**
	 * MemberAdd constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param MembershipService $membershipService
	 */
	public function __construct(MemberRequest $memberRequest, MembershipService $membershipService) {
		$this->memberRequest = $memberRequest;
		$this->membershipService = $membershipService;
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
		$level = $event->getData()->gInt('level');

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

		$outcomeMember = clone $member;
		$outcomeMember->setLevel($level);
		$event->setOutcome($outcomeMember->jsonSerialize());
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$member = clone $event->getMember();
		$member->setLevel($event->getData()->gInt('level'));
		$this->memberRequest->updateLevel($member);

		if ($member->getLevel() === Member::LEVEL_OWNER) {
			$oldOwner = clone $event->getCircle()->getOwner();
			$oldOwner->setLevel(Member::LEVEL_ADMIN);
			$this->memberRequest->updateLevel($oldOwner);
		}

		$this->membershipService->onUpdate($member->getSingleId());
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
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
		$initiatorHelper->mustHaveLevelAbove($level);
		$initiatorHelper->mustBeHigherLevelThan($member);
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

