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


use daita\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use daita\MySmallPhpTools\Traits\TAsync;
use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
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


	use TNC22Logger;
	use TAsync;


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
	 * @throws FederatedEventDSyncException
	 * @throws FederatedEventException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws FederatedItemException
	 */
	public function requestedEvent(FederatedEvent $event): void {
		$item = $this->federatedEventService->getFederatedItem($event, true);

		if (!$this->configService->isLocalInstance($event->getCircle()->getInstance())) {
			throw new FederatedEventDSyncException('Circle is not from this instance');
		}

		if ($event->isLimitedToInstanceWithMember()) {
			$instances = $this->memberRequest->getMemberInstances($event->getCircle()->getSingleId());
			if (!in_array($event->getIncomingOrigin(), $instances)) {
				throw new FederatedEventException('Instance have no members in this Circle');
			}
		}

		$this->federatedEventService->confirmInitiator($event, false);
		$this->confirmContent($event, true);

		$item->verify($event);
		if ($event->isDataRequestOnly()) {
			return;
		}

		$filter = [];
		if (!$event->isAsync()) {
			$item->manage($event);
			// we dont filter anymore as some data might be change during the remote verify()
//			$filter[] = $event->getIncomingOrigin();
		}

		$this->federatedEventService->initBroadcast($event, $filter);
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
			$this->e($e, ['event' => $event]);
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
			$localCircle = $this->circleRequest->getCircle($circle->getSingleId());
		} catch (CircleNotFoundException $e) {
			return false;
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
			$localMember = $this->memberRequest->getMember($member->getId());
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
		if ($event->getIncomingOrigin() !== $event->getCircle()->getInstance()) {
			$this->debug('invalid origin', ['event' => $event]);
			throw new FederatedEventException('invalid origin');
		}
	}

}

