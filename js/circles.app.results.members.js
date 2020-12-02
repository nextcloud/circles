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


	generateItemResult: function(search, value) {

		switch (value.type) {
			case define.typeUser:
				return resultMembers.generateItemUser(search, value);
			case define.typeGroup:
				return resultMembers.generateItemGroup(search, value);
			case define.typeContact:
				return resultMembers.generateItemContact(search, value);
		}
	},


	enhanceSearchResult: function(search, display) {
		display = nav.escapeHtml(display);
		if (search.length > 0) {
			display = display.replace(new RegExp('(' + search + ')', 'gi'), '<b>$1</b>');
		}

		return display;
	},


	generateItemUser: function(search, value) {
		var instance = value.instance;
		if (instance !== '') {
			return '<div class="result_top">' +
				resultMembers.enhanceSearchResult(search, value.data.display) + '</div>' +
				'<div class="result_bot">' + t('circles', 'Global Scale User') + ' (' + instance + ')</div>';
		}

		return '<div class="result_top">' +
			resultMembers.enhanceSearchResult(search, value.data.display) + '</div>' +
			'<div class="result_bot">' + t('circles', 'Local User') + '</div>';
	},

	generateItemGroup: function(search, value) {
		return '<div class="result_top">' +
			resultMembers.enhanceSearchResult(search, value.data.display) + '</div>' +
			'<div class="result_bot">' + t('circles', 'Local Group') + '</div>';
	},

	generateItemContact: function(search, value) {
		var display = resultMembers.enhanceSearchResult(search, value.data.display);
		var email = resultMembers.enhanceSearchResult(search, value.data.email);
		var org = resultMembers.enhanceSearchResult(search, value.data.organization);
		if (email !== '') {
			email = ' - ' + email;
		}

		if (org !== '') {
			display += '   (' + org + ')';
		}

		return '<div class="result_top">' + display + '</div>' +
			'<div class="result_bot">' + t('circles', 'Contact') + email + '</div>';
	},


	searchMembersResult: function(response) {
		if (response === null) {
			elements.membersSearchResult.children().remove();
			elements.membersSearchResult.fadeOut(0);
			return;
		}

		if (response.order < curr.searchOrderDisplayed) {
			return;
		}

		elements.membersSearchResult.children().remove();
		curr.searchOrderDisplayed = response.order;

		var currSearch = response.search;
		$.each(response.result, function(index, value) {
			elements.membersSearchResult.append('<div class="members_search" data-type="' +
				value.type + '" data-ident="' + nav.escapeHtml(value.ident) + '" data-instance="' + nav.escapeHtml(value.instance) + '">' +
				resultMembers.generateItemResult(currSearch, value) + '</div>'
			)
			;
		});

		$('.members_search').on('click', function() {
			var ident = $(this).attr('data-ident');
			var type = $(this).attr('data-type');
			var instance = $(this).attr('data-instance');
			if (instance === undefined) {
				instance = '';
			}
			if (Number(type) === define.typeGroup) {

				OC.dialogs.confirm(
					t('circles',
						'This operation will add/invite all members of the group to the circle'),
					t('circles', 'Please confirm'),
					function(e) {
						if (e === true) {
							api.addMember(curr.circle, ident, type, instance,
								resultMembers.addMemberResult);
						}
					});

			} else {
				api.addMember(curr.circle, ident, type, instance, resultMembers.addMemberResult);
			}

			elements.membersSearchResult.hide(100);
		});

		// elements.fillMembersSearch('users', response.ocs.data.exact.users,
		// response.ocs.data.users); elements.fillMembersSearch('groups',
		// response.ocs.data.exact.groups, response.ocs.data.groups);  if
		// (elements.membersSearchResult.children().length === 0) {
		// elements.membersSearchResult.fadeOut(0); return; }  $('.members_search').on('click',
		// function () { curr.searchUserSelected = $(this).attr('searchresult'); if
		// ($(this).attr('source') === 'groups') {  OC.dialogs.confirm( t('circles', 'This
		// operation will add/invite all members of the group to the circle'), t('circles', 'Please
		// confirm'), function (e) { if (e === true) { api.addGroupMembers(curr.circle,
		// curr.searchUserSelected, resultMembers.addGroupMembersResult); } }); } else {
		// api.addMember(curr.circle, curr.searchUserSelected, resultMembers.addMemberResult); } });
		elements.membersSearchResult.fadeIn(300);
	},


	addMemberResult: function(result) {
		resultMembers.addMemberUserResult(result);
		resultMembers.addMemberGroupResult(result);
		resultMembers.addMemberMailResult(result);
		resultMembers.addMemberContactResult(result);
	},


	addMemberUserResult: function(result) {
		if (result.user_type !== define.typeUser) {
			return;
		}

		if (curr.circleDetails.type === define.typeClosed) {
			resultMembers.inviteMemberResult(result);
			return;
		}

		if (result.status === 1) {
			OCA.notification.onSuccess(t('circles', "A new member was added to the circle"));

			nav.displayMembers(result.members);
			return;
		}

		OCA.notification.onFail(
			t('circles', "Member could not be added to the circle") +
			': ' + ((result.error) ? result.error : t('circles', 'no error message')));
	},


	addMemberMailResult: function(result) {
		if (result.user_type !== define.typeMail) {
			return;
		}

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "The email address '{email}' was added to the circle",
					{email: result.display}));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "The email address '{email}' could not be added to the circle",
				{email: result.display}) +
			': ' + ((result.error) ? result.error : t('circles', 'no error message')));
	},

	addMemberContactResult: function(result) {
		if (result.user_type !== define.typeContact) {
			return;
		}

		if (result.status === 1) {
			OCA.notification.onSuccess(t('circles', "A new contact was added to the circle"));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "Contact could not be added to the circle") +
			': ' + ((result.error) ? result.error : t('circles', 'no error message')));
	},


	inviteMemberResult: function(result) {

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "A new member was invited to the circle"));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "Member could not be invited to the circle") +
			': ' + ((result.error) ? result.error : t('circles', 'no error message')));
	},


	addMemberGroupResult: function(result) {

		if (result.user_type !== define.typeGroup) {
			return;
		}

		if (curr.circleDetails.type === define.typeClosed) {
			resultMembers.inviteMemberGroupResult(result);
			return;
		}

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "Members of the group '{name}' were added to the circle",
					{name: result.display}));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "Members of the group '{name}' could not be added to the circle",
				{name: result.display}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},


	inviteMemberGroupResult: function(result) {

		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "Members of the group '{name}' were invited to the circle",
					{name: result.display}));

			nav.displayMembers(result.members);
			return;
		}
		OCA.notification.onFail(
			t('circles', "Members of the group '{name}' could not be invited to the circle",
				{name: result.display}) +
			': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	},


	removeMemberResult: function(result) {
		if (result.status === 1) {

			elements.mainUIMembersTable.children("[member-id='" + result.user_id + "']").each(
				function() {
					if (Number($(this).attr('member-type')) === result.user_type) {
						$(this).hide(300);
					}
				});
			OCA.notification.onSuccess(t('circles', "Member was removed from the circle"));
			return;
		}

		OCA.notification.onFail(
			t('circles', "Member could not be removed from the circle") +
			': ' + ((result.error) ? result.error : t('circles', 'no error message')));
	},

	levelMemberResult: function(result) {
		if (result.status === 1) {
			OCA.notification.onSuccess(
				t('circles', "Member updated"));

			nav.displayMembers(result.members);
			return;
		}

		nav.displayMembers('');
		OCA.notification.onFail(
			t('circles', "Member could not be updated") + ': ' +
			((result.error) ? result.error : t('circles', 'no error message')));
	}

};
