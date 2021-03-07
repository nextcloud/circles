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


use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemCircleCheckNotRequired;
use OCA\Circles\IFederatedItemInitiatorMustBeLocal;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\ConfigService;


/**
 * Class CircleCreate
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleCreate implements
	IFederatedItem,
	IFederatedItemCircleCheckNotRequired,
	IFederatedItemInitiatorMustBeLocal {


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var ConfigService */
	private $configService;


	/**
	 * CircleCreate constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param ConfigService $configService
	 */
	public function __construct(
		CircleRequest $circleRequest, MemberRequest $memberRequest, ConfigService $configService
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->configService = $configService;
	}


	/**
	 * Circles are created on the original instance, using IFederatedItemInitiatorMustBeLocal
	 *
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();

		$event->setOutcome(['circle' => $circle]);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventDSyncException
	 * @throws InvalidIdException
	 */
	public function manage(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$owner = $circle->getOwner();

		try {
			$this->circleRequest->getCircle($circle->getId());
			throw new FederatedEventDSyncException('circle already exist');
		} catch (CircleNotFoundException $e) {
		}

		try {
			$this->memberRequest->getMember($owner->getId());
			throw new FederatedEventDSyncException('owner already exist');
		} catch (MemberNotFoundException $e) {
		}

		$this->circleRequest->save($circle);
		$this->memberRequest->save($owner);

		// TODO: EventsService
		// $this->eventsService->onCircleCreation($circle);
	}


	/**
	 * @param FederatedEvent[] $events
	 */
	public function result(array $events): void {
	}

}

