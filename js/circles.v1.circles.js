/*
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

/** global: OC */
/** global: OCA */

var circles = {

	createCircle: function (type, name, callback) {

		var result = {status: -1};
		$.ajax({
			method: 'PUT',
			url: OC.generateUrl('/apps/circles/v1/circles'),
			data: {
				type: type,
				name: name
			}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	},


	listCircles: function (type, name, level, callback) {
		var result = {status: -1};
		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/circles/v1/circles'),
			data: {
				type: type,
				name: name,
				level: level
			}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	},


	detailsCircle: function (circleId, callback) {
		var result = {status: -1};
		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId)
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	},


	joinCircle: function (circleId, callback) {
		var result = {status: -1};
		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/join'),
			data: {}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	},


	settingsCircle: function (circleId, settings, callback) {
		var result = {status: -1};
		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/settings'),
			data: {settings: settings}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	},


	leaveCircle: function (circleId, callback) {
		var result = {status: -1};
		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/leave'),
			data: {}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	},


	destroyCircle: function (circleId, callback) {
		var result = {status: -1};
		$.ajax({
			method: 'DELETE',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId),
			data: {}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	}

};

