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

use ArtificialOwl\MySmallPhpTools\ActivityPub\Nextcloud\nc22\NC22Signature;
use ArtificialOwl\MySmallPhpTools\Exceptions\RequestNetworkException;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc22\NC22Request;
use ArtificialOwl\MySmallPhpTools\Model\Request;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Request;
use ArtificialOwl\MySmallPhpTools\Traits\TStringTools;
use OC;
use OCA\Circles\Db\EventWrapperRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Db\ShareLockRequest;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedShareBelongingException;
use OCA\Circles\Exceptions\FederatedShareNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemCircleCheckNotRequired;
use OCA\Circles\IFederatedItemDataRequestOnly;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemInitiatorCheckNotRequired;
use OCA\Circles\IFederatedItemInitiatorMembershipNotRequired;
use OCA\Circles\IFederatedItemLimitedToInstanceWithMembership;
use OCA\Circles\IFederatedItemLoopbackTest;
use OCA\Circles\IFederatedItemMemberCheckNotRequired;
use OCA\Circles\IFederatedItemMemberEmpty;
use OCA\Circles\IFederatedItemMemberOptional;
use OCA\Circles\IFederatedItemMemberRequired;
use OCA\Circles\IFederatedItemMustBeInitializedLocally;
use OCA\Circles\IFederatedItemSharedItem;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\EventWrapper;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Member;
use ReflectionClass;
use ReflectionException;

/**
 * Class FederatedEventService
 *
 * @package OCA\Circles\Service
 */
class FederatedEventService extends NC22Signature {
	use TNC22Request;
	use TStringTools;


	/** @var EventWrapperRequest */
	private $eventWrapperRequest;

	/** @var RemoteRequest */
	private $remoteRequest;

	/** @var ShareLockRequest */
	private $shareLockRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var RemoteUpstreamService */
	private $remoteUpstreamService;

	/** @var InterfaceService */
	private $interfaceService;

	/** @var ConfigService */
	private $configService;


	/**
	 * FederatedEventService constructor.
	 *
	 * @param EventWrapperRequest $eventWrapperRequest
	 * @param RemoteRequest $remoteRequest
	 * @param MemberRequest $memberRequest
	 * @param ShareLockRequest $shareLockRequest
	 * @param RemoteUpstreamService $remoteUpstreamService
	 * @param InterfaceService $interfaceService
	 * @param ConfigService $configService
	 */
	public function __construct(
		EventWrapperRequest $eventWrapperRequest,
		RemoteRequest $remoteRequest,
		MemberRequest $memberRequest,
		ShareLockRequest $shareLockRequest,
		RemoteUpstreamService $remoteUpstreamService,
		InterfaceService $interfaceService,
		ConfigService $configService
	) {
		$this->eventWrapperRequest = $eventWrapperRequest;
		$this->remoteRequest = $remoteRequest;
		$this->shareLockRequest = $shareLockRequest;
		$this->memberRequest = $memberRequest;
		$this->remoteUpstreamService = $remoteUpstreamService;
		$this->interfaceService = $interfaceService;
		$this->configService = $configService;
	}


	/**
	 * Called when creating a new Event.
	 * This method will manage the event locally and upstream the payload if needed.
	 *
	 * @param FederatedEvent $event
	 *
	 * @return array
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RemoteInstanceException
	 * @throws RequestBuilderException
	 */
	public function newEvent(FederatedEvent $event): array {
		$event->setOrigin($this->interfaceService->getLocalInstance());

		$federatedItem = $this->getFederatedItem($event, false);
		$this->confirmInitiator($event, true);

		if ($event->canBypass(FederatedEvent::BYPASS_CIRCLE)) {
			$instance = $this->interfaceService->getLocalInstance();
		} else {
			$instance = $event->getCircle()->getInstance();
		}

		if ($this->configService->isLocalInstance($instance)) {
			$event->setSender($instance);
			$federatedItem->verify($event);

			if ($event->isDataRequestOnly()) {
				return $event->getOutcome();
			}

			if (!$event->isAsync()) {
				$federatedItem->manage($event);
			}

			$this->initBroadcast($event);
		} else {
			$this->remoteUpstreamService->confirmEvent($event);
			if ($event->isDataRequestOnly()) {
				return $event->getOutcome();
			}

//			if (!$event->isAsync()) {
//				$federatedItem->manage($event);
//			}
		}

		return $event->getOutcome();
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
			throw new InitiatorNotConfirmedException('Initiator does not exist');
		}

		if ($local) {
			if (!$this->configService->isLocalInstance($circle->getInitiator()->getInstance())) {
				throw new InitiatorNotConfirmedException(
					'Initiator is not from the instance at the origin of the request'
				);
			}
		} else {
			if ($circle->getInitiator()->getInstance() !== $event->getSender()) {
				throw new InitiatorNotConfirmedException(
					'Initiator must belong to the instance at the origin of the request'
				);
			}
		}

		if (!$event->canBypass(FederatedEvent::BYPASS_INITIATORMEMBERSHIP)
			&& $circle->getInitiator()->getLevel() < Member::LEVEL_MEMBER) {
			throw new InitiatorNotConfirmedException('Initiator must be a member of the Circle');
		}
	}


	/**
	 * @param FederatedEvent $event
	 * @param bool $checkLocalOnly
	 *
	 * @return IFederatedItem
	 * @throws FederatedEventException
	 */
	public function getFederatedItem(FederatedEvent $event, bool $checkLocalOnly = true): IFederatedItem {
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
		if (!($item instanceof IFederatedItem)) {
			throw new FederatedEventException($class . ' not an IFederatedItem');
		}

		if ($item instanceof IFederatedItemHighSeverity) {
			$event->setSeverity(FederatedEvent::SEVERITY_HIGH);
		}

		$this->setFederatedEventBypass($event, $item);
		$this->confirmRequiredCondition($event, $item, $checkLocalOnly);
		$this->configureEvent($event, $item);

//		$this->confirmSharedItem($event, $item);

		return $item;
	}


	/**
	 * Some event might need to bypass some checks
	 *
	 * @param FederatedEvent $event
	 * @param IFederatedItem $item
	 */
	private function setFederatedEventBypass(FederatedEvent $event, IFederatedItem $item) {
		if ($item instanceof IFederatedItemLoopbackTest) {
			$event->bypass(FederatedEvent::BYPASS_CIRCLE);
			$event->bypass(FederatedEvent::BYPASS_INITIATORCHECK);
		}
		if ($item instanceof IFederatedItemCircleCheckNotRequired) {
			$event->bypass(FederatedEvent::BYPASS_LOCALCIRCLECHECK);
		}
		if ($item instanceof IFederatedItemMemberCheckNotRequired) {
			$event->bypass(FederatedEvent::BYPASS_LOCALMEMBERCHECK);
		}
		if ($item instanceof IFederatedItemInitiatorCheckNotRequired) {
			$event->bypass(FederatedEvent::BYPASS_INITIATORCHECK);
		}
		if ($item instanceof IFederatedItemInitiatorMembershipNotRequired) {
			$event->bypass(FederatedEvent::BYPASS_INITIATORMEMBERSHIP);
		}
	}

	/**
	 * Some event might require additional check
	 *
	 * @param FederatedEvent $event
	 * @param IFederatedItem $item
	 * @param bool $checkLocalOnly
	 *
	 * @throws FederatedEventException
	 */
	private function confirmRequiredCondition(
		FederatedEvent $event,
		IFederatedItem $item,
		bool $checkLocalOnly = true
	) {
		if (!$event->canBypass(FederatedEvent::BYPASS_CIRCLE) && !$event->hasCircle()) {
			throw new FederatedEventException('FederatedEvent has no Circle linked');
		}

		// TODO: enforce IFederatedItemMemberEmpty if no member
		if ($item instanceof IFederatedItemMemberEmpty) {
			$event->setMember(null);
		} elseif ($item instanceof IFederatedItemMemberRequired && !$event->hasMember()) {
			throw new FederatedEventException('FederatedEvent has no Member linked');
		}

		if ($event->hasMember()
			&& !($item instanceof IFederatedItemMemberRequired)
			&& !($item instanceof IFederatedItemMemberOptional)) {
			throw new FederatedEventException(
				get_class($item)
				. ' does not implements IFederatedItemMemberOptional nor IFederatedItemMemberRequired'
			);
		}

		if ($item instanceof IFederatedItemMustBeInitializedLocally && $checkLocalOnly) {
			throw new FederatedEventException('FederatedItem must be executed locally');
		}
	}


	/**
	 * @param FederatedEvent $event
	 * @param IFederatedItem $item
	 *
	 * @throws FederatedEventException
	 * @throws FederatedShareBelongingException
	 * @throws FederatedShareNotFoundException
	 * @throws OwnerNotFoundException
	 */
	private function confirmSharedItem(FederatedEvent $event, IFederatedItem $item): void {
		if (!$item instanceof IFederatedItemSharedItem) {
			return;
		}

		if ($event->getItemId() === '') {
			throw new FederatedEventException('FederatedItem must contains ItemId');
		}

		if ($this->configService->isLocalInstance($event->getCircle()->getInstance())) {
			$shareLock = $this->shareLockRequest->getShare($event->getItemId());
			if ($shareLock->getInstance() !== $event->getSender()) {
				throw new FederatedShareBelongingException('ShareLock belongs to another instance');
			}
		}
	}


	/**
	 * @param FederatedEvent $event
	 * @param IFederatedItem $item
	 */
	private function configureEvent(FederatedEvent $event, IFederatedItem $item) {
		if ($item instanceof IFederatedItemAsyncProcess) {
			$event->setAsync(true);
		}
		if ($item instanceof IFederatedItemLimitedToInstanceWithMembership) {
			$event->setLimitedToInstanceWithMember(true);
		}
		if ($item instanceof IFederatedItemDataRequestOnly) {
			$event->setDataRequestOnly(true);
		}
	}


	/**
	 * async the process, generate a local request that will be closed.
	 *
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 */
	public function initBroadcast(FederatedEvent $event): void {
		$instances = $this->getInstances($event);
		if (empty($instances) && !$event->isAsync()) {
			return;
		}

		$wrapper = new EventWrapper();
		$wrapper->setEvent($event);
		$wrapper->setToken($this->uuid());
		$wrapper->setCreation(time());
		$wrapper->setSeverity($event->getSeverity());

		if ($event->isAsync()) {
			$wrapper->setInstance($this->configService->getLoopbackInstance());
			$this->eventWrapperRequest->save($wrapper);
		}

		foreach ($instances as $instance) {
			if ($event->getCircle()->isConfig(Circle::CFG_LOCAL)) {
				break;
			}

			$wrapper->setInstance($instance->getInstance());
			$wrapper->setInterface($instance->getInterface());
			$this->eventWrapperRequest->save($wrapper);
		}

		$request = new NC22Request('', Request::TYPE_POST);
		$this->configService->configureLoopbackRequest(
			$request,
			'circles.EventWrapper.asyncBroadcast',
			['token' => $wrapper->getToken()]
		);

		$event->setWrapperToken($wrapper->getToken());

		try {
			$this->doRequest($request);
		} catch (RequestNetworkException $e) {
			$this->e($e, ['wrapper' => $wrapper]);
		}
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @return RemoteInstance[]
	 * @throws RequestBuilderException
	 */
	public function getInstances(FederatedEvent $event): array {
		if (!$event->hasCircle()) {
			return [];
		}

		$circle = $event->getCircle();
		$broadcastAsFederated = $event->getData()->gBool('_broadcastAsFederated');
		$instances = $this->remoteRequest->getOutgoingRecipient($circle, $broadcastAsFederated);

		if ($event->isLimitedToInstanceWithMember()) {
			$knownInstances = $this->memberRequest->getMemberInstances($circle->getSingleId());
			$instances = array_filter(
				array_map(
					function (RemoteInstance $instance) use ($knownInstances) {
						if (!in_array($instance->getInstance(), $knownInstances)) {
							return null;
						}

						return $instance;
					}, $instances
				)
			);
		}

		// Check that in case of event has Member, the instance of that member is in the list.
		if ($event->hasMember()
			&& !$this->configService->isLocalInstance($event->getMember()->getInstance())) {
			$currentInstances = array_map(
				function (RemoteInstance $instance): string {
					return $instance->getInstance();
				}, $instances
			);

			if (!in_array($event->getMember()->getInstance(), $currentInstances)) {
				try {
					$instances[] = $this->remoteRequest->getFromInstance($event->getMember()->getInstance());
				} catch (RemoteNotFoundException $e) {
				}
			}
		}

		return $instances;
	}


	/**
	 * should be used to manage results from events, like sending mails on user creation
	 *
	 * @param string $token
	 */
	public function manageResults(string $token): void {
		$wrappers = $this->eventWrapperRequest->getByToken($token);

		$event = null;
		$results = [];
		foreach ($wrappers as $wrapper) {
			if ($wrapper->getStatus() !== EventWrapper::STATUS_DONE) {
				return;
			}

			if (is_null($event)) {
				$event = $wrapper->getEvent();
			}

			$results[$wrapper->getInstance()] = $wrapper->getResult();
		}

		if (is_null($event)) {
			return;
		}

		try {
			$gs = $this->getFederatedItem($event, false);
			$gs->result($event, $results);
		} catch (FederatedEventException $e) {
		}
	}
}
