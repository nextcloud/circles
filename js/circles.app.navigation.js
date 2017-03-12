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
/** global: elements */
/** global: curr */
/** global: api */


var nav = {

	displayCirclesList: function (type) {

		curr.circlesType = type;
		curr.searchCircle = '';
		curr.searchUser = '';

		curr.circle = 0;
		curr.circleLevel = 0;

		elements.navigation.show('slide', 800);
		elements.emptyContent.show(800);
		elements.mainUI.fadeOut(800);

		elements.circlesSearch.val('');
		elements.addMember.val('');

		this.resetCirclesTypeSelection(type);
		elements.resetCirclesList();
		api.listCircles(type, actions.listCirclesResult);
	},


	resetCirclesTypeSelection: function (type) {
		elements.circlesList.children('div').removeClass('selected');
		elements.circlesList.children().each(function () {
			if ($(this).attr('circle-type') == type.toLowerCase()) {
				$(this).addClass('selected');
			}
		});
	},

	/**
	 *
	 * @param display
	 */
	displayOptionsNewCircle: function (display) {
		if (display) {
			elements.newType.fadeIn(300);
			elements.newSubmit.fadeIn(500);
			elements.newTypeDefinition.fadeIn(700);
		}
		else {
			elements.newType.fadeOut(700);
			elements.newSubmit.fadeOut(500);
			elements.newTypeDefinition.fadeOut(300);
		}
	},


	displayMembers: function (members) {

		elements.mainUIMembers.emptyTable();
		if (members === null) {
			elements.mainUIMembers.hide(200);
			return;
		}

		elements.mainUIMembers.show(200);
		for (var i = 0; i < members.length; i++) {
			elements.mainUIMembers.append(elements.generateTmplMember(members[i]));
		}

		this.displayMembersAsModerator();
	},


	displayMembersAsModerator: function () {
		if (curr.circleLevel >= 6) {

			elements.mainUIMembers.children("[member-level!='9']").each(function () {
				$(this).children('.delete').show(0);

				var member = $(this).attr('member-id');
				$(this).children('.delete').on('click', function () {
					api.removeMember(curr.circle, member, actions.removeMemberResult);
				});
			});
		}
	},


	displayMembersInteraction: function (details) {
		if (details.user.level < 6) {
			elements.addMember.hide();
		} else {
			elements.addMember.show();
		}

		this.displayNonMemberInteraction(details);

		if (details.user.level == 9) {
			elements.joinCircle.hide();
			elements.leaveCircle.hide();
			return;
		}

		if (details.user.level >= 1) {
			elements.joinCircle.hide();
			elements.leaveCircle.show();
		}
	},

	displayNonMemberInteraction: function (details) {
		elements.joinCircleAccept.hide();
		elements.joinCircleReject.hide();
		elements.joinCircleRequest.hide();
		elements.joinCircleInvite.hide();

		if (details.user.status == 'Invited') {
			this.displayInvitedMemberInteraction();
			return;
		}

		if (details.user.status == 'Requesting') {
			this.displayRequestingMemberInteraction();
			return;
		}

		elements.joinCircle.show();
		elements.leaveCircle.hide();
	},

	displayInvitedMemberInteraction: function () {
		elements.joinCircleInvite.show();
		elements.joinCircleAccept.show();
		elements.joinCircleReject.show();
		elements.joinCircle.hide();
		elements.leaveCircle.hide();
	},

	displayRequestingMemberInteraction: function () {
		elements.joinCircleRequest.show();
		elements.joinCircle.hide();
		elements.leaveCircle.show();
	}

};

