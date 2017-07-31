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


var resultMembers = {


	searchMembersResult: function (response) {

		elements.membersSearchResult.children().remove();

		if (response === null) {
			elements.membersSearchResult.fadeOut(0);
			return;
		}

		elements.fillMembersSearch('users', response.ocs.data.exact.users, response.ocs.data.users);
		elements.fillMembersSearch('groups', response.ocs.data.exact.groups,
			response.ocs.data.groups);

		if (elements.membersSearchResult.children().length === 0) {
			elements.membersSearchResult.fadeOut(0);
			return;
		}

		$('.members_search').on('click', function () {
			curr.searchUserSelected = $(this).attr('searchresult');
			if ($(this).attr('source') === 'groups') {

				OC.dialogs.confirm(
					t('circles',
						'This operation will add/invite all members of the group to the circle'),
					t('circles', 'Please confirm'),
					function (e) {
						if (e === true) {
							api.addGroupMembers(curr.circle, curr.searchUserSelected,
								resultMembers.addGroupMembersResult);
						}
					});
			} else {
				api.addMember(curr.circle, curr.searchUserSelected,
					resultMembers.addMemberResult);
			}
		});
		elements.membersSearchResult.fadeIn(300);
	},


	addMemberResult: function (result) {

		if (curr.circleDetails.type === define.typePrivate) {
			resultMembers.inviteMemberResult(result);
			return;
		}

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "The member '{name}' was added to the circle",
					{name: result.name}));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "The member '{name}' could not be added to the circle",
				{name: result.name}) +
			': ' + ((result.error) ? result.error : t('circles', 'no error message')));
	},


	inviteMemberResult: function (result) {

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "The member '{name}' was invited to the circle",
					{name: result.name}));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "The member '{name}' could not be invited to the circle",
				{name: result.name}) +
			': ' + ((result.error) ? result.error : t('circles', 'no error message')));
	},


	addGroupMembersResult: function (result) {

		if (curr.circleDetails.type === define.typePrivate) {
			resultMembers.inviteGroupMembersResult(result);
			return;
		}

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "Members of the group '{name}' were added to the circle",
					{name: result.name}));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "Members of the group '{name}' could not be added to the circle",
				{name: result.name}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},


	inviteGroupMembersResult: function (result) {

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "Members of the group '{name}' were invited to the circle",
					{name: result.name}));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "Members of the group '{name}' could not be invited to the circle",
				{name: result.name}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},


	removeMemberResult: function (result) {
		if (result.status === 1) {

			elements.mainUIMembersTable.children("[member-id='" + result.user_id + "']").each(
				function () {
					$(this).hide(300);
				});
			OCA.notification.onSuccess(
				t('circles', "The member '{name}' was removed from the circle",
					{name: result.name}));
			return;
		}

		OCA.notification.onFail(
			t('circles', "The member '{name}' could not be removed from the circle",
				{name: result.name}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},

	levelMemberResult: function (result) {
		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "Member '{name}' updated",
					{name: result.name}));

			nav.displayMembers(result.members);
			return;
		}

		nav.displayMembers('');
		OCA.notification.onFail(
			t('circles', "The member '{name}' could not be updated", {name: result.name}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	}

};
