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


namespace OCA\Circles\Service;

use OCA\Circles\Tools\Exceptions\RequestContentException;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\RequestResultNotJsonException;
use OCA\Circles\Tools\Exceptions\RequestResultSizeException;
use OCA\Circles\Tools\Exceptions\RequestServerException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TNCRequest;
use Exception;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Db\EventWrapperRequest;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;
use OCA\Circles\GlobalScale\CircleStatus;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\GlobalScale\GSWrapper;
use OCP\IURLGenerator;

/**
 * Class GSUpstreamService
 *
 * @package OCA\Circles\Service
 */
class GSUpstreamService {
	use TNCRequest;


	/** @var string */
	private $userId = '';

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var EventWrapperRequest */
	private $eventWrapperRequest;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;

	/** @var GlobalScaleService */
	private $globalScaleService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * GSUpstreamService constructor.
	 *
	 * @param $userId
	 * @param IURLGenerator $urlGenerator
	 * @param EventWrapperRequest $eventWrapperRequest
	 * @param DeprecatedCirclesRequest $circlesRequest
	 * @param DeprecatedMembersRequest $membersRequest
	 * @param GlobalScaleService $globalScaleService
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IURLGenerator $urlGenerator,
		EventWrapperRequest $eventWrapperRequest,
		DeprecatedCirclesRequest $circlesRequest,
		DeprecatedMembersRequest $membersRequest,
		GlobalScaleService $globalScaleService,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->urlGenerator = $urlGenerator;
		$this->eventWrapperRequest = $eventWrapperRequest;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->globalScaleService = $globalScaleService;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param GSEvent $event
	 *
	 * @return string
	 * @throws Exception
	 */
	public function newEvent(GSEvent $event): string {
		$event->setSource($this->configService->getFrontalInstance());
		try {
			$gs = $this->globalScaleService->getGlobalScaleEvent($event);
			if ($this->isLocalEvent($event)) {
				$gs->verify($event, true);
				if (!$event->isAsync()) {
					$gs->manage($event);
				}

				return $this->globalScaleService->asyncBroadcast($event);
			} else {
				$this->confirmEvent($event);

				return '';
			}
		} catch (Exception $e) {
			$this->miscService->log(
				get_class($e) . ' on new event: ' . $e->getMessage() . ' - ' . json_encode($event), 1
			);
			throw $e;
		}
	}


	/**
	 * @param GSWrapper $wrapper
	 */
	public function broadcastWrapper(GSWrapper $wrapper): void {
		$status = GSWrapper::STATUS_FAILED;

		try {
			$this->broadcastEvent($wrapper->getEvent(), $wrapper->getInstance());
			$status = GSWrapper::STATUS_DONE;
		} catch (RequestContentException | RequestNetworkException | RequestResultSizeException | RequestServerException | RequestResultNotJsonException $e) {
		}

		if ($wrapper->getSeverity() === GSEvent::SEVERITY_HIGH) {
			$wrapper->setStatus($status);
		} else {
			$wrapper->setStatus(GSWrapper::STATUS_OVER);
		}

		$this->eventWrapperRequest->update($wrapper);
	}


	/**
	 * @param GSEvent $event
	 * @param string $instance
	 * @param string $protocol
	 *
	 * @throws RequestContentException
	 * @throws RequestNetworkException
	 * @throws RequestResultNotJsonException
	 * @throws RequestResultSizeException
	 * @throws RequestServerException
	 */
	public function broadcastEvent(GSEvent $event, string $instance): void {
		$this->signEvent($event);

		if ($this->configService->isLocalInstance($instance)) {
			$request = new NCRequest('', Request::TYPE_POST);
			$this->configService->configureRequest($request, 'circles.EventWrapper.broadcast');
		} else {
			$path = $this->urlGenerator->linkToRoute('circles.EventWrapper.broadcast');
			$request = new NCRequest($path, Request::TYPE_POST);
			$this->configService->configureRequest($request);
			$request->setInstance($instance);
		}

		$request->setDataSerialize($event);

		$data = $this->retrieveJson($request);
		$event->setResult(new SimpleDataStore($this->getArray('result', $data, [])));
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws RequestContentException
	 * @throws RequestNetworkException
	 * @throws RequestResultSizeException
	 * @throws RequestServerException
	 * @throws RequestResultNotJsonException
	 * @throws GlobalScaleEventException
	 */
	public function confirmEvent(GSEvent &$event): void {
		$this->signEvent($event);

		$circle = $event->getDeprecatedCircle();
		$owner = $circle->getOwner();
		$path = $this->urlGenerator->linkToRoute('circles.EventWrapper.event');

		$request = new NCRequest($path, Request::TYPE_POST);
		$this->configService->configureRequest($request);
		$request->basedOnUrl($owner->getInstance());

		$request->setDataSerialize($event);

		$result = $this->retrieveJson($request);
		$this->miscService->log('result ' . json_encode($result), 0);
		if ($this->getInt('status', $result) === 0) {
			throw new GlobalScaleEventException($this->get('error', $result));
		}

		$updatedData = $this->getArray('event', $result);
		$this->miscService->log('updatedEvent: ' . json_encode($updatedData), 0);
		if (!empty($updatedData)) {
			$updated = new GSEvent();
			try {
				$updated->import($updatedData);
				$event = $updated;
			} catch (Exception $e) {
			}
		}
	}


	/**
	 * @param GSEvent $event
	 */
	private function signEvent(GSEvent $event) {
		$event->setKey($this->globalScaleService->getKey());
	}


	/**
	 * We check that the event can be managed/checked locally or if the owner of the circle belongs to
	 * an other instance of Nextcloud
	 *
	 * @param GSEvent $event
	 *
	 * @return bool
	 */
	private function isLocalEvent(GSEvent $event): bool {
		if ($event->isLocal()) {
			return true;
		}

		$circle = $event->getDeprecatedCircle();
		$owner = $circle->getOwner();
		if ($owner->getInstance() === ''
			|| in_array($owner->getInstance(), $this->configService->getTrustedDomains())) {
			return true;
		}

		return false;
	}


	/**
	 * @param string $token
	 *
	 * @return GSWrapper[]
	 * @throws JsonException
	 * @throws ModelException
	 */
	public function getEventsByToken(string $token): array {
		return $this->eventWrapperRequest->getByToken($token);
	}


	/**
	 * should be used to manage results from events, like sending mails on user creation
	 *
	 * @param string $token
	 */
	public function manageResults(string $token): void {
		try {
			$wrappers = $this->eventWrapperRequest->getByToken($token);
		} catch (JsonException | ModelException $e) {
			return;
		}

		$event = null;
		$events = [];
		foreach ($wrappers as $wrapper) {
			if ($wrapper->getStatus() !== GSWrapper::STATUS_DONE) {
				return;
			}

			$events[$wrapper->getInstance()] = $event = $wrapper->getEvent();
		}

		if ($event === null) {
			return;
		}

		try {
			$gs = $this->globalScaleService->getGlobalScaleEvent($event);
			$gs->result($events);
		} catch (GlobalScaleEventException $e) {
		}
	}


	/**
	 *
	 */
	private function removeDeprecatedCircles() {
		$knownCircles = $this->circlesRequest->forceGetCircles();
		foreach ($knownCircles as $knownItem) {
			if ($knownItem->getOwner()
						  ->getInstance() === '') {
				continue;
			}

			try {
				$this->checkCircle($knownItem);
			} catch (GSStatusException $e) {
			}
		}
	}


	/**
	 * @param DeprecatedCircle $circle
	 *
	 * @throws GSStatusException
	 */
	private function checkCircle(DeprecatedCircle $circle): void {
		$status = $this->confirmCircleStatus($circle);

		if (!$status) {
			$this->circlesRequest->destroyCircle($circle->getUniqueId());
			$this->membersRequest->removeAllFromCircle($circle->getUniqueId());
		}
	}


	/**
	 * @param DeprecatedCircle $circle
	 *
	 * @return bool
	 * @throws GSStatusException
	 */
	public function confirmCircleStatus(DeprecatedCircle $circle): bool {
		$event = new GSEvent(GSEvent::CIRCLE_STATUS, true);
		$event->setSource($this->configService->getFrontalInstance());
		$event->setDeprecatedCircle($circle);

		$this->signEvent($event);

		$path = $this->urlGenerator->linkToRoute('circles.EventWrapper.status');
		$request = new NCRequest($path, Request::TYPE_POST);
		$this->configService->configureRequest($request);
		$request->setDataSerialize($event);

		$requestIssue = false;
		$notFound = false;
		$foundWithNoOwner = false;
		foreach ($this->globalScaleService->getInstances() as $instance) {
			$request->setHost($instance);

			try {
				$result = $this->retrieveJson($request);
//				$this->miscService->log('result: ' . json_encode($result));
				if ($this->getInt('status', $result, 0) !== 1) {
					throw new RequestContentException('result status is not good');
				}

				$status = $this->getInt('success.data.status', $result);

				// if error, we assume the circle might still exist.
				if ($status === CircleStatus::STATUS_ERROR) {
					return true;
				}

				if ($status === CircleStatus::STATUS_OK) {
					return true;
				}

				// TODO: check the data.supposedOwner entry.
				if ($status === CircleStatus::STATUS_NOT_OWNER) {
					$foundWithNoOwner = true;
				}

				if ($status === CircleStatus::STATUS_NOT_FOUND) {
					$notFound = true;
				}
			} catch (RequestContentException
			| RequestNetworkException
			| RequestResultNotJsonException
			| RequestResultSizeException
			| RequestServerException $e) {
				$requestIssue = true;
				// TODO: log instances that have network issue, after too many tries (7d), remove this circle.
				continue;
			}
		}

		// if no request issue, we can imagine that the instance that owns the circle is down.
		// We'll wait for more information (cf request exceptions management);
		if ($requestIssue) {
			return true;
		}

		// circle were not found in any other instances, we can easily says that the circle does not exists anymore
		if ($notFound && !$foundWithNoOwner) {
			return false;
		}

		// circle were found everywhere but with no owner on every instance. we need to assign a new owner.
		// This should be done by checking admin rights. if no admin rights, let's assume that circle should be removed.
		if (!$notFound && $foundWithNoOwner) {
			// TODO: assign a new owner and check that when changing owner, we do check that the destination instance is updated FOR SURE!
			return true;
		}

		// some instances returned notFound, some returned circle with no owner. let's assume the circle is deprecated.
		return false;
	}

	/**
	 * @throws GSStatusException
	 */
	public function syncEvents() {
	}

	/**
	 *
	 */
	private function removeDeprecatedEvents() {
//		$this->deprecatedEvents();
	}
}
