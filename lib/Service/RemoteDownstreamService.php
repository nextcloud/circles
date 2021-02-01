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


namespace OCA\Circles\Service;


use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Logger;
use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteEventException;
use OCA\Circles\Model\Remote\RemoteEvent;


/**
 * Class RemoteDownstreamService
 *
 * @package OCA\Circles\Service
 */
class RemoteDownstreamService {


	use TNC21Logger;


//	/** @var GlobalScaleService */
//	private $globalScaleService;
//
//	/** @var ConfigService */
//	private $configService;
//


	/** @var CircleRequest */
	private $circleRequest;

	/** @var RemoteEventService */
	private $remoteEventService;


	/**
	 * RemoteDownstreamService constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param RemoteEventService $remoteEventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		CircleRequest $circleRequest,
		RemoteEventService $remoteEventService,
		ConfigService $configService
	) {
		$this->setup('app', 'circles');

		$this->circleRequest = $circleRequest;
		$this->remoteEventService = $remoteEventService;
//		$this->configService = $configService;
	}


//
//
//	/**
//	 * @param GSEvent $event
//	 *
//	 * @throws CircleDoesNotExistException
//	 * @throws ConfigNoCircleAvailableException
//	 * @throws GSKeyException
//	 * @throws GlobalScaleDSyncException
//	 * @throws GlobalScaleEventException
//	 */
//	public function statusEvent(GSEvent $event) {
//		$this->globalScaleService->checkEvent($event);
//
//		$gs = $this->globalScaleService->getGlobalScaleEvent($event);
//		$gs->verify($event, false);
//		$gs->manage($event);
//	}
//


	/**
	 * @param RemoteEvent $event
	 *
	 * @throws RemoteEventException
	 */
	public function requestedEvent(RemoteEvent $event) {
//		if ($event instanceof IRemoteEventMustBeLocal) {
//			return true;
//		}

//		$gs = $this->remoteEventService->getRemoteEvent($event);
//		if (!$this->remoteEventService->isLocalEvent($event)) {
//			return;
//		}
//
//		$gs->verify($event);
//
//		if (!$event->isAsync()) {
//			$gs->manage($event);
//		}

//		$this->globalScaleService->asyncBroadcast($event);
	}


	/**
	 * @param RemoteEvent $event
	 *
	 * @return array
	 */
	public function incomingEvent(RemoteEvent $event): array {
		$result = [];
		try {
			$gs = $this->remoteEventService->getRemoteEvent($event);
			$this->confirmCircle($event);
			$this->confirmOriginEvent($event);

			$gs->manage($event);
		} catch (Exception $e) {
			$this->e($e, ['event' => $event]);
		}

		return $result;
	}


	/**
	 * @param RemoteEvent $event
	 *
	 * @throws RemoteEventException
	 */
	private function confirmCircle(RemoteEvent $event): void {
		if ($event->canBypass(RemoteEvent::BYPASS_LOCALCIRCLECHECK) || $this->verifyCircle($event)) {
			return;
		}

		throw new RemoteEventException('could not verify circle');
	}

	/**
	 * @param RemoteEvent $event
	 *
	 * @return bool
	 */
	private function verifyCircle(RemoteEvent $event): bool {
		$circle = $event->getCircle();
		try {
			$localCircle = $this->circleRequest->getCircle($circle->getId());
		} catch (CircleNotFoundException $e) {
			return false;
		}

		return ($localCircle->compareWith($circle));
	}


	/**
	 * @param RemoteEvent $event
	 *
	 * @throws RemoteEventException
	 * @throws OwnerNotFoundException
	 */
	private function confirmOriginEvent(RemoteEvent $event): void {
		if ($event->getIncomingOrigin() !== $event->getCircle()->getInstance()) {
			$this->debug('invalid origin', ['event' => $event]);
			throw new RemoteEventException('invalid origin');
		}
	}

}

