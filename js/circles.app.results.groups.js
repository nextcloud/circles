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


var resultGroups = {


	searchGroupsResult: function (response) {

		elements.groupsSearchResult.children().remove();

		if (response === null) {
			elements.groupsSearchResult.fadeOut(0);
			return;
		}

		elements.fillGroupsSearch(response.ocs.data.exact.groups, response.ocs.data.groups);
		if (elements.groupsSearchResult.children().length === 0) {
			elements.groupsSearchResult.fadeOut(0);
			return;
		}

		$('.groups_search').on('click', function () {
			curr.searchGroupSelected = $(this).attr('searchresult');
			api.linkGroup(curr.circle, curr.searchGroupSelected,
				resultGroups.linkGroupResult);
		});
		elements.groupsSearchResult.fadeIn(300);
	},


	linkGroupResult: function (result) {

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "Group '{name}' successfully added to the circle",
					{name: result.name}));

			nav.displayGroups(result.groups);
			return;
		}
		OCA.notification.onFail(
			t('circles', "Group '{name}' could not be added to the circle", {name: result.name}) +
			': ' + ((result.error) ? result.error : t('circles', 'no error message')));
	},


	levelGroupResult: function (result) {
		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "Group '{name}' updated",
					{name: result.name}));

			nav.displayGroups(result.groups);
			return;
		}

		nav.displayGroups('');
		OCA.notification.onFail(
			t('circles', "Group '{name}' could not be updated", {name: result.name}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},


	unlinkGroupResult: function (result) {
		if (result.status === 1) {
			elements.mainUIGroupsTable.children("[group-id='" + result.name + "']").each(
				function () {
					$(this).hide(300);
				});

			OCA.notification.onSuccess(
				t('circles', "Group '{name}' successfully removed from the circle",
					{name: result.name}));

			nav.displayGroups(result.groups);
			return;
		}

		nav.displayGroups('');
		OCA.notification.onFail(
			t('circles', "Group '{name}' could not be removed from the circle",
				{name: result.name}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	}

};
