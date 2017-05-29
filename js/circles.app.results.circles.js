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

/** global: actions */
/** global: nav */
/** global: elements */
/** global: curr */
/** global: api */


var resultCircles = {


	joinCircleResult: function (result) {
		if (result.status === 0) {
			OCA.notification.onFail(
				t('circles', "Cannot join this circle") + ': ' +
				((result.error) ? result.error : t('circles', 'no error message')));
			return;
		}

		elements.removeMemberslistEntry(result.member.user_id);
		if (result.member.level === define.levelMember) {
			OCA.notification.onSuccess(
				t('circles', "You have successfully joined this circle"));
		} else {
			OCA.notification.onSuccess(
				t('circles', "You have requested to join this circle"));
		}
		actions.selectCircle(result.circle_id);
	},


	leaveCircleResult: function (result) {
		if (result.status === 1) {

			elements.mainUIMembers.children("[member-id='" + result.name + "']").each(
				function () {
					$(this).hide(300);
				});

			actions.selectCircle(result.circle_id);
			OCA.notification.onSuccess(
				t('circles', "You have successfully left this circle"));
			return;
		}

		OCA.notification.onFail(
			t('circles', "Cannot leave this circle") + ': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},


	destroyCircleResult: function (result) {
		if (result.status === 1) {

			actions.unselectCircle(result.circle_id);
			OCA.notification.onSuccess(
				t('circles', "You have successfully deleted this circle"));
			return;
		}

		OCA.notification.onFail(
			t('circles', "Cannot delete this circle") + ': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},


	createCircleResult: function (result) {
		var type = actions.getStringTypeFromType(result.type);

		if (result.status === 1) {
			OCA.notification.onSuccess(t('circles', " {type} '{name}' created", {
				type: type,
				name: result.name
			}));
			elements.emptyCircleCreation();
			nav.displayCirclesList(result.circle.type);
			actions.selectCircle(result.circle.id);
			return;
		}

		OCA.notification.onFail(
			t('circles', " {type} '{name}' could not be created", {
				type: type,
				name: result.name
			}) + ': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},



	selectCircleResult: function (result) {

		elements.mainUIMembers.emptyTable();
		if (result.status < 1) {
			OCA.notification.onFail(
				t('circles', 'Issue while retrieving the details of this circle') + '" ' +
				((result.error) ? result.error : t('circles', 'no error message')));
			return;
		}

		elements.navigation.children('.circle').removeClass('selected');
		elements.navigation.children(".circle[circle-id='" + result.circle_id + "']").each(
			function () {
				$(this).addClass('selected');
			});

		elements.emptyContent.hide(800);
		elements.mainUI.fadeIn(800);
		curr.circle = result.circle_id;
		curr.circleLevel = result.details.user.level;
		curr.circleStatus = result.details.user.status;

		nav.displayCircleDetails(result.details);
		nav.displayMembersInteraction(result.details);
		nav.displayMembers(result.details.members);
	},


	listCirclesResult: function (result) {

		if (result.status < 1) {
			OCA.notification.onFail(
				t('circles', 'Issue while retrieving the list of circles') + '; ' +
				((result.error) ? result.error : t('circles', 'no error message')));
			return;
		}

		elements.resetCirclesList();

		var data = result.data;
		for (var i = 0; i < data.length; i++) {
			var tmpl = elements.generateTmplCircle(data[i]);
			elements.navigation.append(
				'<div class="circle" circle-id="' + data[i].id + '">' + tmpl + '</div>');
		}

		elements.navigation.children('.circle').on('click', function () {
			actions.selectCircle($(this).attr('circle-id'));
		});
	},



};
