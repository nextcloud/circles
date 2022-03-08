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

use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TNCRequest;
use OCA\Circles\Db\EventWrapperRequest;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Federated\EventWrapper;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;

/**
 * Class RemoteUpstreamService
 *
 * @package OCA\Circles\Service
 */
class RemoteUpstreamService {
	use TNCRequest;


	/** @var EventWrapperRequest */
	private $eventWrapperRequest;

	/** @var RemoteStreamService */
	private $remoteStreamService;

	/** @var InterfaceService */
	private $interfaceService;

	/** @var ConfigService */
	private $configService;


	/**
	 * RemoteUpstreamService constructor.
	 *
	 * @param EventWrapperRequest $eventWrapperRequest
	 * @param RemoteStreamService $remoteStreamService
	 * @param InterfaceService $interfaceService
	 * @param ConfigService $configService
	 */
	public function __construct(
		EventWrapperRequest $eventWrapperRequest,
		RemoteStreamService $remoteStreamService,
		InterfaceService $interfaceService,
		ConfigService $configService
	) {
		$this->eventWrapperRequest = $eventWrapperRequest;
		$this->remoteStreamService = $remoteStreamService;
		$this->interfaceService = $interfaceService;
		$this->configService = $configService;
	}


	/**
	 * @param string $token
	 *
	 * @return EventWrapper[]
	 */
	public function getEventsByToken(string $token): array {
		return $this->eventWrapperRequest->getByToken($token);
	}


	/**
	 * @param EventWrapper $wrapper
	 *
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function broadcastEvent(EventWrapper $wrapper): void {
		$event = clone $wrapper->getEvent();
		$event->resetInternal();

		$this->interfaceService->setCurrentInterface($wrapper->getInterface());
		$data = $this->remoteStreamService->resultRequestRemoteInstance(
			$wrapper->getInstance(),
			RemoteInstance::INCOMING,
			Request::TYPE_POST,
			$event
		);

		$wrapper->getEvent()->setResult(new SimpleDataStore($data));
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function confirmEvent(FederatedEvent $event): void {
		$instance = $event->getCircle()->getInstance();

		$data = $this->remoteStreamService->resultRequestRemoteInstance(
			$instance,
			RemoteInstance::EVENT,
			Request::TYPE_POST,
			$event
		);

		$event->setOutcome($data);
	}


	//
	//
	//

//	/**
//	 * @param array $sync
//	 *
//	 * @throws GSStatusException
//	 */
//	public function synchronize(array $sync) {
//		$this->configService->getGSStatus();
//
//		$this->synchronizeCircles($sync);
//		$this->removeDeprecatedCircles();
//		$this->removeDeprecatedEvents();
//	}


//	/**
//	 * @param array $circles
//	 */
//	public function synchronizeCircles(array $circles): void {
//		$event = new GSEvent(GSEvent::GLOBAL_SYNC, true);
//		$event->setSource($this->configService->getLocalInstance());
//		$event->setData(new SimpleDataStore($circles));
//
//		foreach ($this->federatedEventService->getInstances() as $instance) {
//			try {
//				$this->broadcastEvent($event, $instance);
//			} catch (RequestContentException | RequestNetworkException | RequestResultSizeException | RequestServerException | RequestResultNotJsonException $e) {
//			}
//		}
//	}
//
//
//	/**
//	 *
//	 */
//	private function removeDeprecatedCircles() {
//		$knownCircles = $this->circlesRequest->forceGetCircles();
//		foreach ($knownCircles as $knownItem) {
//			if ($knownItem->getOwner()
//						  ->getInstance() === '') {
//				continue;
//			}
//
//			try {
//				$this->checkCircle($knownItem);
//			} catch (GSStatusException $e) {
//			}
//		}
//	}
//
//
//	/**
//	 * @param DeprecatedCircle $circle
//	 *
//	 * @throws GSStatusException
//	 */
//	private function checkCircle(DeprecatedCircle $circle): void {
//		$status = $this->confirmCircleStatus($circle);
//
//		if (!$status) {
//			$this->circlesRequest->destroyCircle($circle->getUniqueId());
//			$this->membersRequest->removeAllFromCircle($circle->getUniqueId());
//		}
//	}
//
//
//	/**
//	 * @param DeprecatedCircle $circle
//	 *
//	 * @return bool
//	 * @throws GSStatusException
//	 */
//	public function confirmCircleStatus(DeprecatedCircle $circle): bool {
//		$event = new GSEvent(GSEvent::CIRCLE_STATUS, true);
//		$event->setSource($this->configService->getLocalInstance());
//		$event->setDeprecatedCircle($circle);
//
//		$this->signEvent($event);
//
//		$path = $this->urlGenerator->linkToRoute('circles.EventWrapper.status');
//		$request = new NC22Request($path, Request::TYPE_POST);
//		$this->configService->configureRequest($request);
//		$request->setDataSerialize($event);
//
//		$requestIssue = false;
//		$notFound = false;
//		$foundWithNoOwner = false;
//		foreach ($this->federatedEventService->getInstances() as $instance) {
//			$request->setHost($instance);
//
//			try {
//				$result = $this->retrieveJson($request);
//				if ($this->getInt('status', $result, 0) !== 1) {
//					throw new RequestContentException('result status is not good');
//				}
//
//				$status = $this->getInt('success.data.status', $result);
//
//				// if error, we assume the circle might still exist.
//				if ($status === CircleStatus::STATUS_ERROR) {
//					return true;
//				}
//
//				if ($status === CircleStatus::STATUS_OK) {
//					return true;
//				}
//
//				// TODO: check the data.supposedOwner entry.
//				if ($status === CircleStatus::STATUS_NOT_OWNER) {
//					$foundWithNoOwner = true;
//				}
//
//				if ($status === CircleStatus::STATUS_NOT_FOUND) {
//					$notFound = true;
//				}
//
//			} catch (RequestContentException
//			| RequestNetworkException
//			| RequestResultNotJsonException
//			| RequestResultSizeException
//			| RequestServerException $e) {
//				$requestIssue = true;
//				// TODO: log instances that have network issue, after too many tries (7d), remove this circle.
//				continue;
//			}
//		}
//
//		// if no request issue, we can imagine that the instance that owns the circle is down.
//		// We'll wait for more information (cf request exceptions management);
//		if ($requestIssue) {
//			return true;
//		}
//
//		// circle were not found in any other instances, we can easily says that the circle does not exists anymore
//		if ($notFound && !$foundWithNoOwner) {
//			return false;
//		}
//
//		// circle were found everywhere but with no owner on every instance. we need to assign a new owner.
//		// This should be done by checking admin rights. if no admin rights, let's assume that circle should be removed.
//		if (!$notFound && $foundWithNoOwner) {
//			// TODO: assign a new owner and check that when changing owner, we do check that the destination instance is updated FOR SURE!
//			return true;
//		}
//
//		// some instances returned notFound, some returned circle with no owner. let's assume the circle is deprecated.
//		return false;
//	}
//
//	/**
//	 * @throws GSStatusException
//	 */
//	public function syncEvents() {
//
//	}
//
//	/**
//	 *
//	 */
//	private function removeDeprecatedEvents() {
////		$this->deprecatedEvents();
//
//	}
}
