<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems;

use OCA\Circles\Db\ShareLockRequest;
use OCA\Circles\Exceptions\FederatedShareNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemDataRequestOnly;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\FederatedShare;
use OCA\Circles\Tools\Traits\TStringTools;

/**
 * Class ItemLock
 *
 * @package OCA\Circles\FederatedItems
 */
class ItemLock implements
	IFederatedItem,
	IFederatedItemDataRequestOnly {
	use TStringTools;


	public const STATUS_LOCKED = 'locked';
	public const STATUS_ALREADY_LOCKED = 'already_locked';
	public const STATUS_INSTANCE_LOCKED = 'instance_locked';


	/** @var ShareLockRequest */
	private $shareLockRequest;


	/**
	 * ItemLock constructor.
	 *
	 * @param ShareLockRequest $shareLockRequest
	 */
	public function __construct(ShareLockRequest $shareLockRequest) {
		$this->shareLockRequest = $shareLockRequest;
	}


	/**
	 * create lock in db if the lock does not exist for this circle.
	 * will fail if the lock already exist for anothr instance, even for another circle
	 *
	 * @param FederatedEvent $event
	 *
	 * @throws InvalidIdException
	 * @throws FederatedShareNotFoundException
	 */
	public function verify(FederatedEvent $event): void {
		$itemId = $event->getParams()->g('itemId');
		$this->shareLockRequest->confirmValidId($itemId);

		$status = '';
		try {
			$known = $this->shareLockRequest->getShare($itemId);

			if ($known->getInstance() === $event->getSender()) {
				$status = self::STATUS_ALREADY_LOCKED;
				$known = $this->shareLockRequest->getShare($itemId, $event->getCircle()->getSingleId());
			} else {
				$status = self::STATUS_INSTANCE_LOCKED;
			}
		} catch (FederatedShareNotFoundException $e) {
			$share = new FederatedShare();
			$share->setItemId($itemId);
			$share->setCircleId($event->getCircle()->getSingleId());
			$share->setInstance($event->getSender());

			$this->shareLockRequest->save($share);
			$known = $this->shareLockRequest->getShare($itemId);
			if ($status === '') {
				$status = self::STATUS_LOCKED;
			}
		}

		$known->setLockStatus($status);
		$event->setOutcome(['federatedShare' => $known]);
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
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}
}
