<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\AppInfo\Application;
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
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Class EventService
 *
 * @package OCA\Circles\Service
 */
class EventService {
	use TNCLogger;


	/** @var IEventDispatcher */
	private $eventDispatcher;


	/**
	 * EventService constructor.
	 *
	 * @param IEventDispatcher $eventDispatcher
	 */
	public function __construct(IEventDispatcher $eventDispatcher) {
		$this->eventDispatcher = $eventDispatcher;

		$this->setup('app', Application::APP_ID);
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
		$event->setType(CircleGenericEvent::INVITED);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function memberAdded(FederatedEvent $federatedEvent, array $results): void {
		$event = new CircleMemberAddedEvent($federatedEvent, $results);
		$event->setType(CircleGenericEvent::INVITED);
		$this->eventDispatcher->dispatchTyped($event);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function memberInviting(FederatedEvent $federatedEvent): void {
		$event = new RequestingCircleMemberEvent($federatedEvent);
		$event->setType(CircleGenericEvent::INVITED);
		$this->eventDispatcher->dispatchTyped($event);
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
	 * @param Mount $mount
	 */
	public function federatedShareCreated(ShareWrapper $wrappedShare, Mount $mount): void {
//		Circles::shareToCircle(
//			$circle->getUniqueId(), 'files', '',
//			['id' => $share->getId(), 'share' => $this->shareObjectToArray($share)],
//			'\OCA\Circles\Circles\FileSharingBroadcaster'
//		);
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



//	/**
//	 * @param FederatedEvent $federatedEvent
//	 */
//	public function onSharedItemsSyncRequested(FederatedEvent $federatedEvent) {
//		$event = new SharedItemsSyncRequestedEvent($federatedEvent);
//		$this->eventDispatcher->dispatchTyped($event);
//	}
}
