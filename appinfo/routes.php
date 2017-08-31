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
		[
			'name' => 'Settings#setSettings',
			'url'  => '/admin/settings',
			'verb' => 'POST'
		],
		[
			'name' => 'Settings#getSettings',
			'url'  => '/admin/settings',
			'verb' => 'GET'
		],
		['name' => 'Navigation#navigate', 'url' => '/', 'verb' => 'GET'],
		['name' => 'Navigation#settings', 'url' => '/settings', 'verb' => 'GET'],
		['name' => 'Circles#create', 'url' => '/v1/circles', 'verb' => 'PUT'],
		['name' => 'Circles#listing', 'url' => '/v1/circles', 'verb' => 'GET'],
		[
			'name'         => 'Circles#details', 'url' => '/v1/circles/{uniqueId}', 'verb' => 'GET',
		],
		[
			'name'         => 'Circles#settings', 'url' => '/v1/circles/{uniqueId}/settings',
			'verb'         => 'POST',
		],
		[
			'name'         => 'Circles#destroy', 'url' => '/v1/circles/{uniqueId}', 'verb' => 'DELETE',
		],
		[
			'name'         => 'Circles#join', 'url' => '/v1/circles/{uniqueId}/join', 'verb' => 'GET',
		],
		[
			'name'         => 'Circles#leave', 'url' => '/v1/circles/{uniqueId}/leave', 'verb' => 'GET',
		],
		['name' => 'Links#createLink', 'url' => '/v1/circles/{uniqueId}/link', 'verb' => 'POST'],
		[
			'name'         => 'Links#updateLinkStatus',
			'url'          => '/v1/link/{linkId}/status', 'verb' => 'POST'
		],
		['name' => 'Federated#requestedLink', 'url' => '/v1/link', 'verb' => 'PUT'],
		['name' => 'Federated#updateLink', 'url' => '/v1/link', 'verb' => 'POST'],
		[
			'name' => 'Shares#initShareDelivery', 'url' => '/v1/payload',
			'verb' => 'POST'
		],
		[
			'name' => 'Federated#receiveFederatedDelivery', 'url' => '/v1/payload',
			'verb' => 'PUT'
		],
		['name' => 'Members#searchGlobal', 'url' => '/v1/globalsearch', 'verb' => 'GET'],
//		[
//			'name'         => 'Members#importFromGroup', 'url' => '/v1/circles/{uniqueId}/groupmembers',
//			'verb'         => 'PUT'
//		],
		[
			'name'         => 'Members#addMember', 'url' => '/v1/circles/{uniqueId}/member', 'verb' => 'PUT'
		],
		[
			'name'         => 'Members#removeMember', 'url' => '/v1/circles/{uniqueId}/member',
			'verb'         => 'DELETE',
		],
		[
			'name'         => 'Members#levelMember', 'url' => '/v1/circles/{uniqueId}/level', 'verb' => 'POST'
		],
		[
			'name'         => 'Groups#add', 'url' => '/v1/circles/{uniqueId}/groups', 'verb' => 'PUT'
		],
		[
			'name'         => 'Groups#level', 'url' => '/v1/circles/{uniqueId}/group/level', 'verb' => 'POST'
		],
		[
			'name'         => 'Groups#remove', 'url' => '/v1/circles/{uniqueId}/groups',
			'verb'         => 'DELETE'
		],
		[
			'name'         => 'Shares#create', 'url' => '/v1/circles/{circleUniqueId}/share', 'verb' => 'PUT'
		]
	]
];