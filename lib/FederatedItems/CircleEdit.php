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
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNameTooShortException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\EventService;

/**
 * Class CircleEdit
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleEdit implements IFederatedItem {
	use TDeserialize;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var CircleService */
	private $circleService;

	/** @var EventService */
	private $eventService;


	/**
	 * CircleEdit constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param CircleService $circleService
	 * @param EventService $eventService
	 */
	public function __construct(
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		CircleService $circleService,
		EventService $eventService
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->circleService = $circleService;
		$this->eventService = $eventService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 * @throws CircleNameTooShortException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();

		$initiatorHelper = new MemberHelper($circle->getInitiator());
		$initiatorHelper->mustBeOwner();

		$data = $event->getParams();
		$new = clone $circle;

		if ($data->hasKey('name')) {
			$new->setName($this->circleService->cleanCircleName($data->g('name')));
			if (strlen($new->getName()) < 3) {
				throw new CircleNameTooShortException('Circle name is too short');
			}
			$event->getData()->s('name', $new->getName());
		}

		if ($data->hasKey('description')) {
			$new->setDescription($data->g('description'));
			$event->getData()->s('description', $new->getDescription());
		}

		$this->circleService->confirmName($new);

		$event->setOutcome($this->serialize($new));
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		$circle = clone $event->getCircle();
		$data = $event->getData();

		// TODO: verify that event->GetCircle() is updated by the instance that owns the Circle so we can
		// use it as a thrustable base
		if ($data->hasKey('name')) {
			$circle->setName($data->g('name'));
		}

		$this->circleService->confirmName($circle);

		if ($data->hasKey('description')) {
			$circle->setDescription($data->g('description'));
		}

		$this->circleRequest->edit($circle);
		$this->memberRequest->updateDisplayName($circle->getSingleId(), $circle->getDisplayName());
		$this->eventService->circleEditing($event);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->circleEdited($event, $results);
	}
}
