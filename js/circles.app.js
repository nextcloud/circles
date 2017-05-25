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
/** global: Notyf */


/** global: nav */
/** global: actions */
/** global: elements */

var api = OCA.Circles.api;
var curr = {
	circlesType: '',
	circle: 0,
	circleLevel: 0,
	circleStatus: '',
	searchCircle: '',
	searchFilter: 0,
	searchUser: '',
	allowed_federated: 0,
	allowed_circles: 0
};


$(document).ready(function () {

	/**
	 * @constructs Navigation
	 */
	var Navigation = function () {

		$.extend(Navigation.prototype, curr);
		$.extend(Navigation.prototype, nav);
		$.extend(Navigation.prototype, elements);
		$.extend(Navigation.prototype, actions);

		this.init();
		this.initTransifex();
		this.retrieveSettings();
	};


	Navigation.prototype = {

		init: function () {
			elements.initElements();
			elements.initUI();
			elements.initTweaks();
			elements.initAnimationNewCircle();
			elements.initExperienceCirclesList();
			elements.initExperienceCircleButtons();
			//elements.initExperienceMemberDetails();
			nav.initNavigation();
		},

		initTransifex: function () {
			t('circles', 'Personal Circle');
			t('circles', 'Hidden Circle');
			t('circles', 'Private Circle');
			t('circles', 'Public Circle');

			t('circles', 'Personal');
			t('circles', 'Hidden');
			t('circles', 'Private');
			t('circles', 'Public');

			t('circles', 'Not a member');
			t('circles', 'Member');
			t('circles', 'Moderator');
			t('circles', 'Admin');
			t('circles', 'Owner');


			t('circles', 'Unknown');
			t('circles', 'Invited');
			t('circles', 'Requesting');
			t('circles', 'Blocked');
			t('circles', 'Kicked');
		},

		retrieveSettings: function () {
			var self = this;

			$.ajax({
				method: 'GET',
				url: OC.generateUrl('/apps/circles/settings'),
			}).done(function (result) {
				self.retrieveSettingsResult(result)
			}).fail(function () {
				self.retrieveSettingsResult({status: -1});
			});
		},

		retrieveSettingsResult: function (result) {
			if (result.status !== 1) {
				return;
			}

			curr.allowed_federated = result.allowed_federated;
			curr.allowed_circles = result.allowed_circles;
		}

	};


	/**
	 * @constructs Notification
	 */
	var Notification = function () {
		this.initialize();
	};

	Notification.prototype = {

		initialize: function () {

			//noinspection SpellCheckingInspection
			var notyf = new Notyf({
				delay: 5000
			});

			this.onSuccess = function (text) {
				notyf.confirm(text);
			};

			this.onFail = function (text) {
				notyf.alert(text);
			};

		}

	};

	OCA.Circles.Navigation = Navigation;
	OCA.Circles.navigation = new Navigation();

	OCA.Notification = Notification;
	OCA.notification = new Notification();

});

