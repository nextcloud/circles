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


var navdiv = {


	newTypeDefinition: null,
	newType: null,
	newSubmit: null,
	newName: null,
	navigation: null,
	circlesList: null,
	emptyContent: null,
	mainUI: null,
	mainUIMembers: null,
	membersSearchResult: null,

	joinCircleAccept: null,
	joinCircleReject: null,
	joinCircleRequest: null,
	joinCircleInvite: null,
	joinCircle: null,
	leaveCircle: null,
	addMember: null,


	initElements: function () {

		navdiv.newTypeDefinition = $('#circles_new_type_definition');
		navdiv.newType = $('#circles_new_type');
		navdiv.newSubmit = $('#circles_new_submit');
		navdiv.newName = $('#circles_new_name');
		navdiv.navigation = $('#app-navigation.circles');
		navdiv.circlesList = $('#circles_list');
		navdiv.emptyContent = $('#emptycontent');
		navdiv.mainUI = $('#mainui');
		navdiv.mainUIMembers = $('#memberslist_table');
		navdiv.membersSearchResult = $('#members_search_result');

		navdiv.joinCircleAccept = $('#joincircle_acceptinvit');
		navdiv.joinCircleReject = $('#joincircle_rejectinvit');
		navdiv.joinCircleRequest = $('#joincircle_request');
		navdiv.joinCircleInvite = $('#joincircle_invit');
		navdiv.joinCircle = $('#joincircle');
		navdiv.leaveCircle = $('#leavecircle');
		navdiv.addMember = $('#addmember');

		this.initElementsActions();
	},


	initUI: function () {
		navdiv.newTypeDefinition.children('div').fadeOut(0);
		$('#circles_new_type_' + navdiv.newType.children('option:selected').val()).fadeIn(
			0);

		navdiv.newType.hide();
		navdiv.newSubmit.hide();
		navdiv.newTypeDefinition.hide();

		$('.icon-circles').css('background-image',
			'url(' + OC.imagePath('circles', 'colored') + ')');

		navdiv.membersSearchResult.hide();
	},


	initElementsActions: function () {

		navdiv.joinCircle.on('click', function () {
			api.joinCircle(curr.circle, actions.joinCircleResult);
		});

		navdiv.leaveCircle.on('click', function () {
			api.leaveCircle(curr.circle, actions.leaveCircleResult);
		});

		navdiv.joinCircleAccept.on('click', function () {
			api.joinCircle(curr.circle, actions.joinCircleResult);
		});

		navdiv.joinCircleReject.on('click', function () {
			api.leaveCircle(curr.circle, actions.leaveCircleResult);
		});

		navdiv.addMember.on('input propertychange paste focus', function () {
			actions.searchMembersRequest($(this).val().trim());
		}).blur(function () {
			navdiv.membersSearchResult.fadeOut(400);
		});
	},


	/**
	 *
	 */
	initAnimationNewCircle: function () {

		navdiv.newName.on('keyup', function () {
			actions.onEventNewCircleName();
		});

		navdiv.newType.on('change', function () {
			actions.onEventNewCircleType();
		});

		navdiv.newSubmit.on('click', function () {
			api.createCircle(navdiv.newType.val(), navdiv.newName.val(),
				actions.createCircleResult);
		});

	},


	fillMembersSearch: function (exact, partial) {
		this.fillExactMembersSearch(exact);
		this.fillPartialMembersSearch(partial);
		navdiv.membersSearchResult.children().first().css('border-top-width', '0px');
	},

	fillExactMembersSearch: function (exact) {

		$.each(exact, function (index, value) {
			navdiv.membersSearchResult.append(
				'<div class="members_search exact" searchresult="' +
				value.value.shareWith + '">' + value.label + '   (' +
				value.value.shareWith + ')</div>');
		});

	},

	fillPartialMembersSearch: function (partial) {
		$.each(partial, function (index, value) {
			var currSearch = $('#addmember').val().trim();
			var line = value.label + '   (' + value.value.shareWith + ')';
			if (currSearch.length > 0) {
				line =
					line.replace(new RegExp('(' + currSearch + ')', 'gi'),
						'<b>$1</b>');
			}

			navdiv.membersSearchResult.append(
				'<div class="members_search" searchresult="' +
				value.value.shareWith +
				'">' + line + '</div>');
		});

	},


	displayMembers: function (members) {

		navdiv.mainUIMembers.emptyTable();

		if (members === null) {
			navdiv.mainUIMembers.hide(200);
			return;
		}

		navdiv.mainUIMembers.show(200);
		for (var i = 0; i < members.length; i++) {
			navdiv.mainUIMembers.append(this.generateTmplMember(members[i]));
		}

		if (curr.circleLevel >= 6) {
			navdiv.mainUIMembers.children("[member-level!='9']").each(function () {
				$(this).children('.delete').show(0);
			});

			navdiv.mainUIMembers.children('.delete').on('click', function () {
				var member = $(this).parent().attr('member-id');
				api.removeMember(curr.circle, member, actions.removeMemberResult);
			});
		}

	}

};