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
/** global: curr */

var members = {

	searchUsers: function(search, callback) {
		curr.searchOrder++;
		OCA.Circles.api.request({
			method: 'GET',
			url: OC.generateUrl('/apps/circles/v1/globalsearch'),
			data: {
				search: search,
				order: curr.searchOrder
			}
		}, callback);
	},


	addMember: function(circleId, ident, type, instance, callback) {
		OCA.Circles.api.request({
			method: 'PUT',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/member'),
			data: {
				ident: ident,
				type: type,
				instance: instance
			}
		}, callback);
	},


	removeMember: function(circleId, userId, userType, instance, callback) {
		OCA.Circles.api.request({
			method: 'DELETE',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/member'),
			data: {
				member: userId,
				type: Number(userType),
				instance: instance
			}
		}, callback);
	},


	levelMember: function(circleId, userId, userType, instance, level, callback) {
		OCA.Circles.api.request({
			method: 'POST',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/level'),
			data: {
				member: userId,
				type: userType,
				instance: instance,
				level: level
			}
		}, callback);
	}


};

