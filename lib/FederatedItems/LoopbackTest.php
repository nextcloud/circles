<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems;

use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemLoopbackTest;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Tools\Model\SimpleDataStore;

/**
 * Class LoopbackTest
 *
 * @package OCA\Circles\FederatedItems
 */
class LoopbackTest implements
	IFederatedItem,
	IFederatedItemAsyncProcess,
	IFederatedItemLoopbackTest {
	public const VERIFY = 17;
	public const MANAGE = 42;


	/**
	 * LoopbackTest constructor.
	 */
	public function __construct() {
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
		$event->setData(new SimpleDataStore(['verify' => self::VERIFY]));
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$event->setResult(new SimpleDataStore(['manage' => self::MANAGE]));
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}
}
