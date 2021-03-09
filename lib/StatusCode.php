<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCA\Circles;


/**
 * Class StatusCode
 *
 * @package OCA\Circles
 */
class StatusCode {


	static $CIRCLE_CREATE = [
		120 => 'Duplicate name'
	];

	static $CIRCLE_CONFIG = [
		120 => 'Invalid configuration'
	];

	static $CIRCLE_JOIN = [
		120 => 'You are already a member',
		121 => 'Circle is full',
	];

	static $CIRCLE_LEAVE = [
	];

	static $MEMBER_ADD = [
		120 => 'Unknown entity',
		121 => 'Already member of the circle',
		122 => 'Circle is full',
		123 => 'The designed circle cannot be added'
	];

	static $MEMBER_LEVEL = [
		120 => 'The designed member\'s level is too high',
		121 => 'Incorrect Level'
	];

	static $MEMBER_REMOVE = [
		120 => 'The designed member\'s level is too high',
	];

}
