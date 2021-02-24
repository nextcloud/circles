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
use OCA\Circles\Db\ShareRequest;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemLimitedToInstanceWithMember;
use OCA\Circles\IFederatedItemRequestOnly;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\FederatedShare;
use OCA\Circles\Model\ManagedModel;


/**
 * Class SharesSync
 *
 * @package OCA\Circles\FederatedItems
 */
class ShareLock implements
	IFederatedItem,
	IFederatedItemLimitedToInstanceWithMember,
	IFederatedItemRequestOnly {

// TODO: implements IFederatedItemRequestOnly. Exchange only between an instance and the instance that own the Circle

	use TStringTools;


	/** @var ShareRequest */
	private $shareRequest;


	public function __construct(ShareRequest $shareRequest) {
		$this->shareRequest = $shareRequest;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws InvalidIdException
	 */
	public function verify(FederatedEvent $event): void {

		$share = new FederatedShare();
		$share->setUniqueId($this->token(ManagedModel::ID_LENGTH));
		$share->setCircleId($event->getCircle()->getId());
		$share->setInstance($event->getIncomingOrigin());

		$this->shareRequest->save($share);

		$event->getData()->sObj('federatedShare', $this->shareRequest->getShare($share->getUniqueId()));
		// Create a unique ID, stored in database of the instance that 'owns' the Circle, that will 'lock'
		// the share to an instance. meaning, only this instance can update data
		// about a share
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

