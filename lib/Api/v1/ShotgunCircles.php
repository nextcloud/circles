<?php
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

namespace OCA\Circles\Api\v1;


use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\ApiVersionIncompatibleException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\FederatedLinkService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\SharingFrameService;
use OCP\Util;

/**
 * Better use the other one.
 *
 * This is a shotgun class; don't blow your foot.
 */
class ShotgunCircles {

	protected static function getContainer() {
		$app = new Application();

		return $app->getContainer();
	}


	/**
	 * ShotgunCircles::getSharesFromCircle();
	 *
	 * This function will returns all item (array) shared to a specific circle identified by its Id,
	 * source and type.
	 *
	 * Warning - please use Circles::getSharesFromCircle for any interaction with the current user
	 * session.
	 *
	 * @param string $circleUniqueId
	 * @param string $userId
	 *
	 * @return SharingFrame[]
	 */
	public static function getSharesFromCircle($circleUniqueId, $userId = '') {
		$c = self::getContainer();

		return $c->query(SharingFrameService::class)
				 ->forceGetFrameFromCircle($circleUniqueId, $userId);
	}


}