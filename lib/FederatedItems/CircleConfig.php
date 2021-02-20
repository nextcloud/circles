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


use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Service\ConfigService;


/**
 * Class CircleConfig
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleConfig implements IFederatedItem {


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var ConfigService */
	private $configService;


	/**
	 * CircleConfig constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param ConfigService $configService
	 */
	public function __construct(
		CircleRequest $circleRequest, MemberRequest $memberRequest, ConfigService $configService
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->configService = $configService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$config = $event->getData()->gInt('config');

		$initiatorHelper = new MemberHelper($circle->getInitiator());
		$initiatorHelper->mustBeAdmin();

		$listing = Circle::$DEF_CFG_CORE_FILTER;
		if (!$circle->isConfig(Circle::CFG_SYSTEM)) {
			$listing = array_merge($listing, Circle::$DEF_CFG_SYSTEM_FILTER);
		}

		$confirmed = true;
		foreach ($listing as $item) {
			if ($circle->isConfig($item, $config)) {
				$confirmed = false;
			}
		}

		if (!$confirmed || $config > Circle::$DEF_CFG_MAX) {
			throw new FederatedItemException('Configuration value is not valid');
		}

		$event->setDataOutcome(['circle' => $circle]);
		$event->setReadingOutcome('Configuration have been updated');
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$circle = clone $event->getCircle();
		$config = $event->getData()->gInt('config');

		$circle->setConfig($config);
		$this->circleRequest->updateConfig($circle);
	}


	/**
	 * @param FederatedEvent[] $events
	 */
	public function result(array $events): void {
	}

}

