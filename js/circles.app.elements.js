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
/** global: settings */
/** global: resultCircles */
/** global: curr */
/** global: api */
/** global: define */

var elements = {

	newTypeDefinition: null,
	newType: null,
	newSubmit: null,
	newName: null,
	navigation: null,
	circlesList: null,
	circlesSearch: null,
	circlesFilters: null,
	circlesDetails: null,
	emptyContent: null,
	mainUI: null,
	mainUIMembersTable: null,
	mainUILinksTable: null,
	membersSearchResult: null,
	memberDetails: null,
	memberRequest: null,

	joinCircleInteraction: null,
	joinCircleAccept: null,
	joinCircleReject: null,
	joinCircleRequest: null,
	joinCircleInvite: null,
	joinCircle: null,
	leaveCircle: null,
	destroyCircle: null,

	settingsPanel: null,
	settingsName: null,
	settingsLink: null,
	settingsLinkAuto: null,
	settingsLinkFiles: null,
	settingsEntryLink: null,
	settingsEntryLinkAuto: null,
	settingsEntryLinkFiles: null,
	settingsSave: null,

	rightPanel: null,
	addMember: null,
	remMember: null,
	linkCircle: null,

	buttonCircleActions: null,
	buttonCircleActionReturn: null,
	buttonCircleSettings: null,
	buttonAddMember: null,
	buttonLinkCircle: null,


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

		elements.mainUIMembers = $('#memberslist');
		elements.mainUIMembersTable = $('#memberslist_table');
		elements.mainUILinksTable = $('#linkslist_table');
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
		elements.destroyCircle = $('#circle-actions-delete');

		elements.settingsPanel = $('#settings-panel');
		elements.settingsName = $('#settings-name');
		elements.settingsLink = $('#settings-link');
		elements.settingsLinkAuto = $('#settings-link-auto');
		elements.settingsLinkFiles = $('#settings-link-files');
		elements.settingsEntryLink = $('#settings-entry-link');
		elements.settingsEntryLinkAuto = $('#settings-entry-link-auto');
		elements.settingsEntryLinkFiles = $('#settings-entry-link-files');
		elements.settingsSave = $('#settings-submit');

		elements.rightPanel = $('#rightpanel');
		elements.addMember = $('#addmember');
		elements.remMember = $('#remmember');
		elements.linkCircle = $('#linkcircle');

		elements.buttonCircleActions = $('#circle-actions-buttons');
		elements.buttonCircleActionReturn = $('#circle-actions-return');
		elements.buttonCircleSettings = $('#circle-actions-settings');
		elements.buttonAddMember = $('#circle-actions-add');
		elements.buttonLinkCircle = $('#circle-actions-link');
		elements.buttonJoinCircle = $('#circle-actions-join');
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
		$('.icon-add-user').css('background-image',
			'url(' + OC.imagePath('circles', 'add-user') + ')');
		$('.icon-join').css('background-image',
			'url(' + OC.imagePath('circles', 'join') + ')');

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
			api.listCircles(curr.circlesType, curr.searchCircle, curr.searchFilter,
				resultCircles.listCirclesResult);
		});
	},


	initExperienceCirclesListFromFilter: function () {

		this.circlesFilters.on('input property paste focus', function () {
			var searchFilter = $(this).val();
			if (curr.searchFilter === searchFilter) {
				return;
			}

			curr.searchFilter = searchFilter;
			api.listCircles(curr.circlesType, curr.searchCircle, curr.searchFilter,
				resultCircles.listCirclesResult);
		});

	},


	/**
	 *
	 */
	initExperienceCircleButtons: function () {

		elements.buttonCircleActionReturn.hide();
		elements.buttonCircleActionReturn.on('click', function () {
			nav.circlesActionReturn();
		});

		elements.buttonAddMember.on('click', function () {
			settings.displaySettings(false);
			nav.displayCircleButtons(false);
			nav.displayAddMemberInput(true);
			nav.displayLinkCircleInput(false);
			nav.displayJoinCircleButton(false);
		});

		elements.buttonLinkCircle.on('click', function () {
			settings.displaySettings(false);
			nav.displayCircleButtons(false);
			nav.displayAddMemberInput(false);
			nav.displayLinkCircleInput(true);
			nav.displayJoinCircleButton(false);
		});

		elements.buttonCircleSettings.on('click', function () {
			settings.displaySettings(true);
			nav.displayCircleButtons(false);
			nav.displayAddMemberInput(false);
			nav.displayLinkCircleInput(false);
			nav.displayJoinCircleButton(false);
		});

		elements.buttonJoinCircle.on('click', function () {
			nav.joinCircleAction();
		});

		elements.settingsSave.on('click', function () {
			actions.saveSettings();
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
				resultCircles.createCircleResult);
		});

	},


	emptyCircleCreation: function () {
		elements.newName.val('');
		elements.newType.val('');
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
				escapeHTML(value.value.shareWith) + '">' + escapeHTML(value.label) + '   (' +
				escapeHTML(value.value.shareWith) + ')</div>');
		});

	},


	fillPartialMembersSearch: function (partial) {
		$.each(partial, function (index, value) {
			var currSearch = elements.addMember.val().trim();
			var line = escapeHTML(value.label) + '   (' + escapeHTML(value.value.shareWith) + ')';
			if (currSearch.length > 0) {
				line = line.replace(new RegExp('(' + currSearch + ')', 'gi'), '<b>$1</b>');
			}

			elements.membersSearchResult.append(
				'<div class="members_search" searchresult="' + escapeHTML(value.value.shareWith) +
				'">' + line +
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
		this.mainUIMembersTable.children("[member-id='" + escapeHTML(membername) + "']").each(
			function () {
				$(this).hide(300);
			});
	},


	generateTmplCircle: function (entry) {
		var tmpl = $('#tmpl_circle').html();

		tmpl = tmpl.replace(/%title%/g, escapeHTML(entry.name));
		tmpl = tmpl.replace(/%type%/g, t('circles', escapeHTML(entry.type)));
		tmpl = tmpl.replace(/%owner%/g, escapeHTML(entry.owner.display_name));
		tmpl = tmpl.replace(/%status%/g, t('circles', escapeHTML(entry.user.status)));
		tmpl = tmpl.replace(/%level_string%/g, t('circles', escapeHTML(entry.user.level_string)));
		tmpl = tmpl.replace(/%creation%/g, escapeHTML(entry.creation));

		return tmpl;
	},


	generateTmplMember: function (entry) {
		var tmpl = $('#tmpl_member').html();

		tmpl = tmpl.replace(/%username%/g, escapeHTML(entry.user_id));
		tmpl = tmpl.replace(/%displayname%/g, escapeHTML(entry.display_name));
		tmpl = tmpl.replace(/%level%/g, escapeHTML(entry.level));
		tmpl = tmpl.replace(/%status%/g, escapeHTML(entry.status));
		tmpl = tmpl.replace(/%joined%/g, escapeHTML(entry.joined));

		return tmpl;
	},

	generateTmplLink: function (entry) {
		var tmpl = $('#tmpl_link').html();

		tmpl = tmpl.replace(/%id%/g, escapeHTML(entry.id));
		tmpl = tmpl.replace(/%token%/g, escapeHTML(entry.token));
		tmpl = tmpl.replace(/%address%/g, escapeHTML(entry.address));
		tmpl = tmpl.replace(/%status%/g, escapeHTML(entry.status));
		tmpl = tmpl.replace(/%joined%/g, escapeHTML(entry.creation));

		return tmpl;
	}


};