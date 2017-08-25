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

(function () {


	/**
	 * @constructs Circles
	 */
	var Circles = function () {

		$.extend(Circles.prototype, circles);
		$.extend(Circles.prototype, members);

		this.initialize();
	};

	Circles.prototype = {


		initialize: function () {

			var self = this;


			this.linkGroup = function (circleId, groupId, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'PUT',
					url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/groups'),
					data: {
						name: groupId
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.unlinkGroup = function (circleId, groupId, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'DELETE',
					url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/groups'),
					data: {
						group: groupId
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.levelGroup = function (circleId, group, level, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'POST',
					url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/group/level'),
					data: {
						group: group,
						level: level
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.shareToCircle = function (circleId, source, type, item, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'PUT',
					url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/share'),
					data: {
						source: source,
						type: type,
						item: item
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.linkCircle = function (circleId, remote, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'POST',
					url: OC.generateUrl('/apps/circles/v1/circles/' + circleId + '/link'),
					data: {
						remote: remote
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.linkStatus = function (linkId, status, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'POST',
					url: OC.generateUrl('/apps/circles/v1/link/' + linkId + '/status'),
					data: {
						status: status
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};

			this.onCallback = function (callback, result) {
				if (callback && (typeof callback === 'function')) {
					if (typeof result === 'object') {
						callback(result);
					} else {
						callback({status: -1});
					}
				}
			};

		}

	};

	OCA.Circles = Circles;
	OCA.Circles.api = new Circles();

})();


