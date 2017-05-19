<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

namespace OCA\Circles\Api;


use OCA\Circles\AppInfo\Application;
use OCA\Circles\Model\Frame;

class Circles {


	protected static function getContainer() {
		$app = new Application();

		return $app->getContainer();
	}

	public static function createCircle($type, $name) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->createCircle($type, $name);
	}


	public static function listCircles($type, $name = '', $level = 0) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->listCircles($type, $name, $level);
	}


	public static function detailsCircle($circleId) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->detailsCircle($circleId);
	}


	public static function deleteCircle($circleId) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->removeCircle($circleId);
	}


	public static function shareToCircle(
		int $circleId, string $source, string $type, array $payload, string $broadcaster
	) {
		$c = self::getContainer();

		$frame = new Frame($source, $type);
		$frame->setCircleId($circleId);
		$frame->setPayload($payload);

		return $c->query('SharesService')
				 ->createFrame($frame, $broadcaster);
	}


	public static function addMember($circleId, $userId) {
		$c = self::getContainer();

		return $c->query('MembersService')
				 ->addMember($circleId, $userId);
	}

}