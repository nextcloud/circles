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

var elements = {
	allow_federated_circles: null
};


$(document).ready(function () {

	elements.allow_federated_circles = $('#allow_federated_circle');
	elements.allow_federated_circles.on('change', function () {
		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/circles/admin/settings'),
			data: {
				allow_federated_circles: (elements.allow_federated_circles.is(
					':checked')) ? '1' : '0'
			}
		}).done(function (res) {
			elements.allow_federated_circles.prop('checked', (res.allowFederatedCircles === '1'));
		});
	});

	$.ajax({
		method: 'GET',
		url: OC.generateUrl('/apps/circles/admin/settings'),
		data: {}
	}).done(function (res) {
		elements.allow_federated_circles.prop('checked', (res.allowFederatedCircles === '1'));
	});

});