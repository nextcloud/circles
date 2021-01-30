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


namespace OCA\Circles\RemoteEvents;


use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\RemoteEventException;
use OCA\Circles\IRemoteEvent;
use OCA\Circles\IRemoteEventBypassLocalCircleCheck;
use OCA\Circles\Model\Remote\RemoteEvent;
use OCA\Circles\Service\ConfigService;


/**
 * Class CircleCreate
 *
 * @package OCA\Circles\RemoteEvents
 */
class CircleCreate implements
	IRemoteEvent,
	IRemoteEventBypassLocalCircleCheck {

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
	 * Circles are created on the original instance, so do no check;
	 *
	 * @param RemoteEvent $event
	 *
	 * @throws RemoteEventException
	 */
	public function verify(RemoteEvent $event): void {
		if (!$this->configService->isLocalInstance($event->getSource())) {
			throw new RemoteEventException('Creation of Circle cannot be managed from a remote instance');
		}

		if ($event->getCircle()->getOwner()->getInstance() !== $event->getSource()) {
			throw new RemoteEventException('Owner of the Circle must be belongs to local instance');
		}
	}


	/**
	 * @param RemoteEvent $event
	 */
	public function manage(RemoteEvent $event): void {
		if (!$event->hasCircle()) {
			return;
		}

		$circle = $event->getCircle();
		$owner = $circle->getOwner();

		$this->circleRequest->save($circle);
		$this->memberRequest->save($owner);

		// TODO: EventsService
		// $this->eventsService->onCircleCreation($circle);
	}


	/**
	 * @param RemoteEvent[] $events
	 */
	public function result(array $events): void {
	}
}

