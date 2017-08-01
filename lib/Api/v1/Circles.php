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

namespace OCA\Circles\Api\v1;


use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\ApiVersionIncompatibleException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;

class Circles {

	const API_VERSION = [0, 10, 0];

	protected static function getContainer() {
		$app = new Application();

		return $app->getContainer();
	}


	/**
	 * Circles::version();
	 *
	 * returns the current version of the API
	 *
	 * @return int[]
	 */
	public static function version() {
		return self::API_VERSION;
	}


	/**
	 * Circles::compareVersion();
	 *
	 * Compare and return true if version is compatible.
	 * Exception otherwise.
	 *
	 * @param array $apiVersion
	 *
	 * @return bool
	 * @throws ApiVersionIncompatibleException
	 */
	public static function compareVersion($apiVersion) {
		if ((int)$apiVersion[0] !== Circles::API_VERSION[0]
			|| (int)$apiVersion[1] !== Circles::API_VERSION[1]) {
			throw new ApiVersionIncompatibleException('api_not_compatible');
		}

		return true;
	}


	/**
	 * Circles::createCircle();
	 *
	 * Create a new circle and make the current user its owner.
	 * You must specify type and name. type is one of this value:
	 *
	 * CIRCLES_PERSONAL is 1 or 'personal'
	 * CIRCLES_SECRET is 2 or 'secret'
	 * CIRCLES_CLOSED is 4 or 'closed'
	 * CIRCLES_PUBLIC is 8 or 'public'
	 *
	 * @param mixed $type
	 * @param string $name
	 *
	 * @return Circle
	 */
	public static function createCircle($type, $name) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->createCircle($type, $name);
	}


	/**
	 * Circles::joinCircle();
	 *
	 * This function will make the current user joining a circle identified by its Id.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return Member
	 */
	public static function joinCircle($circleUniqueId) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->joinCircle($circleUniqueId);
	}


	/**
	 * Circles::leaveCircle();
	 *
	 * This function will make the current user leaving the circle identified by its Id. Will fail
	 * if user is the owner of the circle.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return Member
	 */
	public static function leaveCircle($circleUniqueId) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->leaveCircle($circleUniqueId);
	}


	/**
	 * Circles::listCircles();
	 *
	 * This function list all circles fitting a search regarding its name and the level and the
	 * rights from the current user. In case of Secret circle, name needs to be complete so the
	 * circle is included in the list (or if the current user is the owner)
	 *
	 * example: Circles::listCircles(Circle::CIRCLES_ALL, '', 8, callback); will returns all
	 * circles when the current user is at least an Admin.
	 *
	 * @param mixed $type
	 * @param string $name
	 * @param int $level
	 *
	 * @return Circle[]
	 */
	public static function listCircles($type, $name = '', $level = 0) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->listCircles($type, $name, $level);
	}


	/**
	 * Circles::joinedCircles();
	 *
	 * Return all the circle the current user is a member.
	 *
	 * @return Circle[]
	 */
	public static function joinedCircles() {
		return self::listCircles(Circle::CIRCLES_ALL, '', Member::LEVEL_MEMBER);
	}


	/**
	 * Circles::detailsCircle();
	 *
	 * WARNING - This function is called by the core - WARNING
	 *                 Do not change it
	 *
	 * Returns details on the circle. If the current user is a member, the members list will be
	 * return as well.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return Circle
	 */
	public static function detailsCircle($circleUniqueId) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->detailsCircle($circleUniqueId);
	}


	/**
	 * Circles::settingsCircle();
	 *
	 * Save the settings. Settings is an array and current user need to be an admin
	 *
	 * @param string $circleUniqueId
	 * @param array $settings
	 *
	 * @return Circle
	 */
	public static function settingsCircle($circleUniqueId, array $settings) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->settingsCircle($circleUniqueId, $settings);
	}


	/**
	 * Circles::destroyCircle();
	 *
	 * This function will destroy the circle if the current user is the Owner.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return mixed
	 */
	public static function destroyCircle($circleUniqueId) {
		$c = self::getContainer();

		return $c->query('CirclesService')
				 ->removeCircle($circleUniqueId);
	}


	/**
	 * Circles::addMember();
	 *
	 * This function will add a user as member of the circle. Current user need at least to be
	 * Moderator.
	 *
	 * @param string $circleUniqueId
	 * @param string $userId
	 *
	 * @return Member[]
	 */
	public static function addMember($circleUniqueId, $userId) {
		$c = self::getContainer();

		return $c->query('MembersService')
				 ->addMember($circleUniqueId, $userId);
	}


	/**
	 * Circles::getMember();
	 *
	 * This function will return information on a member of the circle. Current user need at least
	 * to be Member.
	 *
	 * @param string $circleUniqueId
	 * @param string $userId
	 *
	 * @return Member
	 */
	public static function getMember($circleUniqueId, $userId) {
		$c = self::getContainer();

		return $c->query('MembersService')
				 ->getMember($circleUniqueId, $userId);
	}


	/**
	 * Circles::removeMember();
	 *
	 * This function will remove a member from the circle. Current user needs to be at least
	 * Moderator and have a higher level that the targeted member.
	 *
	 * @param string $circleUniqueId
	 * @param string $userId
	 *
	 * @return Member[]
	 */
	public static function removeMember($circleUniqueId, $userId) {
		$c = self::getContainer();

		return $c->query('MembersService')
				 ->removeMember($circleUniqueId, $userId);
	}


	/**
	 * Circles::levelMember();
	 *
	 * Edit the level of a member of the circle. The current level of the target needs to be lower
	 * than the user that initiate the process (ie. the current user). The new level of the target
	 * cannot be the same than the current level of the user that initiate the process (ie. the
	 * current user).
	 *
	 * @param string $circleUniqueId
	 * @param string $userId
	 * @param int $level
	 *
	 * @return Member[]
	 */
	public static function levelMember($circleUniqueId, $userId, $level) {
		$c = self::getContainer();

		return $c->query('MembersService')
				 ->levelMember($circleUniqueId, $userId, $level);
	}


	/**
	 * Circles::shareToCircle();
	 *
	 * This function will share an item (array) to the circle identified by its Id.
	 * Source is the app that is sharing the item and type can be used by the app to identified the
	 * payload.
	 *
	 * @param string $circleUniqueId
	 * @param string $source
	 * @param string $type
	 * @param array $payload
	 * @param string $broadcaster
	 *
	 * @return mixed
	 */
	public static function shareToCircle(
		$circleUniqueId, $source, $type, array $payload, $broadcaster
	) {
		$c = self::getContainer();

		$frame = new SharingFrame((string)$source, (string)$type);
		$frame->setCircleId($circleUniqueId);
		$frame->setPayload($payload);

		return $c->query('SharesService')
				 ->createFrame($frame, (string)$broadcaster);
	}


	/**
	 * Circles::linkCircle();
	 *
	 * Initiate a link procedure. Current user must be at least Admin of the circle.
	 * circleId is the local circle and remote is the target for the link.
	 * Remote format is: <circle_name>@<remote_host> when remote_host must be a valid HTTPS address.
	 *
	 * @param string $circleUniqueId
	 * @param string $remote
	 *
	 * @return FederatedLink
	 */
	public static function linkCircle($circleUniqueId, $remote) {
		$c = self::getContainer();

		return $c->query('FederatedService')
				 ->linkCircle($circleUniqueId, $remote);
	}


	/**
	 * Circles::generateLink();
	 *
	 * Returns the link to get access to a local circle.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return string
	 */
	public static function generateLink($circleUniqueId) {
		return \OC::$server->getURLGenerator()
						   ->linkToRoute('circles.Navigation.navigate') . '#' . $circleUniqueId;
	}


	/**
	 * Circles::generateLink();
	 *
	 * Returns the link to get access to a remote circle.
	 *
	 * @param FederatedLink $link
	 *
	 * @return string
	 */
	public static function generateRemoteLink(FederatedLink $link) {
		return \OC::$server->getURLGenerator()
						   ->linkToRoute('circles.Navigation.navigate') . '#' . $link->getUniqueId()
			   . '-' . $link->getToken();
	}

}