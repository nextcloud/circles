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


namespace OCA\Circles\GlobalScale;

use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\GlobalScaleDSyncException;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\DeprecatedMember;

/**
 * Class CircleUpdate
 *
 * @package OCA\Circles\GlobalScale
 */
class CircleUpdate extends AGlobalScaleEvent {


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 *
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws GlobalScaleDSyncException
	 * @throws GlobalScaleEventException
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		parent::verify($event, $localCheck, true);

		$circle = $event->getDeprecatedCircle();
		$data = $event->getData();
		$viewer = $circle->getHigherViewer();
		if (!$data->gBool('local_admin') && $viewer->getLevel() !== DeprecatedMember::LEVEL_OWNER) {
			throw new GlobalScaleDSyncException('Member is not Owner');
		}
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws CircleAlreadyExistsException
	 * @throws ConfigNoCircleAvailableException
	 */
	public function manage(GSEvent $event): void {
		if (!$event->hasCircle()) {
			return;
		}

		$circle = $event->getDeprecatedCircle();
		$settings = $event->getData()
						  ->gArray('settings');
		$ak = array_keys($settings);
		foreach ($ak as $k) {
			if ($k === 'password_single') {
				$circle->setPasswordSingle($settings[$k]);
			}
			$circle->setSetting($k, $settings[$k]);
		}

		$owner = $circle->getHigherViewer();
		$this->circlesRequest->updateCircle($circle, $owner->getUserId());

		$oldSettings = array_merge(
			$circle->getSettings(),
			[
				'circle_name' => $circle->getName(),
				'circle_desc' => $circle->getDescription(),
			]
		);

		$data = $event->getData();

		if ($data->gBool('password_changed')) {
			$this->circlesService->updatePasswordOnShares($circle);
		}

		$this->eventsService->onSettingsChange($circle, $oldSettings);
	}


	/**
	 * @param GSEvent[] $events
	 */
	public function result(array $events): void {
	}
}
