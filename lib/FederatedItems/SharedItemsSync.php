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


namespace OCA\Circles\FederatedItems;

use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemLimitedToInstanceWithMembership;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\CircleEventService;

/**
 * Class SharesSync
 *
 * @package OCA\Circles\FederatedItems
 */
class SharedItemsSync implements
	IFederatedItem,
	IFederatedItemLimitedToInstanceWithMembership {
	// TODO: testing that IFederatedItemLimitedToInstanceWithMembership is working (since multi-instance)
	// TODO: implements IFederatedItemInstanceMember to the check procedure

	/** @var CircleEventService */
	private $circleEventService;


	public function __construct(CircleEventService $circleEventService) {
		$this->circleEventService = $circleEventService;
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$this->circleEventService->onSharedItemsSyncRequested($event);

		$event->setResult(new SimpleDataStore(['shares' => 'ok']));
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}
}
