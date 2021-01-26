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
use OCA\Circles\Db\RemoteWrapperRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;
use OCA\Circles\Exceptions\RemoteEventException;
use OCA\Circles\IRemoteEvent;
use OCA\Circles\Model\GlobalScale\GSWrapper;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Remote\RemoteEvent;
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
		CircleRequest $circleRequest,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->signer = $signer;
		$this->remoteWrapperRequest = $remoteWrapperRequest;
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
	 */
	public function newEvent(RemoteEvent $event): void {
		$event->setSource($this->configService->getLocalInstance());
		$this->verifyViewer($event);
		try {
			$gs = $this->getRemoteEvent($event);
		} catch (RemoteEventException $e) {
			$this->e($e);
			throw $e;
		}

		try {
			if ($this->isLocalEvent($event)) {
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
	 * @param RemoteEvent $event
	 */
	private function verifyViewer(RemoteEvent $event): void {
		if (!$event->hasCircle() || !$event->getCircle()->hasViewer()) {
			return;
		}

		// TODO: Verify/Set Source of Viewer (check based on the source of the request)
//		if ($event->isLocal()) {
//		}

		$circle = $event->getCircle();
		$viewer = $circle->getViewer();

		try {
			$localCircle = $this->circleRequest->getCircle($circle->getId(), $viewer);
		} catch (CircleNotFoundException $e) {
			return;
		}

		if (!$this->compareMembers($viewer, $localCircle->getViewer())) {
			return;
		}

		$event->setVerifiedViewer(true);
		$event->setCircle($localCircle);
	}


	/**
	 * We check that the event can be managed/checked locally or if the owner of the circle belongs to
	 * an other instance of Nextcloud
	 *
	 * @param RemoteEvent $event
	 *
	 * @return bool
	 * @throws CircleNotFoundException
	 */
	private function isLocalEvent(RemoteEvent $event): bool {
		if ($event->isLocal()) {
			return true;
		}

		if (!$event->hasCircle()) {
			return false;
		}

		$circle = $event->getCircle();
		if (!$circle->hasOwner()) {
			// TODO: Check on circle with no owner (add getInstance() to Circle)
			return false;
		}

		if ($event->isVerifiedViewer()) {
			$localCircle = $event->getCircle();
		} else {
			$localCircle = $this->circleRequest->getCircle($circle->getId());
		}

		$owner = $localCircle->getOwner();
		if ($owner->getInstance() === ''
			|| $this->configService->isLocalInstance($owner->getInstance())) {
			return true;
		}

		return false;
	}


	/**
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

		return $gs;
	}


	/**
	 * @param bool $all
	 *
	 * @return array
	 */
	public function getInstances(bool $all = false): array {
		$gsInstances = $this->getGlobalScaleInstances();
		$remoteInstances = $this->getRemoteInstances();
		$local = $this->configService->getLocalInstance();

		$instances = array_merge([$local], $gsInstances, $remoteInstances);
		if ($all) {
			return $instances;
		}

		return array_values(
			array_diff($instances, array_merge($this->configService->getTrustedDomains(), [$local]))
		);
	}


	/**
	 * @return array
	 */
	private function getGlobalScaleInstances(): array {
		try {
			$lookup = $this->configService->getGSStatus(ConfigService::GS_LOOKUP);
			$request = new NC21Request(ConfigService::GS_LOOKUP_INSTANCES, Request::TYPE_POST);
			$this->configService->configureRequest($request);
			$request->basedOnUrl($lookup);
			$request->addData('authKey', $this->configService->getGSStatus(ConfigService::GS_KEY));

			try {
				return $this->retrieveJson($request);
			} catch (RequestNetworkException $e) {
				$this->e($e, ['request' => $request]);
			}
		} catch (GSStatusException $e) {
		}

		return [];
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


	/**
	 * @param Member $member1
	 * @param Member $member2
	 *
	 * @return bool
	 */
	private function compareMembers(Member $member1, Member $member2): bool {
//		if ($member1->getInstance() === '') {
//			$member1->setInstance($this->configService->getLocalInstance());
//		}
//
//		if ($member2->getInstance() === '') {
//			$member2->setInstance($this->configService->getLocalInstance());
//		}

		if ($member1->getCircleId() !== $member2->getCircleId()
			|| $member1->getUserId() !== $member2->getUserId()
			|| $member1->getUserType() <> $member2->getUserType()
			|| $member1->getLevel() <> $member2->getLevel()
			|| $member1->getStatus() !== $member2->getStatus()
			|| $member1->getInstance() !== $member2->getInstance()) {
			return false;
		}

		return true;
	}

}

