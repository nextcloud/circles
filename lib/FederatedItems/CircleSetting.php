<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Tools\Traits\TDeserialize;

class CircleSetting implements
	IFederatedItem,
	IFederatedItemAsyncProcess {
	use TDeserialize;


	/** @var CircleRequest */
	private $circleRequest;


	/**
	 * CircleConfig constructor.
	 *
	 * @param CircleRequest $circleRequest
	 */
	public function __construct(CircleRequest $circleRequest) {
		$this->circleRequest = $circleRequest;
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();

		$initiatorHelper = new MemberHelper($circle->getInitiator());
		$initiatorHelper->mustBeAdmin();

		$params = $event->getParams();
		$setting = $params->g('setting');
		$value = $params->gBool('unset') ? null : $params->g('value');

		$settings = $circle->getSettings();

		if (!is_null($value)) {
			$settings[$setting] = $value;
		} elseif (array_key_exists($setting, $settings)) {
			unset($settings[$setting]);
		}

		$event->getData()->sArray('settings', $settings);

		$new = clone $circle;
		$new->setSettings($settings);

		$event->setOutcome($this->serialize($new));
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$circle = clone $event->getCircle();
		$settings = $event->getData()->gArray('settings');

		$circle->setSettings($settings);
		// TODO list imported from FederatedItem/CircleConfig.php - need to check first there.

		// TODO: Check locally that circle is not un-federated during the process
		// TODO: if the circle is managed remotely, remove the circle locally
		// TODO: if the circle is managed locally, remove non-local users

		// TODO: Check locally that circle is not federated during the process
		// TODO: sync if it is to broadcast to Trusted RemoteInstance

		$this->circleRequest->updateSettings($circle);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}
}
