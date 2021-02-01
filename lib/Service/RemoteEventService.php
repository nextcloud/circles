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


use daita\MySmallPhpTools\ActivityPub\Nextcloud\nc21\NC21Signature;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Request;
use daita\MySmallPhpTools\Traits\TStringTools;
use OC;
use OC\Security\IdentityProof\Signer;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Db\RemoteWrapperRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteEventException;
use OCA\Circles\Exceptions\ViewerNotConfirmedException;
use OCA\Circles\IRemoteEvent;
use OCA\Circles\IRemoteEventBypassLocalCircleCheck;
use OCA\Circles\IRemoteEventBypassViewerCheck;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\GlobalScale\GSWrapper;
use OCA\Circles\Model\Remote\RemoteEvent;
use OCA\Circles\Model\Remote\RemoteInstance;
use OCA\Circles\Model\Remote\RemoteWrapper;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use ReflectionClass;
use ReflectionException;


/**
 * Class RemoteService
 *
 * @package OCA\Circles\Service
 */
class RemoteEventService extends NC21Signature {


	use TNC21Request;
	use TStringTools;


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	/** @var Signer */
	private $signer;

	/** @var RemoteWrapperRequest */
	private $remoteWrapperRequest;

	/** @var RemoteRequest */
	private $remoteRequest;

	/** @var CircleRequest */
	private $circleRequest;


	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * GlobalScaleService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param Signer $signer
	 * @param RemoteWrapperRequest $remoteWrapperRequest
	 * @param RemoteRequest $remoteRequest
	 * @param CircleRequest $circleRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		IUserSession $userSession,
		Signer $signer,
		RemoteWrapperRequest $remoteWrapperRequest,
		RemoteRequest $remoteRequest,
		CircleRequest $circleRequest,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->signer = $signer;
		$this->remoteWrapperRequest = $remoteWrapperRequest;
		$this->remoteRequest = $remoteRequest;
		$this->circleRequest = $circleRequest;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * Called when creating a new Event.
	 * This method will manage the event locally and upstream the payload if needed.
	 *
	 * @param RemoteEvent $event
	 *
	 * @throws RemoteEventException
	 * @throws OwnerNotFoundException
	 * @throws ViewerNotConfirmedException
	 */
	public function newEvent(RemoteEvent $event): void {
		$event->setSource($this->configService->getLocalInstance());
		if (!$event->hasCircle()) {
			throw new RemoteEventException('Event does not contains Circle');
		}

		try {
			$gs = $this->getRemoteEvent($event);
		} catch (RemoteEventException $e) {
			$this->e($e);
			throw $e;
		}

		$this->confirmViewer($event);

		try {
			if ($this->configService->isLocalInstance($event->getCircle()->getInstance())) {
				$gs->verify($event);
				if (!$event->isAsync()) {
					$gs->manage($event);
				}

				$this->initBroadcast($event);
			} else {
				//	$this->confirmEvent($event);
			}
		} catch (CircleNotFoundException $e) {
			$this->e($e, ['event' => $event]);
		}

	}


	/**
	 * This confirmation is optional, method is just here to avoid going too far away on the process
	 *
	 * @param RemoteEvent $event
	 *
	 * @throws ViewerNotConfirmedException
	 */
	private function confirmViewer(RemoteEvent $event): void {
		if ($event->canBypass(RemoteEvent::BYPASS_VIEWERCHECK)) {
			return;
		}

		$circle = $event->getCircle();
		if (!$circle->hasViewer()
			|| !$this->configService->isLocalInstance(
				$circle->getViewer()->getInstance()
			)) {
			throw new ViewerNotConfirmedException('viewer does not exist or is not local');
		}
	}



//	/**
//	 * We check that the event can be managed/checked locally or if the owner of the circle belongs to
//	 * an other instance of Nextcloud
//	 *
//	 * @param RemoteEvent $event
//	 *
//	 * @return bool
//	 * @throws CircleNotFoundException
//	 * @throws OwnerNotFoundException
//	 */
//	public function isLocalEvent(RemoteEvent $event): bool {
////		if ($event->isLocal()) {
////			return true;
////		}
//
//		$circle = $event->getCircle();
//
////		if (!$circle->hasOwner()) {
//		return ($this->configService->isLocalInstance($circle->getInstance()));
////		}
//
////		if ($event->isVerifiedCircle()) {
////			$localCircle = $event->getCircle();
////		} else {
////			$localCircle = $this->circleRequest->getCircle($circle->getId());
////		}
////
////		$owner = $localCircle->getOwner();
////		if ($owner->getInstance() === ''
////			|| $this->configService->isLocalInstance($owner->getInstance())) {
////			return true;
////		}
////
////		return false;
//	}


	/**
	 * // TODO Rename Model/RemoteEvent and/or IRemoteEvent
	 *
	 * @param RemoteEvent $event
	 *
	 * @return IRemoteEvent
	 * @throws RemoteEventException
	 */
	public function getRemoteEvent(RemoteEvent $event): IRemoteEvent {
		$class = $event->getClass();
		try {
			$test = new ReflectionClass($class);
		} catch (ReflectionException $e) {
			throw new RemoteEventException('ReflectionException with ' . $class . ': ' . $e->getMessage());
		}

		if (!in_array(IRemoteEvent::class, $test->getInterfaceNames())) {
			throw new RemoteEventException($class . ' does not implements IRemoteEvent');
		}

		$gs = OC::$server->get($class);
		if (!$gs instanceof IRemoteEvent) {
			throw new RemoteEventException($class . ' not an IRemoteEvent');
		}

		$this->setRemoteEventBypass($event, $gs);

		return $gs;
	}


	/**
	 * Some event might need to bypass some checks
	 *
	 * @param RemoteEvent $event
	 * @param IRemoteEvent $gs
	 */
	private function setRemoteEventBypass(RemoteEvent $event, IRemoteEvent $gs) {
		if ($gs instanceof IRemoteEventBypassLocalCircleCheck) {
			$event->bypass(RemoteEvent::BYPASS_LOCALCIRCLECHECK);
		}
		if ($gs instanceof IRemoteEventBypassViewerCheck) {
			$event->bypass(RemoteEvent::BYPASS_VIEWERCHECK);
		}
	}


	/**
	 * async the process, generate a local request that will be closed.
	 *
	 * @param RemoteEvent $event
	 */
	public function initBroadcast(RemoteEvent $event): void {
		$instances = $this->getInstances($event->isAsync());
		if (empty($instances)) {
			return;
		}

		$wrapper = new RemoteWrapper();
		$wrapper->setEvent($event);
		$wrapper->setToken($this->uuid());
		$wrapper->setCreation(time());
		$wrapper->setSeverity($event->getSeverity());

		foreach ($instances as $instance) {
			$wrapper->setInstance($instance);
			$this->remoteWrapperRequest->create($wrapper);
		}

		$request = new NC21Request('', Request::TYPE_POST);
		$this->configService->configureRequest(
			$request, 'circles.RemoteWrapper.asyncBroadcast', ['token' => $wrapper->getToken()]
		);

		$event->setWrapperToken($wrapper->getToken());

		try {
			$this->doRequest($request);
		} catch (RequestNetworkException $e) {
			$this->e($e, ['wrapper' => $wrapper]);
		}
	}


	/**
	 * @param bool $all
	 * @param Circle|null $circle
	 *
	 * @return array
	 */
	public function getInstances(bool $all = false, ?Circle $circle = null): array {
		$local = $this->configService->getLocalInstance();
		$instances = $this->remoteRequest->getOutgoingRecipient($circle);
		$instances = array_merge(
			[$local], array_map(
						function(RemoteInstance $instance): string {
							return $instance->getInstance();
						}, $instances
					)
		);

		if ($all) {
			return $instances;
		}

		return array_values(
			array_diff($instances, array_merge($this->configService->getTrustedDomains(), [$local]))
		);
	}


	/**
	 * @param array $current
	 */
	private function updateGlobalScaleInstances(array $current): void {
//		$known = $this->remoteRequest->getFromType(RemoteInstance::TYPE_GLOBAL_SCALE);
	}

	/**
	 * @return array
	 */
	private function getRemoteInstances(): array {
		return [];
	}


	/**
	 * should be used to manage results from events, like sending mails on user creation
	 *
	 * @param string $token
	 */
	public function manageResults(string $token): void {
		try {
			$wrappers = $this->remoteWrapperRequest->getByToken($token);
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
			$gs = $this->getRemoteEvent($event);
			$gs->result($events);
		} catch (RemoteEventException $e) {
		}
	}


	/**
	 * @param RemoteEvent $event
	 */
	private function confirmEvent(RemoteEvent $event): void {
//		$this->signEvent($event);

		$circle = $event->getCircle();
		$owner = $circle->getOwner();
		$path = $this->urlGenerator->linkToRoute('circles.RemoteWrapper.event');

		$request = new NC21Request($path, Request::TYPE_POST);
		$this->configService->configureRequest($request);
		$request->basedOnUrl($owner->getInstance());

		$request->setDataSerialize($event);

		$result = $this->retrieveJson($request);
		$this->debug('confirming RemoteEvent', ['event' => $event, 'request' => $request]);
//
//		if ($this->getInt('status', $result) === 0) {
//			throw new GlobalScaleEventException($this->get('error', $result));
//		}

//		$updatedData = $this->getArray('event', $result);
//		$this->miscService->log('updatedEvent: ' . json_encode($updatedData), 0);
//		if (!empty($updatedData)) {
//			$updated = new GSEvent();
//			try {
//				$updated->import($updatedData);
//				$event = $updated;
//			} catch (Exception $e) {
//			}
//		}
	}

}

