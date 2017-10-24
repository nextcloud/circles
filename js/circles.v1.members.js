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

var members = {


	searchUsers: function (search, callback) {

		var result = {status: -1};
		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/circles/v1/globalsearch'),
			data: {
				search: search
			}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	},


	addMember: function (circleId, ident, type, callback) {
		var result = {status: -1};
		$.ajax({
			method: 'PUT',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/member'),
			data: {
				ident: ident,
				type: type
			}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	},


	removeMember: function (circleId, userId, userType, callback) {
		var result = {status: -1};
		$.ajax({
			method: 'DELETE',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/member'),
			data: {
				member: userId,
				type: Number(userType)
			}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	},


	levelMember: function (circleId, userId, userType, level, callback) {
		var result = {status: -1};
		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/level'),
			data: {
				member: userId,
				type: userType,
				level: level
			}
		}).done(function (res) {
			api.onCallback(callback, res);
		}).fail(function () {
			api.onCallback(callback, result);
		});
	}


};

