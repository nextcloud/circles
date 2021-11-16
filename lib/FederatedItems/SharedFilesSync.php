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


namespace OCA\Circles\FederatedItems;

use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemForwardResult;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemLimitedToInstanceWithMembership;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\GlobalEventService;
use OCA\Circles\Service\ShareWrapperService;


/**
 *
 */
class SharedFilesSync implements
	IFederatedItem,
	IFederatedItemForwardResult,
	IFederatedItemHighSeverity,
	IFederatedItemLimitedToInstanceWithMembership {


	/** @var ShareWrapperService */
	private $shareWrapperService;

	/** @var GlobalEventService */
	private $globalEventService;


	public function __construct(
		ShareWrapperService $shareWrapperService,
		GlobalEventService $globalEventService
	) {
		$this->shareWrapperService = $shareWrapperService;
		$this->globalEventService = $globalEventService;
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		\OC::$server->getLogger()->log(3, '### MANAGE');
		$this->globalEventService->onSharedItemsSyncRequested($event);
		$circle = $event->getCircle();

		$wrappedShares = $this->shareWrapperService->getSharesToCircle($circle->getSingleId());
		$event->setResult(new SimpleDataStore(['shares' => $wrappedShares]));
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		\OC::$server->getLogger()->log(3, '### RESULT: ' . json_encode($results));
	}
}
