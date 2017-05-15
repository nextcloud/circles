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


var actions = {


	joinCircleResult: function (result) {
		if (result.status === 0) {
			OCA.notification.onFail(
				t('circles', "Cannot join this circle") + ': ' +
				((result.error) ? result.error : t('circles', 'no error message')));
			return;
		}

		elements.removeMemberslistEntry(result.member.user_id);
		if (result.member.level === 1) {
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

	linkCircleResult: function (result) {

		console.log("!!!! " + JSON.stringify(result));

	},


	selectCircle: function (circle_id) {
		curr.searchUser = '';
		elements.addMember.val('');
		elements.linkCircle.val('');

		api.detailsCircle(circle_id, actions.selectCircleResult);
	},


	unselectCircle: function (circle_id) {
		elements.mainUIMembers.emptyTable();
		elements.navigation.children(".circle[circle-id='" + circle_id + "']").remove();
		elements.emptyContent.show(800);
		elements.mainUI.fadeOut(800);

		curr.circle = 0;
		curr.circleLevel = 0;
	},


	/**
	 *
	 * @param search
	 */
	searchMembersRequest: function (search) {

		if (curr.searchUser === search) {
			return;
		}

		curr.searchUser = search;

		$.get(OC.linkToOCS('apps/files_sharing/api/v1', 1) + 'sharees',
			{
				format: 'json',
				search: search,
				perPage: 200,
				itemType: 'principals'
			}, actions.searchMembersResult);
	},


	searchMembersResult: function (response) {

		elements.membersSearchResult.children().remove();

		if (response === null ||
			(response.ocs.data.users.length === 0 && response.ocs.data.exact.users.length === 0)) {
			elements.membersSearchResult.fadeOut(0);
			return;
		}

		elements.fillMembersSearch(response.ocs.data.exact.users, response.ocs.data.users);

		$('.members_search').on('click', function () {
			api.addMember(curr.circle, $(this).attr('searchresult'),
				actions.addMemberResult);
		});
		elements.membersSearchResult.fadeIn(300);
	},


	addMemberResult: function (result) {

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "Member '{name}' successfully added to the circle",
					{name: result.name}));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "Member '{name}' could not be added to the circle", {name: result.name}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},


	getStringTypeFromType: function (type) {
		switch (type) {
			case '1':
				return t('circles', 'Personal circle');
			case '2':
				return t('circles', 'Hidden circle');
			case '4':
				return t('circles', 'Private circle');
			case '8':
				return t('circles', 'Public circle');
		}

		return t('circles', 'Circle');
	},


	removeMemberResult: function (result) {
		if (result.status === 1) {

			elements.rightPanel.fadeOut(300);
			elements.mainUIMembers.children("[member-id='" + result.name + "']").each(
				function () {
					$(this).hide(300);
				});
			OCA.notification.onSuccess(
				t('circles', "Member '{name}' successfully removed from the circle",
					{name: result.name}));
			return;
		}

		OCA.notification.onFail(
			t('circles', "Member '{name}' could not be removed from the circle",
				{name: result.name}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},


	/**
	 *
	 */
	onEventNewCircle: function () {
		curr.circle = 0;
		curr.circleLevel = 0;

		elements.circlesList.children('div').removeClass('selected');
		elements.emptyContent.show(800);
		elements.mainUI.fadeOut(800);
	},


	/**
	 *
	 */
	onEventNewCircleName: function () {
		this.onEventNewCircle();
		nav.displayOptionsNewCircle((elements.newName.val() !== ''));
	},


	/**
	 *
	 */
	onEventNewCircleType: function () {
		this.onEventNewCircle();
		elements.newTypeDefinition.children('div').fadeOut(300);
		var selectedType = elements.newType.children('option:selected').val();
		if (selectedType === '') {
			elements.newType.addClass('select_none');
		}
		else {
			elements.newType.removeClass('select_none');
			$('#circles_new_type_' + selectedType).fadeIn(
				300);
		}
	}


};
