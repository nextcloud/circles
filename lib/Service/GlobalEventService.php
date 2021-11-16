<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
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

use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc23\TNC23Logger;
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
use OCA\Circles\Events\MembershipsCreatedEvent;
use OCA\Circles\Events\MembershipsRemovedEvent;
use OCA\Circles\Events\RemovingCircleMemberEvent;
use OCA\Circles\Events\RequestingCircleMemberEvent;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\Mount;
use OCA\Circles\Model\ShareWrapper;
use OCP\EventDispatcher\IEventDispatcher;

class GlobalEventService {
	use TNC23Logger;


	/** @var IEventDispatcher */
	private $eventDispatcher;


	/**
	 * EventService constructor.
	 *
	 * @param IEventDispatcher $eventDispatcher
	 */
	public function __construct() {
		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param FederatedEvent $federatedEvent
	 */
	public function onSharedItemsSyncRequested(FederatedEvent $federatedEvent) {

	}
}
