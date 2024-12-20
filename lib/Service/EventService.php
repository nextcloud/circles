<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Service;

use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Events\CircleCreatedEvent;
use OCA\Circles\Events\CircleDestroyedEvent;
use OCA\Circles\Events\CircleEditedEvent;
use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Events\CircleMemberAddedEvent;
use OCA\Circles\Events\CircleMemberEditedEvent;
use OCA\Circles\Events\CircleMemberRemovedEvent;
use OCA\Circles\Events\CircleMemberRequestedEvent;
use OCA\Circles\Events\CreatingCircleEvent;
use OCA\Circles\Events\DestroyingCircleEvent;
use OCA\Circles\Events\EditingCircleEvent;
use OCA\Circles\Events\EditingCircleMemberEvent;
use OCA\Circles\Events\Files\CreatingFileShareEvent;
use OCA\Circles\Events\Files\FileShareCreatedEvent;
use OCA\Circles\Events\Files\PreparingFileShareEvent;
use OCA\Circles\Events\MembershipsCreatedEvent;
use OCA\Circles\Events\MembershipsRemovedEvent;
use OCA\Circles\Events\PreparingCircleMemberEvent;
use OCA\Circles\Events\RemovingCircleMemberEvent;
use OCA\Circles\Events\RequestingCircleMemberEvent;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\Mount;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCP\EventDispatcher\IEventDispatcher;

class EventService {
	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private ActivityService $activityService,
	) {
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function circleCreating(FederatedEvent $federatedEvent): void {
		$event = new CreatingCircleEvent($federatedEvent);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function circleCreated(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleCreatedEvent($federatedEvent, $results);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onCircleCreation($event->getCircle());
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function circleEditing(FederatedEvent $federatedEvent): void {
		$event = new EditingCircleEvent($federatedEvent);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function circleEdited(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleEditedEvent($federatedEvent, $results);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function circleDestroying(FederatedEvent $federatedEvent): void {
		$event = new DestroyingCircleEvent($federatedEvent);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onCircleDestruction($event->getCircle());
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function circleDestroyed(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleDestroyedEvent($federatedEvent, $results);
		$this->eventDispatcher->dispatchTyped($event);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberPreparing(FederatedEvent $federatedEvent): void {
		$event = new PreparingCircleMemberEvent($federatedEvent);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberAdding(FederatedEvent $federatedEvent): void {
		$event = new AddingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::ADDED);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onMemberNew($event->getCircle(), $event->getMember(), CircleGenericEvent::ADDED);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberAdded(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberAddedEvent($federatedEvent, $results);
		$event->setType(CircleGenericEvent::ADDED);
		$this->eventDispatcher->dispatchTyped($event);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberInviting(FederatedEvent $federatedEvent): void {
		$event = new RequestingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::INVITED);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onMemberNew($event->getCircle(), $event->getMember(), CircleGenericEvent::INVITED);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberInvited(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberRequestedEvent($federatedEvent, $results);
		$event->setType(CircleGenericEvent::INVITED);
		$this->eventDispatcher->dispatchTyped($event);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberRequesting(FederatedEvent $federatedEvent): void {
		$event = new RequestingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::REQUESTED);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onMemberNew($event->getCircle(), $event->getMember(), CircleGenericEvent::REQUESTED);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberRequested(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberRequestedEvent($federatedEvent, $results);
		$event->setType(CircleGenericEvent::REQUESTED);
		$this->eventDispatcher->dispatchTyped($event);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberJoining(FederatedEvent $federatedEvent): void {
		$event = new AddingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::JOINED);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onMemberNew($event->getCircle(), $event->getMember(), CircleGenericEvent::JOINED);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberJoined(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberAddedEvent($federatedEvent, $results);
		$event->setType(CircleGenericEvent::JOINED);
		$this->eventDispatcher->dispatchTyped($event);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberLevelEditing(FederatedEvent $federatedEvent): void {
		$event = new EditingCircleMemberEvent($federatedEvent);
		$event->setLevel($federatedEvent->getData()->gInt('level'));
		$event->setType(CircleGenericEvent::LEVEL);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onMemberLevel($event->getCircle(), $event->getMember(), $event->getLevel());
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberLevelEdited(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberEditedEvent($federatedEvent, $results);
		$event->setNewLevel($federatedEvent->getData()->gInt('level'));
		$event->setType(CircleGenericEvent::LEVEL);
		$this->eventDispatcher->dispatchTyped($event);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberNameEditing(FederatedEvent $federatedEvent): void {
		$event = new EditingCircleMemberEvent($federatedEvent);
		$event->setDisplayName($federatedEvent->getData()->g('displayName'));
		$event->setType(CircleGenericEvent::NAME);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberNameEdited(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberEditedEvent($federatedEvent, $results);
		$event->setNewDisplayName($federatedEvent->getData()->g('displayName'));
		$event->setType(CircleGenericEvent::NAME);
		$this->eventDispatcher->dispatchTyped($event);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberRemoving(FederatedEvent $federatedEvent): void {
		$event = new RemovingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::REMOVED);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberRemoved(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberRemovedEvent($federatedEvent, $results);
		$event->setType(CircleGenericEvent::REMOVED);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onMemberRemove($event->getCircle(), $event->getMember(), CircleGenericEvent::REMOVED);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberLeaving(FederatedEvent $federatedEvent): void {
		$event = new RemovingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::LEFT);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param SimpleDataStore[] $results
	 */
	public function memberLeft(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberRemovedEvent($federatedEvent, $results);
		$event->setType(CircleGenericEvent::LEFT);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onMemberRemove($event->getCircle(), $event->getMember(), CircleGenericEvent::LEFT);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function fileSharePreparing(FederatedEvent $federatedEvent): void {
		$event = new PreparingFileShareEvent($federatedEvent);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param Mount|null $mount
	 */
	public function fileShareCreating(FederatedEvent $federatedEvent, ?Mount $mount = null): void {
		$event = new CreatingFileShareEvent($federatedEvent);
		if (!is_null($mount)) {
			$event->setMount($mount);
		}
		$this->eventDispatcher->dispatchTyped($event);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 * @param SimpleDataStore[] $result
	 */
	public function fileShareCreated(FederatedEvent $federatedEvent, array $result): void {
		$event = new FileShareCreatedEvent($federatedEvent, $result);
		$this->eventDispatcher->dispatchTyped($event);
		$this->activityService->onShareNew($event->getCircle(), $event->getFederatedEvent());
	}


	/**
	 * @param Membership[] $new
	 */
	public function membershipsCreated(array $new): void {
		$event = new MembershipsCreatedEvent($new);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param Membership[] $deprecated
	 */
	public function membershipsRemoved(array $deprecated): void {
		$event = new MembershipsRemovedEvent($deprecated);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param ShareWrapper $wrappedShare
	 */
	public function localShareCreated(ShareWrapper $wrappedShare): void {
	}

	/**
	 * @param ShareWrapper $wrappedShare
	 */
	public function localShareDeleted(ShareWrapper $wrappedShare): void {
	}

	/**
	 * @param ShareWrapper $wrappedShare
	 */
	public function federatedShareDeleted(ShareWrapper $wrappedShare): void {
	}
}
