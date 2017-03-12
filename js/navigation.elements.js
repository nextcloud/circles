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
/** global: actions */
/** global: curr */
/** global: api */


var elements = {

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

		elements.newTypeDefinition = $('#circles_new_type_definition');
		elements.newType = $('#circles_new_type');
		elements.newSubmit = $('#circles_new_submit');
		elements.newName = $('#circles_new_name');
		elements.navigation = $('#app-navigation.circles');
		elements.circlesList = $('#circles_list');
		elements.emptyContent = $('#emptycontent');
		elements.mainUI = $('#mainui');
		elements.mainUIMembers = $('#memberslist_table');
		elements.membersSearchResult = $('#members_search_result');

		elements.joinCircleAccept = $('#joincircle_acceptinvit');
		elements.joinCircleReject = $('#joincircle_rejectinvit');
		elements.joinCircleRequest = $('#joincircle_request');
		elements.joinCircleInvite = $('#joincircle_invit');
		elements.joinCircle = $('#joincircle');
		elements.leaveCircle = $('#leavecircle');
		elements.addMember = $('#addmember');

		this.initElementsActions();
	},


	initUI: function () {
		elements.newTypeDefinition.children('div').fadeOut(0);
		$('#circles_new_type_' + elements.newType.children('option:selected').val()).fadeIn(
			0);

		elements.newType.hide();
		elements.newSubmit.hide();
		elements.newTypeDefinition.hide();

		$('.icon-circles').css('background-image',
			'url(' + OC.imagePath('circles', 'colored') + ')');

		elements.membersSearchResult.hide();
	},


	initElementsActions: function () {

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

		elements.addMember.on('input propertychange paste focus', function () {
			actions.searchMembersRequest($(this).val().trim());
		}).blur(function () {
			elements.membersSearchResult.fadeOut(400);
		});
	},


	/**
	 *
	 */
	initAnimationNewCircle: function () {

		elements.newName.on('keyup', function () {
			actions.onEventNewCircleName();
		});

		elements.newType.on('change', function () {
			actions.onEventNewCircleType();
		});

		elements.newSubmit.on('click', function () {
			api.createCircle(elements.newType.val(), elements.newName.val(),
				actions.createCircleResult);
		});

	},


	fillMembersSearch: function (exact, partial) {
		this.fillExactMembersSearch(exact);
		this.fillPartialMembersSearch(partial);
		elements.membersSearchResult.children().first().css('border-top-width', '0px');
	},

	fillExactMembersSearch: function (exact) {

		$.each(exact, function (index, value) {
			elements.membersSearchResult.append(
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

			elements.membersSearchResult.append(
				'<div class="members_search" searchresult="' +
				value.value.shareWith +
				'">' + line + '</div>');
		});

	},


	displayMembers: function (members) {

		elements.mainUIMembers.emptyTable();

		if (members === null) {
			elements.mainUIMembers.hide(200);
			return;
		}

		elements.mainUIMembers.show(200);
		for (var i = 0; i < members.length; i++) {
			elements.mainUIMembers.append(this.generateTmplMember(members[i]));
		}

		if (curr.circleLevel >= 6) {
			elements.mainUIMembers.children("[member-level!='9']").each(function () {
				$(this).children('.delete').show(0);
			});

			elements.mainUIMembers.children('.delete').on('click', function () {
				var member = $(this).parent().attr('member-id');
				api.removeMember(curr.circle, member, actions.removeMemberResult);
			});
		}

	}

};