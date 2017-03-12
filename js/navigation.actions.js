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



var actions = {


		createCircleResult: function (result) {
			var str = this.getStringTypeFromType(result.type);

			if (result.status == 1) {
				OCA.notification.onSuccess(str + " '" + result.name + "' created");
				nav.displayCirclesList(result.circle.type);
				nav.selectCircle(result.circle.id);
				return;
			}

			OCA.notification.onFail(
				str + " '" + result.name + "' NOT created: " +
				((result.error) ? result.error : 'no error message'));
		},


		displayMembersInteraction: function (details) {
			if (details.user.level < 6) {
				navdiv.addMember.hide();
			} else {
				navdiv.addMember.show();
			}

			this.displayNonMemberInteraction(details);

			if (details.user.level == 9) {
				navdiv.joinCircle.hide();
				navdiv.leaveCircle.hide();
				return;
			}

			if (details.user.level >= 1) {
				navdiv.joinCircle.hide();
				navdiv.leaveCircle.show();
			}

		},


		/**
		 *
		 * @param search
		 */
		searchMembersRequest: function (search) {

			if (curr.searchUser == search) {
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

			if (response === null ||
				(response.ocs.data.users === 0 && response.ocs.data.exact.users === 0)) {
				navdiv.membersSearchResult.fadeOut(300);
				return;
			}

			navdiv.membersSearchResult.children().remove();

			navdiv.fillMembersSearch(response.ocs.data.exact.users, response.ocs.data.users);

			$('.members_search').on('click', function () {
				api.addMember(curr.circle, $(this).attr('searchresult'),
					actions.addMemberResult);
			});
			navdiv.membersSearchResult.fadeIn(300);

		},


		addMemberResult: function (result) {

			if (result.status == 1) {
				OCA.notification.onSuccess(
					"Member '" + result.name + "' successfully added to the circle");

				navdiv.displayMembers(result.members);
				return;
			}
			OCA.notification.onFail(
				"Member '" + result.name + "' NOT added to the circle: " +
				((result.error) ? result.error : 'no error message'));
		},


		getStringTypeFromType: function (type) {
			switch (type) {
				case '1':
					return 'Personal circle';
				case '2':
					return 'Hidden circle';
				case '4':
					return 'Private circle';
				case '8':
					return 'Public circle';
			}

			return 'Circle';
		},


		removeMemberResult: function (result) {
			if (result.status == 1) {

				navdiv.mainUIMembers.children("[member-id='" + result.name + "']").each(
					function () {
						$(this).hide(300);
					});
				OCA.notification.onSuccess(
					"Member '" + result.name + "' successfully removed from the circle");
				return;
			}

			OCA.notification.onFail(
				"Member '" + result.name + "' NOT removed from the circle: " +
				((result.error) ? result.error : 'no error message'));
		},


		/**
		 *
		 */
		onEventNewCircle: function () {

			curr.circle = 0;
			curr.circleLevel = 0;

			navdiv.navigation.hide('slide', 800);
			navdiv.circlesList.children('div').removeClass('selected');
			navdiv.emptyContent.show(800);
			navdiv.mainUI.fadeOut(800);
		}
		,

		/**
		 *
		 */
		onEventNewCircleName: function () {
			this.onEventNewCircle();
			this.displayOptionsNewCircle((navdiv.newName.val() !== ''));
		}
		,

		/**
		 *
		 */
		onEventNewCircleType: function () {
			this.onEventNewCircle();
			navdiv.newTypeDefinition.children('div').fadeOut(300);
			$('#circles_new_type_' + navdiv.newType.children('option:selected').val()).fadeIn(
				300);
		}


	}
	;

// $(document).ready(function () {
// (function () {
//
//
// Navigation.prototype.displayMembersInteraction
// 	= function (details) {
// 	if (details.user.level < 6) {
// 		divAddMember.hide();
// 	} else {
// 		divAddMember.show();
// 	}
//
// 	this.displayNonMemberInteraction(details);
//
// 	if (details.user.level == 9) {
// 		divJoinCircle.hide();
// 		divLeaveCircle.hide();
// 		return;
// 	}
//
// 	if (details.user.level >= 1) {
// 		divJoinCircle.hide();
// 		divLeaveCircle.show();
// 	}
// }


// });

// (function () {
//
// 	var Actions = function () {
// 		this.initialize();
// 	};
//
// 	Actions.prototype = {
//
//
// 		initialize: function () {
//
//
//
//
// 			/**
// 			 *
// 			 */
//
//
//
//
//
//
//
// 	}
//
// 	OCA.Circles.Actions = Actions;
// 	OCA.Circles.actions = new Actions();
//
// })
// ();
//
