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

use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\DeprecatedMember;

/**
 * Class CircleStatus
 *
 * @package OCA\Circles\GlobalScale
 */
class CircleStatus extends AGlobalScaleEvent {
	public const STATUS_ERROR = -1;
	public const STATUS_OK = 1;
	public const STATUS_NOT_OWNER = 8;
	public const STATUS_NOT_FOUND = 404;


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
	}


	/**
	 * @param GSEvent $event
	 */
	public function manage(GSEvent $event): void {
		$circle = $event->getDeprecatedCircle();
		$status = self::STATUS_ERROR;

		try {
			$this->circlesRequest->forceGetCircle($circle->getUniqueId());
			$owners = $this->membersRequest->forceGetMembers($circle->getUniqueId(), DeprecatedMember::LEVEL_OWNER);
			if (!empty($owners)) {
				$owner = $owners[0];
				if ($owner->getInstance() === '') {
					$status = self::STATUS_OK;
				} else {
					$status = self::STATUS_NOT_OWNER;
					$event->getData()
						  ->sObj('supposedOwner', $owner);
				}
			}
		} catch (CircleDoesNotExistException $e) {
			$status = self::STATUS_NOT_FOUND;
		}

		$event->getData()
			  ->sInt('status', $status);
	}


	/**
	 * @param GSEvent[] $events
	 */
	public function result(array $events): void {
	}
}
