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

		if (response === null ||
			(response.ocs.data.users.length === 0 && response.ocs.data.exact.users.length === 0)) {
			elements.membersSearchResult.fadeOut(0);
			return;
		}

		elements.fillMembersSearch(response.ocs.data.exact.users, response.ocs.data.users);

		$('.members_search').on('click', function () {
			api.addMember(curr.circle, $(this).attr('searchresult'),
				resultMembers.addMemberResult);
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


	removeMemberResult: function (result) {
		if (result.status === 1) {

			elements.rightPanel.fadeOut(300);
			elements.mainUIMembersTable.children("[member-id='" + result.name + "']").each(
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
			t('circles', "Member '{name}' could not be updated", {name: result.name}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	}

};
