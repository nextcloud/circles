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

var links = {

	linkCircle: function (circleId, remote, callback) {
		OCA.Circles.api.request({
			method: 'POST',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/link'),
			data: {
				remote: remote
			}
		}, callback);
	},


	linkStatus: function (linkId, status, callback) {
		OCA.Circles.api.request({
			method: 'POST',
			url: OC.generateUrl('/apps/circles/v1/link/' + linkId + '/status'),
			data: {
				status: status
			}
		}, callback);
	},


	linkGroup: function (circleId, groupId, callback) {
		OCA.Circles.api.request({
			method: 'PUT',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/groups'),
			data: {
				name: groupId
			}
		}, callback);
	},


	unlinkGroup: function (circleId, groupId, callback) {
		OCA.Circles.api.request({
			method: 'DELETE',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/groups'),
			data: {
				group: groupId
			}
		}, callback);
	},


	levelGroup: function (circleId, group, level, callback) {
		OCA.Circles.api.request({
			method: 'POST',
			url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/group/level'),
			data: {
				group: group,
				level: level
			}
		}, callback);
	}

};
