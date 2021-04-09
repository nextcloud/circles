<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 * @author Daniel Tygel <dtygel@eita.org.br>
 *
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


use daita\MySmallPhpTools\Model\SimpleDataStore;
use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Events\CircleCreatedEvent;
use OCA\Circles\Events\CircleDestroyedEvent;
use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Events\CircleMemberAddedEvent;
use OCA\Circles\Events\CircleMemberEditedEvent;
use OCA\Circles\Events\CircleMemberRemovedEvent;
use OCA\Circles\Events\CreatingCircleEvent;
use OCA\Circles\Events\DestroyingCircleEvent;
use OCA\Circles\Events\EditingCircleMemberEvent;
use OCA\Circles\Events\RemovingCircleMemberEvent;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCP\EventDispatcher\IEventDispatcher;


/**
 * Class EventService
 *
 * @package OCA\Circles\Service
 */
class EventService {


	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var MembershipService */
	private $membershipService;


	/**
	 * EventService constructor.
	 *
	 * @param IEventDispatcher $eventDispatcher
	 * @param MembershipService $membershipService
	 */
	public function __construct(IEventDispatcher $eventDispatcher, MembershipService $membershipService) {
		$this->eventDispatcher = $eventDispatcher;
		$this->membershipService = $membershipService;
	}


	/**
	 * onCircleCreation()
	 *
	 * Called when a circle is created.
	 * Broadcast an activity to the cloud
	 * We won't do anything if the circle is not PUBLIC or CLOSED
	 *
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
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function circleDestroying(FederatedEvent $federatedEvent): void {
		$event = new DestroyingCircleEvent($federatedEvent);
		$this->eventDispatcher->dispatchTyped($event);
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
	public function memberAdding(FederatedEvent $federatedEvent): void {
		$member = $federatedEvent->getMember();
		$this->membershipService->onUpdate($member->getSingleId());

		$event = new AddingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::INVITED);

		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberAdded(FederatedEvent $federatedEvent, array $results): void {
		$federatedEvent = new CircleMemberAddedEvent($federatedEvent, $results);
		$federatedEvent->setType(CircleGenericEvent::INVITED);
		$this->eventDispatcher->dispatchTyped($federatedEvent);
	}


	public function memberInvited(Member $member): void {
	}


	public function memberRequest(Member $member): void {
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberJoining(FederatedEvent $federatedEvent): void {
		$member = $federatedEvent->getMember();
		$this->membershipService->onUpdate($member->getSingleId());

		$event = new AddingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::JOINED);

		$this->eventDispatcher->dispatchTyped($event);
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
	public function memberEditing(FederatedEvent $federatedEvent): void {
		$event = new EditingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::LEVEL);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberEdited(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberEditedEvent($federatedEvent, $results);
		$event->setType(CircleGenericEvent::LEVEL);
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
	}


	/**
	 * @param ShareWrapper $wrappedShare
	 */
	public function shareCreated(ShareWrapper $wrappedShare): void {
//		Circles::shareToCircle(
//			$circle->getUniqueId(), 'files', '',
//			['id' => $share->getId(), 'share' => $this->shareObjectToArray($share)],
//			'\OCA\Circles\Circles\FileSharingBroadcaster'
//		);
	}




//	/**
//	 * @param FederatedEvent $federatedEvent
//	 */
//	public function onSharedItemsSyncRequested(FederatedEvent $federatedEvent) {
//		$event = new SharedItemsSyncRequestedEvent($federatedEvent);
//		$this->eventDispatcher->dispatchTyped($event);
//	}

}

