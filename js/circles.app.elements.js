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
	circlesSearch: null,
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
		elements.circlesSearch = $('#circles_search');
		elements.circlesFilters = $('#circles_filters');
		elements.circlesDetails = $('#circle_details');
		elements.emptyContent = $('#emptycontent');
		elements.mainUI = $('#mainui');
		elements.mainUIMembers = $('#memberslist_table');
		elements.membersSearchResult = $('#members_search_result');
		elements.memberDetails = $('#memberdetails');
		elements.memberRequest = $('#member_request');

		elements.joinCircleInteraction = $('#sjoincircle_interact');
		elements.joinCircleAccept = $('#joincircle_acceptinvit');
		elements.joinCircleReject = $('#joincircle_rejectinvit');
		elements.joinCircleRequest = $('#joincircle_request');
		elements.joinCircleInvite = $('#joincircle_invit');
		elements.joinCircle = $('#joincircle');
		elements.leaveCircle = $('#leavecircle');
		elements.rightPanel = $('#rightpanel');
		elements.addMember = $('#addmember');
		elements.remMember = $('#remmember');
	},


	initTweaks: function () {
		$.fn.emptyTable = function () {
			this.children('tr').each(function () {
				if ($(this).attr('class') !== 'header') {
					$(this).remove();
				}
			});
		};
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

		var theme = $('#body-user').find('#header').css('background-color');
		elements.circlesList.css('background-color', theme);
		elements.circlesDetails.css('background-color', theme);
		elements.rightPanel.css('background-color', theme);

		elements.membersSearchResult.hide();
	},


	/**
	 *
	 */
	initExperienceCirclesList: function () {

		elements.circlesList.children('div').on('click', function () {
			nav.displayCirclesList($(this).attr('circle-type'));
		});

		this.initExperienceCirclesListFromSearch();
		this.initExperienceCirclesListFromFilter();
	},


	initExperienceCirclesListFromSearch: function () {

		this.circlesSearch.on('input property paste focus', function () {
			var search = $(this).val().trim();
			if (curr.searchCircle === search) {
				return;
			}

			curr.searchCircle = search;
			api.searchCircles(curr.circlesType, curr.searchCircle, curr.searchFilter,
				actions.listCirclesResult);
		});
	},


	initExperienceCirclesListFromFilter: function () {

		this.circlesFilters.on('input property paste focus', function () {
			var searchFilter = $(this).val();
			if (curr.searchFilter === searchFilter) {
				return;
			}

			curr.searchFilter = searchFilter;
			api.searchCircles(curr.circlesType, curr.searchCircle, curr.searchFilter,
				actions.listCirclesResult);
		});

	},


	initExperienceMemberDetails: function () {
		elements.memberRequest.hide();
		elements.remMember.on('click', function () {
			api.removeMember(curr.circle, curr.member, actions.removeMemberResult);
		});

		$('#joincircle_acceptrequest').on('click', function () {
			api.addMember(curr.circle, curr.member, actions.addMemberResult);
		});
		$('#joincircle_rejectrequest').on('click', function () {
			api.removeMember(curr.circle, curr.member, actions.removeMemberResult);
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
			var currSearch = elements.addMember.val().trim();
			var line = value.label + '   (' + value.value.shareWith + ')';
			if (currSearch.length > 0) {
				line = line.replace(new RegExp('(' + currSearch + ')', 'gi'), '<b>$1</b>');
			}

			elements.membersSearchResult.append(
				'<div class="members_search" searchresult="' + value.value.shareWith + '">' + line +
				'</div>');
		});

	},


	resetCirclesList: function () {

		elements.navigation.addClass('selected');
		elements.navigation.children().each(function () {
			if ($(this).attr('id') !== 'circles_search' &&
				$(this).attr('id') !== 'circles_filters') {
				$(this).remove();
			}
		});
	},


	removeMemberslistEntry: function (membername) {
		this.mainUIMembers.children("[member-id='" + membername + "']").each(
			function () {
				$(this).hide(300);
			});
	},


	generateTmplCircle: function (entry) {
		var tmpl = $('#tmpl_circle').html();

		tmpl = tmpl.replace(/%title%/g, entry.name);
		tmpl = tmpl.replace(/%type%/g, entry.type);
		tmpl = tmpl.replace(/%owner%/g, entry.owner.user_id);
		tmpl = tmpl.replace(/%status%/g, entry.user.status);
		tmpl = tmpl.replace(/%level_string%/g, entry.user.level_string);
		tmpl = tmpl.replace(/%count%/g, entry.count);
		tmpl = tmpl.replace(/%creation%/g, entry.creation);

		return tmpl;
	},


	generateTmplMember: function (entry) {
		var tmpl = $('#tmpl_member').html();

		tmpl = tmpl.replace(/%username%/g, entry.user_id);
		tmpl = tmpl.replace(/%level%/g, entry.level);
		tmpl = tmpl.replace(/%levelstring%/g, entry.level_string);
		tmpl = tmpl.replace(/%status%/g, entry.status);
		tmpl = tmpl.replace(/%joined%/g, entry.joined);
		tmpl = tmpl.replace(/%note%/g, entry.note);

		return tmpl;
	}


};