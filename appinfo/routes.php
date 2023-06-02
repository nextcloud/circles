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


return [
	'ocs' => [

		// LocalController
		['name' => 'Local#circles', 'url' => '/circles', 'verb' => 'GET'],
		['name' => 'Local#create', 'url' => '/circles', 'verb' => 'POST'],
		['name' => 'Local#destroy', 'url' => '/circles/{circleId}', 'verb' => 'DELETE'],
		['name' => 'Local#search', 'url' => '/search', 'verb' => 'GET'],
		['name' => 'Local#circleDetails', 'url' => '/circles/{circleId}', 'verb' => 'GET'],
		['name' => 'Local#members', 'url' => '/circles/{circleId}/members', 'verb' => 'GET'],
		['name' => 'Local#memberAdd', 'url' => '/circles/{circleId}/members', 'verb' => 'POST'],
		['name' => 'Local#membersAdd', 'url' => '/circles/{circleId}/members/multi', 'verb' => 'POST'],
		[
			'name' => 'Local#memberConfirm', 'url' => '/circles/{circleId}/members/{memberId}',
			'verb' => 'PUT'
		],
		[
			'name' => 'Local#memberRemove', 'url' => '/circles/{circleId}/members/{memberId}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'Local#memberLevel', 'url' => '/circles/{circleId}/members/{memberId}/level',
			'verb' => 'PUT'
		],
		['name' => 'Local#circleJoin', 'url' => '/circles/{circleId}/join', 'verb' => 'PUT'],
		['name' => 'Local#circleLeave', 'url' => '/circles/{circleId}/leave', 'verb' => 'PUT'],

		['name' => 'Local#editName', 'url' => '/circles/{circleId}/name', 'verb' => 'PUT'],
		['name' => 'Local#editDescription', 'url' => '/circles/{circleId}/description', 'verb' => 'PUT'],
		['name' => 'Local#editSetting', 'url' => '/circles/{circleId}/setting', 'verb' => 'PUT'],
		['name' => 'Local#editConfig', 'url' => '/circles/{circleId}/config', 'verb' => 'PUT'],
		['name' => 'Local#link', 'url' => '/link/{circleId}/{singleId}', 'verb' => 'GET'],

		// AdminController
		['name' => 'Admin#circles', 'url' => '/admin/{emulated}/circles', 'verb' => 'GET'],
		['name' => 'Admin#create', 'url' => '/admin/{emulated}/circles', 'verb' => 'POST'],
		['name' => 'Admin#destroy', 'url' => '/admin/{emulated}/circles/{circleId}', 'verb' => 'DELETE'],
		[
			'name' => 'Admin#memberAdd', 'url' => '/admin/{emulated}/circles/{circleId}/members',
			'verb' => 'POST'
		],
		[
			'name' => 'Admin#memberLevel',
			'url' => '/admin/{emulated}/circles/{circleId}/members/{memberId}/level',
			'verb' => 'PUT'
		],

		['name' => 'Admin#circleDetails', 'url' => '/admin/{emulated}/circles/{circleId}', 'verb' => 'GET'],
		['name' => 'Admin#members', 'url' => '/admin/{emulated}/circles/{circleId}/members', 'verb' => 'GET'],
		['name' => 'Admin#memberAdd', 'url' => '/admin/{emulated}/circles/{circleId}/members', 'verb' => 'POST'],
		[
			'name' => 'Admin#memberConfirm', 'url' => '/admin/{emulated}/circles/{circleId}/members/{memberId}',
			'verb' => 'PUT'
		],
		[
			'name' => 'Admin#memberRemove', 'url' => '/admin/{emulated}/circles/{circleId}/members/{memberId}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'Admin#memberLevel', 'url' => '/admin/{emulated}/circles/{circleId}/members/{memberId}/level',
			'verb' => 'PUT'
		],
		['name' => 'Admin#circleJoin', 'url' => '/admin/{emulated}/circles/{circleId}/join', 'verb' => 'PUT'],
		['name' => 'Admin#circleLeave', 'url' => '/admin/{emulated}/circles/{circleId}/leave', 'verb' => 'PUT'],
		['name' => 'Admin#editName', 'url' => '/admin/{emulated}/circles/{circleId}/name', 'verb' => 'PUT'],
		['name' => 'Admin#editDescription', 'url' => '/admin/{emulated}/circles/{circleId}/description', 'verb' => 'PUT'],
		['name' => 'Admin#editSetting', 'url' => '/admin/{emulated}/circles/{circleId}/setting', 'verb' => 'PUT'],
		['name' => 'Admin#editConfig', 'url' => '/admin/{emulated}/circles/{circleId}/config', 'verb' => 'PUT'],
		['name' => 'Admin#link', 'url' => '/admin/{emulated}/link/{circleId}/{singleId}', 'verb' => 'GET']
	],

	'routes' => [
		['name' => 'EventWrapper#asyncBroadcast', 'url' => '/async/{token}/', 'verb' => 'POST'],

		['name' => 'Remote#appService', 'url' => '/', 'verb' => 'GET'],
		['name' => 'Remote#test', 'url' => '/test', 'verb' => 'GET'],
		['name' => 'Remote#event', 'url' => '/event/', 'verb' => 'POST'],
		['name' => 'Remote#incoming', 'url' => '/incoming/', 'verb' => 'POST'],
		['name' => 'Remote#circles', 'url' => '/circles/', 'verb' => 'GET'],
		['name' => 'Remote#circle', 'url' => '/circle/{circleId}/', 'verb' => 'GET'],
		['name' => 'Remote#members', 'url' => '/members/{circleId}/', 'verb' => 'GET'],
		['name' => 'Remote#member', 'url' => '/member/{type}/{userId}/', 'verb' => 'GET'],
		['name' => 'Remote#inherited', 'url' => '/inherited/{circleId}/', 'verb' => 'GET'],
		['name' => 'Remote#memberships', 'url' => '/memberships/{circleId}/', 'verb' => 'GET'],

		['name' => 'Deprecated#listing', 'url' => '/listing', 'verb' => 'GET'],
	]
];
