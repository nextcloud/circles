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
	test_timer: 4000,
	test_async_start: null,
	test_async_reset: null,
	test_async_wait: null,
	allow_linked_groups: null,
	allow_federated_circles: null
};


$(document).ready(function () {

	elements.test_async_start = $('#test_async_start');
	elements.test_async_reset = $('#test_async_reset');
	elements.test_async_wait = $('#test_async_wait');
	elements.test_async_result = $('#test_async_result');
	elements.allow_linked_groups = $('#allow_linked_groups');
	elements.allow_federated_circles = $('#allow_federated_circles');
	elements.enable_audit = $('#enable_audit');	

	elements.test_async_wait.hide().on('click', function () {
		self.refreshResult();
	});

	elements.test_async_reset.hide().on('click', function () {
		$.ajax({
			method: 'DELETE',
			url: OC.generateUrl('/apps/circles/admin/testAsync')
		}).done(function (res) {
			self.displayTestAsync(res);
		});
	});

	elements.test_async_start.hide().on('click', function () {
		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/circles/admin/testAsync')
		}).done(function (res) {
			self.displayTestAsync(res);
		});
	});

	elements.allow_linked_groups.on('change', function () {
		saveChange();
	});

	elements.allow_federated_circles.on('change', function () {
		saveChange();
	});
	
	elements.enable_audit.on('change', function () {
	    saveChange();
	});

	saveChange = function () {
		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/circles/admin/settings'),
			data: {
				allow_linked_groups: (elements.allow_linked_groups.is(
					':checked')) ? '1' : '0',
				allow_federated_circles: (elements.allow_federated_circles.is(
					':checked')) ? '1' : '0',
			    enable_audit: (elements.enable_audit.is(
		            ':checked')) ? '1' : '0'
			}
		}).done(function (res) {
			elements.allow_linked_groups.prop('checked', (res.allowLinkedGroups === '1'));
			elements.allow_federated_circles.prop('checked', (res.allowFederatedCircles === '1'));
			elements.enable_audit.prop('checked', (res.enableAudit === '1'));
		});
	};

	updateTestAsync = function () {
		self.refreshResult();
	};


	refreshResult = function () {
		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/circles/admin/testAsync')
		}).done(function (res) {
			self.displayTestAsync(res);
		});
	};

	displayTestAsync = function (res) {
		displayTestAsyncResult(res);
		displayTestAsyncNewTest(res);
		displayTestAsyncReset(res);
		displayTestAsyncWait(res);
	};


	displayTestAsyncResult = function (res) {
		if (res.init !== '0') {
			if (res.test.running === 0) {
				elements.test_async_result.text(
					'Test is now over; final score: ' + res.test.note);
				return;
			}


			elements.test_async_result.text(
				'Test is running. current tick: ' + res.count + '/121');

			return;
		}

		elements.test_async_result.text(
			t('circles', 'Circles is using its own way to async heavy process.'));
	};


	displayTestAsyncNewTest = function (res) {
		if (res.init !== '' && res.init !== '0') {
			elements.test_async_start.hide();
			return;
		}

		elements.test_async_start.show();
	};

	displayTestAsyncReset = function (res) {
		if (res.init !== '' && res.init !== '0') {
			elements.test_async_reset.show();
			return;
		}

		elements.test_async_reset.hide();
	};

	displayTestAsyncWait = function (res) {
		if (Number(res.test.running) === 1) {
			elements.test_async_reset.hide();
			elements.test_async_start.hide();
			elements.test_async_wait.show();
			return;
		}

		elements.test_async_wait.hide();
	};


	$.ajax({
		method: 'GET',
		url: OC.generateUrl('/apps/circles/admin/settings'),
		data: {}
	}).done(function (res) {
		elements.allow_linked_groups.prop('checked', (res.allowLinkedGroups === '1'));
		elements.allow_federated_circles.prop('checked', (res.allowFederatedCircles === '1'));
		elements.enable_audit.prop('checked', (res.enableAudit === '1'));		
	});

	var timerTestAsync = setInterval(function () {
		self.updateTestAsync();
	}, elements.test_timer);


})
;