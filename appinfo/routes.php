<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


return [
	'ocs' => [

		// LocalController
		['name' => 'Local#circles', 'url' => '/circles', 'verb' => 'GET'],
		['name' => 'Local#probeCircles', 'url' => '/probecircles', 'verb' => 'GET'],
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

		// Teams Dashboard widget endpoint
		['name' => 'TeamsDashboard#getCompleteTeamsData', 'url' => '/teams/dashboard/widget', 'verb' => 'GET'],

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
