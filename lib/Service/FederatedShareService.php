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
use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Exceptions\ItemNotFoundException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\UnknownTypeException;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\ShareLock;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\FederatedShare;


/**
 * Class FederatedShareService
 *
 * @package OCA\Circles\Service
 */
class FederatedShareService extends NC21Signature {


	/** @var FederatedEventService */
	private $federatedEventService;


	/**
	 * FederatedEventService constructor.
	 *
	 * @param FederatedEventService $federatedEventService
	 */
	public function __construct(FederatedEventService $federatedEventService) {
		$this->federatedEventService = $federatedEventService;
	}


	/**
	 * @param Circle $circle
	 *
	 * @return FederatedShare
	 * @throws FederatedEventDSyncException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws InvalidItemException
	 * @throws ItemNotFoundException
	 * @throws UnknownTypeException
	 */
	public function getFreshFederatedShare(Circle $circle): FederatedShare {
		$event = new FederatedEvent(ShareLock::class);
		$event->setCircle($circle);

		$data = $this->federatedEventService->newEvent($event);

		/** @var FederatedShare $share */
		$share = $data->gObj('federatedShare', FederatedShare::class);

		return $share;
	}

}

