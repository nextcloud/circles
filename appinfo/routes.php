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

return [
	'ocs'    => [

	],
	'routes' => [
		['name' => 'Navigation#navigate', 'url' => '/', 'verb' => 'GET'],
		['name' => 'Navigation#settings', 'url' => '/settings', 'verb' => 'GET'],
		['name' => 'Circles#create', 'url' => '/v1/circles', 'verb' => 'PUT'],
		['name' => 'Circles#listing', 'url' => '/v1/circles', 'verb' => 'GET'],
		[
			'name'         => 'Circles#details', 'url' => '/v1/circles/{id}', 'verb' => 'GET',
			'requirements' => ['id' => '\d+'],
		],
		[
			'name'         => 'Circles#settings', 'url' => '/v1/circles/{id}/settings',
			'verb'         => 'POST',
			'requirements' => ['id' => '\d+'],
		],
		[
			'name'         => 'Circles#destroy', 'url' => '/v1/circles/{id}', 'verb' => 'DELETE',
			'requirements' => ['id' => '\d+'],
		],
		[
			'name'         => 'Circles#join', 'url' => '/v1/circles/{id}/join', 'verb' => 'GET',
			'requirements' => ['id' => '\d+'],
		],
		[
			'name'         => 'Circles#leave', 'url' => '/v1/circles/{id}/leave', 'verb' => 'GET',
			'requirements' => ['id' => '\d+'],
		],
		['name' => 'Circles#link', 'url' => '/v1/circles/{circleId}/link', 'verb' => 'POST'],
		[
			'name'         => 'Circles#linkStatus',
			'url'          => '/v1/link/{linkId}/status', 'verb' => 'POST',
			'requirements' => ['linkId' => '\d+'],
		],
		['name' => 'Federated#requestedLink', 'url' => '/v1/circles/link/', 'verb' => 'POST'],
		[
			'name' => 'Federated#initFederatedDelivery', 'url' => '/v1/circles/payload/',
			'verb' => 'POST'
		],
		[
			'name' => 'Federated#receiveFederatedDelivery', 'url' => '/v1/circles/payload/',
			'verb' => 'PUT'
		],
		['name' => 'Members#search', 'url' => '/v1/circles/{id}/members', 'verb' => 'GET'],
		[
			'name'         => 'Members#add', 'url' => '/v1/circles/{id}/members', 'verb' => 'PUT',
			'requirements' => ['id' => '\d+'],
		],
		[
			'name'         => 'Members#remove', 'url' => '/v1/circles/{id}/members',
			'verb'         => 'DELETE',
			'requirements' => ['id' => '\d+'],
		],
		[
			'name'         => 'Members#level', 'url' => '/v1/circles/{id}/level', 'verb' => 'POST',
			'requirements' => ['id' => '\d+'],
		],
		[
			'name'         => 'Shares#create', 'url' => '/v1/circles/{id}/share', 'verb' => 'PUT',
			'requirements' => ['id' => '\d+'],
		],

	],
];