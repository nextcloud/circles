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
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Tools\Traits\TDeserialize;

class CircleSetting implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemAsyncProcess {
	use TDeserialize;

	private CircleRequest $circleRequest;
	private ShareTokenService $shareTokenService;
	private ConfigService $configService;

	public function __construct(
		CircleRequest $circleRequest,
		ShareTokenService $shareTokenService,
		ConfigService $configService
	) {
		$this->circleRequest = $circleRequest;
		$this->shareTokenService = $shareTokenService;
		$this->configService = $configService;
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

		// in case changed settings is about a static password change,
		// refresh password on all previous shares
		if ($setting === 'password_single' && $value !== null) {
			$event->getData()->sBool('refresh_share_password', true);
		}

		// in case changed settings is about removing the enforce_password flag
		// remove password on previous shares (global configuration will be checked in manage())
		if ($setting === 'enforce_password') {
			$event->getData()->sBool('remove_share_password', true);
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

		// refresh share password (in case of static password changes)
		// only is password are enforced for this Circle
		if ($event->getData()->gBool('refresh_share_password')
			&& $this->configService->enforcePasswordOnSharedFile($circle)) {
			$this->shareTokenService->updateSharePassword(
				$circle->getSingleId(),
				$settings['password_single'] ?? ''
			);
		}

		// remove share password on request
		// only if password are not enforced for this Circle
		if ($event->getData()->gBool('remove_share_password')
			&& !$this->configService->enforcePasswordOnSharedFile($circle)) {
			$this->shareTokenService->removeSharePassword($circle->getSingleId());
		}
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}
}
