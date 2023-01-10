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
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemInitiatorMembershipNotRequired;
use OCA\Circles\IFederatedItemMemberOptional;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\StatusCode;

/**
 * Class CircleLeave
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleLeave implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemAsyncProcess,
	IFederatedItemInitiatorMembershipNotRequired,
	IFederatedItemMemberOptional {
	use TDeserialize;
	use TNCLogger;


	/** @var MemberRequest */
	private $memberRequest;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MembershipService */
	private $membershipService;

	/** @var EventService */
	private $eventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CircleLeave constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param CircleRequest $circleRequest
	 * @param MembershipService $membershipService
	 * @param EventService $eventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		MemberRequest $memberRequest,
		CircleRequest $circleRequest,
		MembershipService $membershipService,
		EventService $eventService,
		ConfigService $configService
	) {
		$this->memberRequest = $memberRequest;
		$this->circleRequest = $circleRequest;
		$this->membershipService = $membershipService;
		$this->eventService = $eventService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 * @throws RequestBuilderException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$member = $circle->getInitiator();

		if (!$event->getParams()->gBool('force')) {
			$memberHelper = new MemberHelper($member);
			$memberHelper->cannotBeOwner();
		} elseif ($this->configService->isLocalInstance($event->getOrigin())) {
			if ($member->getLevel() === Member::LEVEL_OWNER) {
				try {
					$newOwner = $this->selectNewOwner($circle);
					$event->getData()->s('newOwnerId', $newOwner->getId());
				} catch (MemberNotFoundException $e) {
					$event->getData()->sBool('destroyCircle', true);
				}
			}
		}
		if ($member->getId() === '') {
			try {
				// make it works for not-yet-members
				$probe = new CircleProbe();
				$probe->includeNonVisibleCircles();
				$member = $this->memberRequest->getMember(
					$circle->getSingleId(),
					$member->getSingleId(),
					$probe
				);
			} catch (MemberNotFoundException $e) {
				throw new MemberNotFoundException(StatusCode::$CIRCLE_LEAVE[120], 120);
			}
		}

		if ($member->getUserType() !== Member::TYPE_USER) {
			throw new MemberNotFoundException(StatusCode::$CIRCLE_LEAVE[121], 121);
		}

		$event->setMember($member);
		$this->memberRequest->delete($member);

		$initiator = new FederatedUser();
		$initiator->importFromIFederatedUser($member);

		try {
			$outcome = $this->circleRequest->getCircle($circle->getSingleId(), $initiator);
			$event->setOutcome($this->serialize($outcome));
		} catch (CircleNotFoundException $e) {
			// if member have no visibility on the circle after leaving it, we don't fill outcome
		}
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 * @throws MemberNotFoundException
	 */
	public function manage(FederatedEvent $event): void {
		$member = $event->getMember();
		$newOwnerId = $event->getData()->g('newOwnerId');
		if ($newOwnerId !== '') {
			$newOwner = $this->memberRequest->getMemberById($newOwnerId);
			$newOwner->setLevel(Member::LEVEL_OWNER);
			$this->memberRequest->updateLevel($newOwner);
			$this->membershipService->onUpdate($newOwner->getSingleId());
		}

		$this->memberRequest->delete($member);

		$destroyingCircle = $event->getData()->gBool('destroyCircle');
		if ($destroyingCircle) {
			$circle = $event->getCircle();
			$this->circleRequest->delete($circle);
		}

		$this->membershipService->onUpdate($member->getSingleId());
		$this->eventService->memberLeaving($event);

		if ($destroyingCircle) {
			$this->membershipService->onUpdate($circle->getSingleId());
			$this->eventService->circleDestroying($event);
		}

		$this->membershipService->updatePopulation($event->getCircle());
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->memberLeft($event, $results);

		if ($event->getData()->gBool('destroyCircle')) {
			$this->eventService->circleDestroyed($event, $results);
		}
	}


	/**
	 * @param Circle $circle
	 *
	 * @return Member
	 * @throws RequestBuilderException
	 * @throws MemberNotFoundException
	 */
	private function selectNewOwner(Circle $circle): Member {
		$members = $this->memberRequest->getMembers($circle->getSingleId());
		$newOwner = null;
		foreach ($members as $member) {
			if ($member->getLevel() === Member::LEVEL_OWNER) {
				continue;
			}
			if (is_null($newOwner)) {
				$newOwner = $member;
				continue;
			}

			if ($member->getLevel() > $newOwner->getLevel()) {
				$newOwner = $member;
			} elseif ($member->getLevel() === $newOwner->getLevel()
					  && ($member->getJoined() < $newOwner->getJoined()
						  || ($this->configService->isLocalInstance($member->getInstance())
							  && !$this->configService->isLocalInstance($newOwner->getInstance()))
					  )) {
				$newOwner = $member;
			}
		}

		if (is_null($newOwner)) {
			throw new MemberNotFoundException();
		}

		return $newOwner;
	}
}
