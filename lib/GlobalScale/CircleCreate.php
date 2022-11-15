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

use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Model\GlobalScale\GSEvent;

/**
 * Class CircleCreate
 *
 * @package OCA\Circles\GlobalScale
 */
class CircleCreate extends AGlobalScaleEvent {
	/**
	 * Circles are created on the original instance, so do no check;
	 *
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		//parent::verify($event, $localCheck, $mustBeChecked);
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws MemberAlreadyExistsException
	 */
	public function manage(GSEvent $event): void {
		if (!$event->hasCircle()) {
			return;
		}

		$circle = $event->getDeprecatedCircle();
		$this->circlesRequest->createCircle($circle);

		$owner = $circle->getOwner();
		if ($owner->getInstance() === '') {
			$owner->setInstance($event->getSource());
		}
		$this->membersRequest->createMember($owner);

		$this->eventsService->onCircleCreation($circle);
	}


	/**
	 * @param GSEvent[] $events
	 */
	public function result(array $events): void {
	}
}
