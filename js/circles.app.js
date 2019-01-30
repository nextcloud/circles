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
/** global: results */
/** global: resultCircles */
/** global: resultMembers */
/** global: resultGroups */
/** global: resultLinks */


var api = OCA.Circles.api;
var curr = {
	userId: '',
	circlesType: '',
	circle: 0,
	circleName: '',
	circleDesc: '',
	circleDetails: {},
	circleLevel: 0,
	circleStatus: '',
	circleMembers: {},
	circleGroups: {},
	circleLinks: {},
	searchCircle: '',
	searchFilter: 0,
	searchUser: '',
	exactMemberSearchType: false,
	searchGroup: '',
	searchUserSelected: '',
	allowed_linked_groups: 0,
	allowed_federated_circles: 0,
	allowed_circles: 0,
	disabled_notification_for_seen_users: 0,

	defineCircle: function (data) {
		curr.circle = data.circle_id;
		curr.circleDetails = data.details;
		curr.circleName = data.details.name;
		curr.circleDesc = data.details.description;
		curr.circleLimit = data.details.settings.members_limit;
		curr.circleSettings = data.details.settings;
		curr.circleLevel = data.details.viewer.level;
		curr.circleStatus = data.details.viewer.status;
	}
};

var define = {
	typePersonal: 1,
	typeSecret: 2,
	typeClosed: 4,
	typePublic: 8,
	typeUser: 1,
	typeGroup: 2,
	typeMail: 3,
	typeContact: 4,
	levelMember: 1,
	levelModerator: 4,
	levelAdmin: 8,
	levelOwner: 9,
	linkRemove: 0,
	linkDown: 1,
	linkSetup: 2,
	linkRefused: 4,
	linkRequestSent: 5,
	linkRequested: 6,
	linkUp: 9,
	animationSpeed: 100,
	animationMenuSpeed: 60,
	status: {
		0: t('circles', 'Link Removed'),
		1: t('circles', 'Link down'),
		2: t('circles', 'Setting link'),
		4: t('circles', 'Request dismissed'),
		5: t('circles', 'Request sent'),
		6: t('circles', 'Link requested'),
		9: t('circles', 'Link up')
	},

	linkStatus: function (status) {
		return define.status[parseInt(status)];
	}
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
		$.extend(Navigation.prototype, settings);
		$.extend(Navigation.prototype, resultCircles);
		$.extend(Navigation.prototype, resultMembers);
		$.extend(Navigation.prototype, resultGroups);
		$.extend(Navigation.prototype, resultLinks);

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
			t('circles', 'Secret Circle');
			t('circles', 'Closed Circle');
			t('circles', 'Public Circle');

			t('circles', 'Personal');
			t('circles', 'Secret');
			t('circles', 'Closed');
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
				url: OC.generateUrl('/apps/circles/settings')
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

			curr.userId = result.user_id;
			curr.allowed_circles = result.allowed_circles;
			curr.allowed_linked_groups = result.allowed_linked_groups;
			curr.allowed_federated_circles = result.allowed_federated_circles;

			var circleId = window.location.hash.substr(1);
			if (circleId) {
				actions.selectCircle(circleId);
			}
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

