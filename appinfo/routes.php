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

return [
	'routes' => [
		['name' => 'Navigation#navigate', 'url' => '/', 'verb' => 'GET'],
		['name' => 'Circles#create', 'url' => '/circles', 'verb' => 'PUT'],
		['name' => 'Circles#listCircles', 'url' => '/circles', 'verb' => 'GET'],
		[
			'name'         => 'Circles#detailsCircle', 'url' => '/circles/{id}', 'verb' => 'GET',
			'requirements' => ['id' => '\d+'],
		],

		['name' => 'Members#search', 'url' => '/circles/{id}/members', 'verb' => 'GET'],
		[
			'name'         => 'Members#add', 'url' => '/circles/{id}/members', 'verb' => 'PUT',
			'requirements' => ['circleid' => '\d+'],
		],

		//		['name' => 'Teams#create', 'url' => '/teams', 'verb' => 'PUT'],
		//		['name' => 'Teams#update', 'url' => '/teams/{id}', 'verb' => 'POST', 'requirements' => ['id' => '\d+'],],
		//		['name' => 'Teams#delete', 'url' => '/teams/{id}', 'verb' => 'DELETE', 'requirements' => ['id' => '\d+'],],
		//		['name' => 'Teams#listMembers', 'url' => '/teams/{id}/members', 'verb' => 'GET', 'requirements' => ['id' => '\d+'],],
		//		['name' => 'Teams#addMember', 'url' => '/teams/{id}/members', 'verb' => 'PUT', 'requirements' => ['id' => '\d+'],],
		//		['name' => 'Teams#removeMember', 'url' => '/teams/{id}/members', 'verb' => 'DELETE', 'requirements' => ['id' => '\d+'],],
	],
];