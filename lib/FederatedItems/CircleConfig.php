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
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Tools\Traits\TDeserialize;

/**
 * Class CircleConfig
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleConfig implements
	IFederatedItem {
	use TDeserialize;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var PermissionService */
	private $permissionService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CircleConfig constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param PermissionService $permissionService
	 * @param ConfigService $configService
	 */
	public function __construct(
		CircleRequest $circleRequest,
		PermissionService $permissionService,
		ConfigService $configService
	) {
		$this->circleRequest = $circleRequest;
		$this->permissionService = $permissionService;
		$this->configService = $configService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 * @throws RequestBuilderException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$config = $event->getParams()->gInt('config');

		$initiatorHelper = new MemberHelper($circle->getInitiator());
		$initiatorHelper->mustBeAdmin();

		$listing = Circle::$DEF_CFG_CORE_FILTER;
		if (!$circle->isConfig(Circle::CFG_SYSTEM)) {
			$listing = array_merge($listing, Circle::$DEF_CFG_SYSTEM_FILTER);
		}

		// filtering config values when not using Super Session
		if (!$event->getParams()->gBool('superSession')) {
			if ($circle->isConfig(Circle::CFG_APP)) {
				$config |= Circle::CFG_APP;
			} else {
				$config &= ~Circle::CFG_APP;
			}
		}

		$confirmed = true;
		foreach ($listing as $item) {
			if ($circle->isConfig($item, $config)) {
				$confirmed = false;
			}
		}

		if ($circle->isConfig(Circle::CFG_LOCAL, $config)
			&& !$circle->isConfig(Circle::CFG_LOCAL)) {
			$config -= Circle::CFG_LOCAL;
		}

		if (!$circle->isConfig(Circle::CFG_LOCAL, $config)
			&& $circle->isConfig(Circle::CFG_LOCAL)) {
			$config += Circle::CFG_LOCAL;
		}

		if (!$circle->isConfig(Circle::CFG_OPEN, $config)
			&& $circle->isConfig(Circle::CFG_OPEN)
			&& $circle->isConfig(Circle::CFG_REQUEST, $config)
		) {
			$config -= Circle::CFG_REQUEST;
		}

		if ($circle->isConfig(Circle::CFG_REQUEST, $config)
			&& !$circle->isConfig(Circle::CFG_REQUEST)
			&& !$circle->isConfig(Circle::CFG_OPEN, $config)) {
			$config += Circle::CFG_OPEN;
		}

		if (!$circle->isConfig(Circle::CFG_ROOT, $config)
			&& $circle->isConfig(Circle::CFG_ROOT)
			&& $circle->isConfig(Circle::CFG_FEDERATED, $config)) {
			$config -= Circle::CFG_FEDERATED;
			// TODO: Broadcast message to other instances about loosing federated tag.
		}

		if ($circle->isConfig(Circle::CFG_FEDERATED, $config)
			&& !$circle->isConfig(Circle::CFG_FEDERATED)
			&& !$circle->isConfig(Circle::CFG_ROOT, $config)) {
			$config += Circle::CFG_ROOT;
			// TODO: Check locally that circle is not a member of another circle.
			// TODO  in that case, remove the membership (and update the memberships)
			$event->getData()->sBool('_broadcastAsFederated', true);
		}

		if (!$confirmed || $config > Circle::$DEF_CFG_MAX) {
			throw new FederatedItemBadRequestException('Configuration value is not valid');
		}

		$new = clone $circle;
		$new->setConfig($config);
		$this->permissionService->confirmAllowedCircleTypes($new, $circle);

		$event->getData()->sInt('config', $new->getConfig());

		$event->setOutcome($this->serialize($new));
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$circle = clone $event->getCircle();
		$config = $event->getData()->gInt('config');

		$circle->setConfig($config);
		// TODO: Check locally that circle is not un-federated during the process
		// TODO: if the circle is managed remotely, remove the circle locally
		// TODO: if the circle is managed locally, remove non-local users

		// TODO: Check locally that circle is not federated during the process
		// TODO: sync if it is to broadcast to Trusted RemoteInstance

		$this->circleRequest->updateConfig($circle);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}
}
