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
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\GlobalScaleDSyncException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Model\Federated\FederatedEvent;


/**
 * Class RemoteDownstreamService
 *
 * @package OCA\Circles\Service
 */
class RemoteDownstreamService {


	use TNC21Logger;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * RemoteDownstreamService constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param FederatedEventService $federatedEventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		FederatedEventService $federatedEventService,
		ConfigService $configService
	) {
		$this->setup('app', 'circles');

		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->federatedEventService = $federatedEventService;
		$this->configService = $configService;
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
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws GlobalScaleDSyncException
	 * @throws FederatedEventDSyncException
	 */
	public function requestedEvent(FederatedEvent $event): void {
		try {
			$gs = $this->federatedEventService->getFederatedItem($event, false);
		} catch (FederatedEventException $e) {
			$this->e($e);
			throw $e;
		}

		if (!$this->configService->isLocalInstance($event->getCircle()->getInstance())) {
			throw new FederatedEventException('Circle is not from this instance');
		}
		$this->federatedEventService->confirmInitiator($event, false);
		try {
			$this->confirmContent($event);
		} catch (Exception $e) {
			throw new FederatedEventDSyncException();
		}

		$gs->verify($event);
		// async.

//			if (!$event->isAsync()) {
//				$gs->manage($event);
//			}
//
//			$this->initBroadcast($event);
//		} else {
//			$this->remoteUpstreamService->confirmEvent($event);
//		}
//
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @return array
	 */
	public function incomingEvent(FederatedEvent $event): array {
		$result = [];
		try {
			$gs = $this->federatedEventService->getFederatedItem($event);
			$this->confirmOriginEvent($event);
			$this->confirmContent($event);

			$gs->manage($event);
		} catch (Exception $e) {
			$this->e($e, ['event' => $event]);
		}

		return $result;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventDSyncException
	 * @throws OwnerNotFoundException
	 */
	private function confirmContent(FederatedEvent $event): void {
		$this->confirmCircle($event);
		$this->confirmMember($event);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws OwnerNotFoundException
	 * @throws FederatedEventDSyncException
	 */
	private function confirmCircle(FederatedEvent $event): void {
		if ($event->canBypass(FederatedEvent::BYPASS_LOCALCIRCLECHECK) || $this->verifyCircle($event)) {
			return;
		}

		throw new FederatedEventDSyncException('Could not verify Circle');
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @return bool
	 * @throws OwnerNotFoundException
	 */
	private function verifyCircle(FederatedEvent $event): bool {
		$circle = $event->getCircle();
		try {
			$localCircle = $this->circleRequest->getCircle($circle->getId());
		} catch (CircleNotFoundException $e) {
			return false;
		}

		if (!$localCircle->compareWith($circle)) {
			return false;
		}
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventDSyncException
	 */
	private function confirmMember(FederatedEvent $event): void {
		if (!$event->hasMember() || $this->verifyMember($event)) {
			return;
		}

		throw new FederatedEventDSyncException('Could not verify Member');
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @return bool
	 */
	private function verifyMember(FederatedEvent $event): bool {
		$member = $event->getMember();
		try {
			$localMember = $this->memberRequest->getMember($member->getId());
		} catch (MemberNotFoundException $e) {
			return false;
		}

		if (!$localMember->compareWith($member)) {
			return false;
		}
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventException
	 * @throws OwnerNotFoundException
	 */
	private function confirmOriginEvent(FederatedEvent $event): void {
		if ($event->getIncomingOrigin() !== $event->getCircle()->getInstance()) {
			$this->debug('invalid origin', ['event' => $event]);
			throw new FederatedEventException('invalid origin');
		}
	}

}

