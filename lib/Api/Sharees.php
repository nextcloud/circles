<?php
/**
 * Circles - bring cloud-users closer
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

use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\Share;


class Sharees {


	static protected function getContainer() {
		$app = new \OCA\Circles\AppInfo\Application();

		return $app->getContainer();
	}

	public static function search($search, $limit, $offset) {
		$c = self::getContainer();

		$data = $c->query('CirclesService')
				  ->listCircles(Circle::CIRCLES_ALL, $search, Member::LEVEL_MEMBER);

		$result = array(
			'exact'   => ['circles'],
			'circles' => []
		);

		foreach ($data as $entry) {
			if (strtolower($entry->getName()) === strtolower($search)) {
				$result['exact']['circles'][] = [
					'label' => $entry->getName(),
					'value' => [
						'shareType' => Share::SHARE_TYPE_CIRCLE,
						'circleInfo' => $entry->getInfo(),
						'shareWith' => $entry->getId()
					],
				];
			} else {
				$result['circles'][] = [
					'label' => $entry->getName(),
					'value' => [
						'shareType' => Share::SHARE_TYPE_CIRCLE,
						'circleInfo' => $entry->getInfo(),
						'shareWith' => $entry->getId()
					],
				];
			}
		}

		return $result;
	}


}