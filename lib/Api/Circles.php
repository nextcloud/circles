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

class Circles {


	static protected function getContainer() {
		$app = new Application();

		return $app->getContainer();
	}

	public static function createCircle($name, $type) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->createCircle($name, $type);
	}


	public static function listCircles($type, $level = 0) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->listCircles($type, $level);
	}


	public static function detailsCircle($circle_id) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->detailsCircle($circle_id);
	}


}