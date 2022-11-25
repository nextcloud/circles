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

use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Traits\TAsync;
use OCA\Circles\Tools\Traits\TNCLogger;
use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Probes\CircleProbe;

/**
 * Class RemoteDownstreamService
 *
 * @package OCA\Circles\Service
 */
class RemoteDownstreamService {
	use TNCLogger;
	use TAsync;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var RemoteService */
	private $remoteService;

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
		RemoteService $remoteService,
		ConfigService $configService
	) {
		$this->setup('app', 'circles');

		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->federatedEventService = $federatedEventService;
		$this->remoteService = $remoteService;
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
	 * @throws FederatedEventDSyncException
	 * @throws FederatedEventException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws FederatedItemException
	 * @throws RequestBuilderException
	 */
	public function requestedEvent(FederatedEvent $event): void {
		$item = $this->federatedEventService->getFederatedItem($event, true);

		if (!$this->configService->isLocalInstance($event->getCircle()->getInstance())) {
			throw new FederatedEventDSyncException('Circle is not from this instance');
		}

		if ($event->isLimitedToInstanceWithMember()) {
			$instances = $this->memberRequest->getMemberInstances($event->getCircle()->getSingleId());
			if (!in_array($event->getSender(), $instances)) {
				throw new FederatedEventException('Instance have no members in this Circle');
			}
		}

		$event->setOrigin($event->getSender());
		$event->resetData();

		$this->federatedEventService->confirmInitiator($event, false);
		$this->confirmContent($event, true);

		$item->verify($event);
		$event->resetResult();

		if ($event->isDataRequestOnly()) {
			return;
		}

		if (!$event->isAsync()) {
			$item->manage($event);
		}

		$this->federatedEventService->initBroadcast($event);
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function incomingEvent(FederatedEvent $event): void {
		try {
			$gs = $this->federatedEventService->getFederatedItem($event, false);
			$this->confirmOriginEvent($event);
			$this->confirmContent($event, false);

			$gs->manage($event);
		} catch (Exception $e) {
			$this->e($e, ['event' => $event->getWrapperToken()]);
		}
	}


	/**
	 * @param FederatedEvent $event
	 * @param bool $full
	 *
	 * @throws FederatedEventDSyncException
	 * @throws OwnerNotFoundException
	 */
	private function confirmContent(FederatedEvent $event, bool $full = true): void {
		$this->confirmCircle($event);
		$this->confirmMember($event, $full);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws CircleNotFoundException
	 * @throws FederatedEventDSyncException
	 * @throws InvalidIdException
	 * @throws InvalidItemException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
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
	 * @throws CircleNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RequestBuilderException
	 * @throws InvalidIdException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws InvalidItemException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 */
	private function verifyCircle(FederatedEvent $event): bool {
		$circle = $event->getCircle();

		try {
			$probe = new CircleProbe();
			$probe->includeSystemCircles()
				  ->includePersonalCircles();
			$localCircle = $this->circleRequest->getCircle($circle->getSingleId(), null, $probe);
		} catch (CircleNotFoundException $e) {
			try {
				$this->remoteService->syncRemoteCircle(
					$circle->getSingleId(),
					$circle->getOwner()->getInstance()
				);

				return true;
			} catch (Exception $e) {
				return false;
			}
		}

		if (!$localCircle->compareWith($circle)) {
			$this->debug(
				'failed to compare Circles',
				['localCircle' => json_encode($localCircle), 'circle' => json_encode($circle)]
			);

			return false;
		}

		return true;
	}


	/**
	 * @param FederatedEvent $event
	 * @param bool $full
	 * // TODO: Check IFederatedItemMember*
	 *
	 * @throws FederatedEventDSyncException
	 */
	private function confirmMember(FederatedEvent $event, bool $full): void {
		if ($event->canBypass(FederatedEvent::BYPASS_LOCALMEMBERCHECK) || !$event->hasMember()
			|| $this->verifyMember($event, $full)) {
			return;
		}

		throw new FederatedEventDSyncException('Could not verify Member');
	}

	/**
	 * @param FederatedEvent $event
	 * @param bool $full
	 *
	 * @return bool
	 */
	private function verifyMember(FederatedEvent $event, bool $full): bool {
		$this->debug('verifyMember()', ['event' => $event]);
		$member = $event->getMember();

		try {
			$localMember = $this->memberRequest->getMemberById($member->getId());
		} catch (MemberNotFoundException $e) {
			$this->debug('Member not found', ['member' => $member]);

			return false;
		}

		if (!$localMember->compareWith($member, $full)) {
			$this->debug(
				'failed to compare Members',
				['localMember' => json_encode($localMember), 'member' => json_encode($member)]
			);

			return false;
		}

		return true;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventException
	 * @throws OwnerNotFoundException
	 */
	private function confirmOriginEvent(FederatedEvent $event): void {
		if ($event->getSender() !== $event->getCircle()->getInstance()) {
			$this->debug('invalid origin', ['event' => $event]);
			throw new FederatedEventException('invalid origin');
		}
	}
}
