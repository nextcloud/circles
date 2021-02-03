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
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Request;
use daita\MySmallPhpTools\Traits\TStringTools;
use OC;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Db\RemoteWrapperRequest;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemBypassInitiatorCheck;
use OCA\Circles\IFederatedItemBypassLocalCircleCheck;
use OCA\Circles\IFederatedItemMustBeLocal;
use OCA\Circles\IFederatedItemMustHaveMember;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Federated\RemoteWrapper;
use OCA\Circles\Model\GlobalScale\GSWrapper;
use ReflectionClass;
use ReflectionException;


/**
 * Class FederatedEventService
 *
 * @package OCA\Circles\Service
 */
class FederatedEventService extends NC21Signature {


	use TNC21Request;
	use TStringTools;


	/** @var RemoteWrapperRequest */
	private $remoteWrapperRequest;

	/** @var RemoteRequest */
	private $remoteRequest;

	/** @var RemoteUpstreamService */
	private $remoteUpstreamService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * FederatedEventService constructor.
	 *
	 * @param RemoteWrapperRequest $remoteWrapperRequest
	 * @param RemoteRequest $remoteRequest
	 * @param RemoteUpstreamService $remoteUpstreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		RemoteWrapperRequest $remoteWrapperRequest, RemoteRequest $remoteRequest,
		RemoteUpstreamService $remoteUpstreamService, ConfigService $configService
	) {
		$this->remoteWrapperRequest = $remoteWrapperRequest;
		$this->remoteRequest = $remoteRequest;
		$this->remoteUpstreamService = $remoteUpstreamService;
		$this->configService = $configService;
	}


	/**
	 * Called when creating a new Event.
	 * This method will manage the event locally and upstream the payload if needed.
	 *
	 * @param FederatedEvent $event
	 *
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws FederatedEventException
	 * @throws RequestNetworkException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws SignatoryException
	 */
	public function newEvent(FederatedEvent $event): void {
		$event->setSource($this->configService->getLocalInstance());

		try {
			$federatedItem = $this->getFederatedItem($event, true);
		} catch (FederatedEventException $e) {
			$this->e($e);
			throw $e;
		}

		// TODO :: UNCOMMENT UNCOMMENT UNCOMMENT !! this is only commented in order to test something
		$this->confirmInitiator($event, true);

		if ($this->configService->isLocalInstance($event->getCircle()->getInstance())) {
			$federatedItem->verify($event);
			if (!$event->isAsync()) {
				$federatedItem->manage($event);
			}

			$this->initBroadcast($event);
		} else {
			$this->remoteUpstreamService->confirmEvent($event);
		}
	}


	/**
	 * This confirmation is optional, method is just here to avoid going too far away on the process
	 *
	 * @param FederatedEvent $event
	 * @param bool $local
	 *
	 * @throws InitiatorNotConfirmedException
	 */
	public function confirmInitiator(FederatedEvent $event, bool $local = false): void {
		if ($event->canBypass(FederatedEvent::BYPASS_INITIATORCHECK)) {
			return;
		}

		$circle = $event->getCircle();
		if (!$circle->hasInitiator()) {
			throw new InitiatorNotConfirmedException('initiator does not exist');
		}

		if ($local) {
			if (!$this->configService->isLocalInstance($circle->getInitiator()->getInstance())) {
				throw new InitiatorNotConfirmedException('initiator is not local');
			}
		} else {
			if ($circle->getInitiator()->getInstance() !== $event->getIncomingOrigin()) {
				throw new InitiatorNotConfirmedException('initiator must belong to remote instance');
			}
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
	 * @param FederatedEvent $event
	 * @param bool $local
	 *
	 * @return IFederatedItem
	 * @throws FederatedEventException
	 */
	public function getFederatedItem(FederatedEvent $event, bool $local = false): IFederatedItem {
		$class = $event->getClass();
		try {
			$test = new ReflectionClass($class);
		} catch (ReflectionException $e) {
			throw new FederatedEventException('ReflectionException with ' . $class . ': ' . $e->getMessage());
		}

		if (!in_array(IFederatedItem::class, $test->getInterfaceNames())) {
			throw new FederatedEventException($class . ' does not implements IFederatedItem');
		}

		$item = OC::$server->get($class);
		if (!$item instanceof IFederatedItem) {
			throw new FederatedEventException($class . ' not an IFederatedItem');
		}

		$this->setFederatedEventBypass($event, $item);
		$this->confirmMustHaveCondition($event, $item, $local);

		return $item;
	}


	/**
	 * Some event might need to bypass some checks
	 *
	 * @param FederatedEvent $event
	 * @param IFederatedItem $gs
	 */
	private function setFederatedEventBypass(FederatedEvent $event, IFederatedItem $gs) {
		if ($gs instanceof IFederatedItemBypassLocalCircleCheck) {
			$event->bypass(FederatedEvent::BYPASS_LOCALCIRCLECHECK);
		}
		if ($gs instanceof IFederatedItemBypassInitiatorCheck) {
			$event->bypass(FederatedEvent::BYPASS_INITIATORCHECK);
		}
	}

	/**
	 * Some event might need to bypass some checks
	 *
	 * @param FederatedEvent $event
	 * @param IFederatedItem $item
	 * @param bool $local
	 *
	 * @throws FederatedEventException
	 */
	private function confirmMustHaveCondition(
		FederatedEvent $event,
		IFederatedItem $item,
		bool $local = false
	) {
		if (!$event->hasCircle()) {
			throw new FederatedEventException('FederatedItem has no Circle linked');
		}
		if ($item instanceof IFederatedItemMustHaveMember && !$event->hasMember()) {
			throw new FederatedEventException('FederatedItem has no Member linked');
		}
		if ($event->hasMember() && !($item instanceof IFederatedItemMustHaveMember)) {
			throw new FederatedEventException(
				get_class($item) . ' does not implements IFederatedItemMustHaveMember '
			);
		}
		if ($item instanceof IFederatedItemMustBeLocal && !$local) {
			throw new FederatedEventException('FederatedItem must be local');
		}
	}


	/**
	 * async the process, generate a local request that will be closed.
	 *
	 * @param FederatedEvent $event
	 */
	public function initBroadcast(FederatedEvent $event): void {
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
			$gs = $this->getFederatedItem($event);
			$gs->result($events);
		} catch (FederatedEventException $e) {
		}
	}

}

