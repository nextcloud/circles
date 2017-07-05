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
/** global: resultMembers */
/** global: resultGroups */
/** global: resultCircles */
/** global: curr */
/** global: api */
/** global: define */
/** global: resultLinks */
/** global: settings */


var actions = {


	changeMemberLevel: function (member, level) {
		if (parseInt(level) === define.levelOwner) {
			actions.changeMemberOwner(member);
			return;
		}
		api.levelMember(curr.circle, member, level, resultMembers.levelMemberResult);
		nav.circlesActionReturn();
	},


	changeGroupLevel: function (group, level) {

		if (level === 'remove_group') {
			api.removeGroup(curr.circle, group, resultGroups.removeGroupResult);
		} else {
			api.levelGroup(curr.circle, group, level, resultGroups.levelGroupResult);
		}
		nav.circlesActionReturn();
	},


	changeMemberOwner: function (member) {
		OC.dialogs.confirm(
			t('circles', 'Are you sure you want to transfer your Owner rights ?', [member]),
			t('circles', 'This action is irreversible'),
			function (e) {
				if (e === true) {
					api.levelMember(curr.circle, member, define.levelOwner,
						resultMembers.levelMemberResult);
					nav.circlesActionReturn();
					return;
				}

				nav.displayMembers('');
			});
	},


	changeMemberStatus: function (member, value) {
		if (value === 'remove_member' || value === 'dismiss_request') {
			api.removeMember(curr.circle, member, resultMembers.removeMemberResult);
		}
		if (value === 'accept_request') {
			api.addMember(curr.circle, member, resultMembers.addMemberResult);
		}
	},


	changeLinkStatus: function (link, value) {
		api.linkStatus(link, value, resultLinks.linkStatusResult);
	},


	selectCircle: function (circle_id) {
		curr.searchUser = '';
		elements.addMember.val('');
		elements.linkGroup.val('');
		elements.linkCircle.val('');

		nav.circlesActionReturn();
		api.detailsCircle(circle_id, resultCircles.selectCircleResult);
	},


	unselectCircle: function (circle_id) {
		elements.mainUIMembersTable.emptyTable();
		elements.navigation.children(".circle[circle-id='" + circle_id + "']").remove();
		elements.emptyContent.show(800);
		elements.mainUI.fadeOut(800);

		curr.circle = 0;
		curr.circleLevel = 0;
	},


	saveSettings: function () {
		data = {
			circle_name: elements.settingsName.val(),
			circle_desc: elements.settingsDesc.val(),
			allow_links: (elements.settingsLink.is(":checked")),
			allow_links_auto: (elements.settingsLinkAuto.is(":checked")),
			allow_links_files: (elements.settingsLinkFiles.is(":checked"))
		};

		api.settingsCircle(curr.circle, data, settings.saveSettingsResult);
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
			}, resultMembers.searchMembersResult);
	},


	/**
	 *
	 * @param search
	 */
	searchGroupsRequest: function (search) {

		if (curr.searchGroup === search) {
			return;
		}

		curr.searchGroup = search;

		$.get(OC.linkToOCS('apps/files_sharing/api/v1', 1) + 'sharees',
			{
				format: 'json',
				search: search,
				perPage: 200,
				itemType: 'principals'
			}, resultGroups.searchGroupsResult);
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
