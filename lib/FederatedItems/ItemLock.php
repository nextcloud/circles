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


use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Db\ShareLocksRequest;
use OCA\Circles\Exceptions\FederatedShareNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemCircleCheckNotRequired;
use OCA\Circles\IFederatedItemDataRequestOnly;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\FederatedShare;


/**
 * Class SharesSync
 *
 * @package OCA\Circles\FederatedItems
 */
class ItemLock implements
	IFederatedItem,
	IFederatedItemCircleCheckNotRequired,
	IFederatedItemDataRequestOnly {


	use TStringTools;


	const STATUS_LOCKED = 'locked';
	const STATUS_ALREADY_LOCKED = 'already_locked';
	const STATUS_INSTANCE_LOCKED = 'instance_locked';


	/** @var ShareLocksRequest */
	private $shareLockRequest;


	public function __construct(ShareLocksRequest $shareLockRequest) {
		$this->shareLockRequest = $shareLockRequest;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws InvalidIdException
	 * @throws FederatedShareNotFoundException
	 */
	public function verify(FederatedEvent $event): void {
		$itemId = $event->getData()->g('itemId');
		$this->shareLockRequest->confirmValidId($itemId);

		try {
			$known = $this->shareLockRequest->getShare($itemId);
			if ($known->getInstance() !== $event->getIncomingOrigin()) {
				$known->setLockStatus(self::STATUS_ALREADY_LOCKED);
			} else {
				$known->setLockStatus(self::STATUS_INSTANCE_LOCKED);
			}
		} catch (FederatedShareNotFoundException $e) {
			$share = new FederatedShare();
			$share->setItemId($itemId);
			$share->setInstance($event->getIncomingOrigin());

			$this->shareLockRequest->save($share);
			$known = $this->shareLockRequest->getShare($itemId);
			$known->setLockStatus(self::STATUS_LOCKED);
		}

		$event->setDataOutcome(['federatedShare' => $known]);
		$event->getData()->s('status', self::STATUS_LOCKED);
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
//		$this->circleEventService->onSharedItemsSyncRequested($event);
//
//		$event->setResult(new SimpleDataStore(['shares' => 'ok']));
	}


	/**
	 * @param FederatedEvent[] $events
	 */
	public function result(array $events): void {
	}

}

