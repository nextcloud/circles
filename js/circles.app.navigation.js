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


	initNavigation: function () {
		this.initElementsMemberNavigation();
		this.initElementsCircleNavigation();

		this.displayCirclesList('all');
	},


	initElementsMemberNavigation: function () {

		elements.addMember.on('input propertychange paste focus', function () {
			var search = $(this).val().trim();
			if (search === '') {
				elements.membersSearchResult.fadeOut(400);
				return;
			}

			actions.searchMembersRequest(search);
			if (elements.membersSearchResult.children().length === 0) {
				elements.membersSearchResult.fadeOut(400);
			} else {
				elements.membersSearchResult.fadeIn(400);
			}
		}).blur(function () {
			elements.membersSearchResult.fadeOut(400);
		});
	},

	initElementsCircleNavigation: function () {

		elements.joinCircle.on('click', function () {
			api.joinCircle(curr.circle, actions.joinCircleResult);
		});

		elements.leaveCircle.on('click', function () {
			api.leaveCircle(curr.circle, actions.leaveCircleResult);
		});

		elements.joinCircleAccept.on('click', function () {
			api.joinCircle(curr.circle, actions.joinCircleResult);
		});

		elements.joinCircleReject.on('click', function () {
			api.leaveCircle(curr.circle, actions.leaveCircleResult);
		});
	},


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

		elements.remMember.fadeOut(300);
		elements.rightPanel.fadeOut(300);

		elements.mainUIMembers.emptyTable();
		if (members === null) {
			elements.mainUIMembers.hide(200);
			return;
		}

		elements.mainUIMembers.show(200);
		for (var i = 0; i < members.length; i++) {
			var tmpl = elements.generateTmplMember(members[i]);
			elements.mainUIMembers.append(tmpl);
		}

		$('tr.entry').on('click', function () {
			nav.displayMemberDetails($(this).attr('member-id'), $(this).attr('member-level'),
				$(this).attr('member-levelstring'), $(this).attr('member-status'));
		});
	},


	displayMemberDetails: function (id, level, levelstring, status) {

		level = parseInt(level);
		curr.member = id;
		curr.memberLevel = level;
		curr.memberStatus = status;

		elements.rightPanel.fadeIn(300);
		elements.memberDetails.children('#member_name').text(id);
		if (level === 0) {
			levelstring += ' / ' + status;
		}
		elements.memberDetails.children('#member_levelstatus').text(levelstring);

		this.displayMemberDetailsAsModerator();
	},


	displayMemberDetailsAsModerator: function () {
		if (curr.circleLevel >= 6 && curr.memberLevel < curr.circleLevel) {
			if (curr.memberStatus == 'Requesting') {
				elements.memberRequest.fadeIn(300);
				elements.remMember.fadeOut(300);
			}
			else {
				elements.memberRequest.fadeOut(300);
				elements.remMember.fadeIn(300);
			}
		} else {
			elements.remMember.fadeOut(300);
			elements.memberRequest.fadeOut(300);
		}
	},


	displayCircleDetails: function (details) {
		elements.circlesDetails.children('#name').text(details.name);
		elements.circlesDetails.children('#type').text(details.typeLongString);
	},


	displayMembersInteraction: function (details) {
		if (details.user.level < 6) {
			elements.addMember.hide();
		} else {
			elements.addMember.show();
		}

		elements.joinCircleInteraction.hide();
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
		elements.joinCircleInteraction.show();
		elements.joinCircleInvite.show();
		elements.joinCircleAccept.show();
		elements.joinCircleReject.show();
		elements.joinCircle.hide();
		elements.leaveCircle.hide();
	},

	displayRequestingMemberInteraction: function () {
		elements.joinCircleInteraction.show();
		elements.joinCircleRequest.show();
		elements.joinCircle.hide();
		elements.leaveCircle.show();
	}

};

