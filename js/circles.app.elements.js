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
	circleDetails: null,
	circleDesc: null,
	emptyContent: null,
	mainUI: null,
	mainUIMembersTable: null,
	mainUIGroupsTable: null,
	mainUILinksTable: null,
	membersSearchResult: null,
	groupsSearchResult: null,
	memberDetails: null,
	memberRequest: null,

	joinCircleInteraction: null,
	joinCircleAccept: null,
	joinCircleReject: null,
	joinCircleRequest: null,
	joinCircleInvite: null,
	joinCircle: null,
	adminSettingsCircle: null,
	leaveCircle: null,
	destroyCircle: null,

	settingsPanel: null,
	settingsName: null,
	settingsDesc: null,
	settingsLimit: null,
	settingsEntryLimit: null,
	settingsLink: null,
	settingsLinkAuto: null,
	settingsLinkFiles: null,
	settingsEntryLink: null,
	settingsEntryLinkAuto: null,
	settingsEntryLinkFiles: null,
	settingsSave: null,

	addMember: null,
	linkGroup: null,
	linkCircle: null,

	buttonCircleActions: null,
	buttonCircleActionReturn: null,
	buttonCircleSettings: null,
	buttonAddMember: null,
	buttonLinkGroup: null,
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
		elements.circleDetails = $('#circle_details')
		elements.circleDesc = $('#circle_desc');
		elements.emptyContent = $('#emptycontent');
		elements.mainUI = $('#mainui');

		elements.mainUIMembers = $('#memberslist');
		elements.mainUIMembersTable = $('#memberslist_table');
		elements.mainUIGroupsTable = $('#groupslist_table');
		elements.mainUILinksTable = $('#linkslist_table');
		elements.membersSearchResult = $('#members_search_result');
		elements.groupsSearchResult = $('#groups_search_result');
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
		elements.adminSettingsCircle = $('#adminsettingscircle');
		elements.adminSettingsCircle.hide().on('click', function () {
			settings.displaySettings(true);
		});

		elements.settingsPanel = $('#settings-panel');
		elements.settingsName = $('#settings-name');
		elements.settingsDesc = $('#settings-desc');
		elements.settingsEntryLimit = $('#settings-entry-limit');
		elements.settingsLimit = $('#settings-limit');
		// elements.settingsLimit.prop('disabled', !OC.isUserAdmin());

		elements.settingsLink = $('#settings-link');
		elements.settingsLinkAuto = $('#settings-link-auto');
		elements.settingsLinkFiles = $('#settings-link-files');
		elements.settingsEntryLink = $('#settings-entry-link');
		elements.settingsEntryLinkAuto = $('#settings-entry-link-auto');
		elements.settingsEntryLinkFiles = $('#settings-entry-link-files');
		elements.settingsSave = $('#settings-submit');

		elements.addMember = $('#addmember');
		elements.linkGroup = $('#linkgroup');
		elements.linkCircle = $('#linkcircle');

		elements.buttonCircleActions = $('#circle-actions-buttons');
		elements.buttonCircleActionReturn = $('#circle-actions-return');
		elements.buttonCircleSettings = $('#circle-actions-settings');
		elements.buttonAddMember = $('#circle-actions-add');
		elements.buttonLinkGroup = $('#circle-actions-group');
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
		$('.icon-link-group').css('background-image',
			'url(' + OC.imagePath('circles', 'link-group') + ')');
		$('.icon-join').css('background-image',
			'url(' + OC.imagePath('circles', 'join') + ')');

		var theme = $('#body-user').find('#header').css('background-color');
		elements.circlesList.css('background-color', theme);
		elements.circleDetails.css('background-color', theme);

		elements.membersSearchResult.hide();
		elements.groupsSearchResult.hide();
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
			nav.displayLinkGroupInput(false);
			nav.displayLinkCircleInput(false);
			nav.displayJoinCircleButton(false);
		});

		elements.buttonLinkGroup.on('click', function () {
			settings.displaySettings(false);
			nav.displayCircleButtons(false);
			nav.displayAddMemberInput(false);
			nav.displayLinkGroupInput(true);
			nav.displayLinkCircleInput(false);
			nav.displayJoinCircleButton(false);
		});

		elements.buttonLinkCircle.on('click', function () {
			settings.displaySettings(false);
			nav.displayCircleButtons(false);
			nav.displayAddMemberInput(false);
			nav.displayLinkGroupInput(false);
			nav.displayLinkCircleInput(true);
			nav.displayJoinCircleButton(false);
		});

		elements.buttonCircleSettings.on('click', function () {
			settings.displaySettings(true);
			nav.displayCircleButtons(false);
			nav.displayAddMemberInput(false);
			nav.displayLinkGroupInput(false);
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
			api.createCircle(Number(elements.newType.val()), elements.newName.val(),
				resultCircles.createCircleResult);
		});

	},


	emptyCircleCreation: function () {
		elements.newName.val('');
		elements.newType.val('');
	},


	fillMembersSearch: function (source, exact, partial) {
		this.fillExactMembersSearch(source, exact);
		this.fillPartialMembersSearch(source, partial);
		elements.membersSearchResult.children().first().css('border-top-width', '0px');
	},


	fillExactMembersSearch: function (source, exact) {
		curr.exactMemberSearchType = '';
		$.each(exact, function (index, value) {
			var details = escapeHTML(value.value.shareWith);
			if (source === 'groups') {
				if (exact.length === 1) {
					curr.exactMemberSearchType = 'group';
				}
				details = 'group';
			}

			elements.membersSearchResult.append(
				'<div class="members_search exact" source="' + source + '" searchresult="' +
				escapeHTML(value.value.shareWith) + '">' + escapeHTML(value.label) + '   (' +
				details + ')</div>');
		});

	},


	fillPartialMembersSearch: function (source, partial) {
		$.each(partial, function (index, value) {

			var currSearch = elements.addMember.val().trim();
			var line = escapeHTML(value.label);

			if (source === 'groups') {
				if (currSearch.length > 0) {
					line = line.replace(new RegExp('(' + currSearch + ')', 'gi'), '<b>$1</b>');
				}
				line += '   (group)';
			} else {
				line += '   (' + escapeHTML(value.value.shareWith) + ')';
				if (currSearch.length > 0) {
					line = line.replace(new RegExp('(' + currSearch + ')', 'gi'), '<b>$1</b>');
				}
			}

			elements.membersSearchResult.append(
				'<div class="members_search" source="' + source + '" searchresult="' +
				escapeHTML(value.value.shareWith) + '">' + line + '</div>');
		});
	},


	fillGroupsSearch: function (exact, partial) {
		this.fillExactGroupsSearch(exact);
		this.fillPartialGroupsSearch(partial);
		elements.groupsSearchResult.children().first().css('border-top-width', '0px');
	},


	fillExactGroupsSearch: function (exact) {
		$.each(exact, function (index, value) {
			elements.groupsSearchResult.append(
				'<div class="groups_search exact" searchresult="' +
				escapeHTML(value.value.shareWith) + '">' + escapeHTML(value.label) + '   (' +
				escapeHTML(value.value.shareWith) + ')</div>');
		});

	},


	fillPartialGroupsSearch: function (partial) {
		$.each(partial, function (index, value) {

			var currSearch = elements.addMember.val().trim();
			var line = escapeHTML(value.label) + '   (' + escapeHTML(value.value.shareWith) + ')';
			if (currSearch.length > 0) {
				line = line.replace(new RegExp('(' + currSearch + ')', 'gi'), '<b>$1</b>');
			}

			elements.groupsSearchResult.append(
				'<div class="groups_search" searchresult="' +
				escapeHTML(value.value.shareWith) + '">' + line + '</div>');
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


	generateTmplCircle: function (entry) {
		var tmpl = $('#tmpl_circle').html();

		tmpl = tmpl.replace(/%title%/g, escapeHTML(entry.name));
		tmpl = tmpl.replace(/%type%/g, t('circles', escapeHTML(entry.type_string)));
		tmpl = tmpl.replace(/%owner%/g, escapeHTML(entry.owner.display_name));
		tmpl = tmpl.replace(/%status%/g, t('circles', escapeHTML(entry.user.status)));
		tmpl = tmpl.replace(/%level_string%/g, t('circles', escapeHTML(entry.user.level_string)));
		tmpl = tmpl.replace(/%creation%/g, escapeHTML(entry.creation));

		return tmpl;
	},


	generateTmplMember: function (entry) {
		var tmpl = $('#tmpl_member').html();

		tmpl = tmpl.replace(/%username%/g, escapeHTML(entry.user_id));
		tmpl = tmpl.replace(/%type%/g, escapeHTML(entry.user_type));
		tmpl = tmpl.replace(/%displayname%/g, escapeHTML(entry.display_name));
		tmpl = tmpl.replace(/%level%/g, escapeHTML(entry.level));
		tmpl = tmpl.replace(/%levelString%/g, escapeHTML(entry.level_string));
		tmpl = tmpl.replace(/%status%/g, escapeHTML(entry.status));
		tmpl = tmpl.replace(/%joined%/g, escapeHTML(entry.joined));

		return tmpl;
	},


	generateTmplGroup: function (entry) {
		var tmpl = $('#tmpl_group').html();

		tmpl = tmpl.replace(/%groupid%/g, escapeHTML(entry.user_id));
		tmpl = tmpl.replace(/%level%/g, escapeHTML(entry.level));
		tmpl = tmpl.replace(/%levelString%/g, escapeHTML(entry.level_string));
		tmpl = tmpl.replace(/%joined%/g, escapeHTML(entry.joined));

		return tmpl;
	},


	generateTmplLink: function (entry) {
		var tmpl = $('#tmpl_link').html();

		tmpl = tmpl.replace(/%id%/g, escapeHTML(entry.unique_id));
		tmpl = tmpl.replace(/%token%/g, escapeHTML(entry.token));
		tmpl = tmpl.replace(/%address%/g, escapeHTML(entry.address));
		tmpl = tmpl.replace(/%status%/g, escapeHTML(entry.status));
		tmpl = tmpl.replace(/%joined%/g, escapeHTML(entry.creation));

		return tmpl;
	}


};