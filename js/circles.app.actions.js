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


var actions = {


	initActions: function () {
		this.initElementsMemberActions();
		this.initElementsCircleActions();
	},

	initElementsMemberActions: function () {

		elements.addMember.on('input propertychange paste focus', function () {
			actions.searchMembersRequest($(this).val().trim());
		}).blur(function () {
			elements.membersSearchResult.fadeOut(400);
		});

	},


	joinCircleResult: function (result) {
		if (result.status == 1) {

			elements.removeMemberslistEntry(result.name);
			if (result.member.level == 1) {
				OCA.notification.onSuccess(
					"You have successfully joined this circle");
			} else {
				OCA.notification.onSuccess(
					"You have requested an invitation to join this circle");
			}
			actions.selectCircle(result.circle_id);
			return;
		}

		OCA.notification.onFail(
			"Cannot join this circle: " +
			((result.error) ? result.error : 'no error message'));
	},


	leaveCircleResult: function (result) {
		if (result.status == 1) {

			elements.mainUIMembers.children("[member-id='" + result.name + "']").each(
				function () {
					$(this).hide(300);
				});

			actions.selectCircle(result.circle_id);
			OCA.notification.onSuccess(
				"You have successfully left this circle");
			return;
		}

		OCA.notification.onFail(
			"Cannot leave this circle: " +
			((result.error) ? result.error : 'no error message'));

	},


	initElementsCircleActions: function () {

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
	}
	,


	createCircleResult: function (result) {
		var str = actions.getStringTypeFromType(result.type);

		if (result.status == 1) {
			OCA.notification.onSuccess(str + " '" + result.name + "' created");
			nav.displayCirclesList(result.circle.type);
			actions.selectCircle(result.circle.id);
			return;
		}

		OCA.notification.onFail(
			str + " '" + result.name + "' NOT created: " +
			((result.error) ? result.error : 'no error message'));
	},


	selectCircleResult: function (result) {

		elements.mainUIMembers.emptyTable();
		if (result.status < 1) {
			OCA.notification.onFail(
				'Issue while retreiving the details of a circle: ' +
				((result.error) ? result.error : 'no error message'));
			return;
		}

		elements.navigation.children('.circle').removeClass('selected');
		elements.navigation.children(".circle[circle-id='" + result.circle_id + "']").each(
			function () {
				$(this).addClass('selected');
			});

		elements.emptyContent.hide(800);
		elements.mainUI.fadeIn(800);
		curr.circle = result.circle_id;
		curr.circleLevel = result.details.user.level;

		nav.displayMembersInteraction(result.details);
		nav.displayMembers(result.details.members);
	},


	listCirclesResult: function (result) {

		if (result.status < 1) {
			OCA.notification.onFail(
				'Issue while retreiving the list of the Circles: ' +
				((result.error) ? result.error : 'no error message'));
			return;
		}

		elements.resetCirclesList();

		var data = result.data;
		for (var i = 0; i < data.length; i++) {
			var tmpl = elements.generateTmplCircle(data[i]);
			elements.navigation.append(
				'<div class="circle" circle-id="' + data[i].id + '">' + tmpl + '</div>');
		}

		elements.navigation.children('.circle').on('click', function () {
			actions.selectCircle($(this).attr('circle-id'));
		});
	},


	selectCircle: function (circle_id) {
		curr.searchUser = '';
		elements.addMember.val('');

		api.detailsCircle(circle_id, actions.selectCircleResult);
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
			elements.membersSearchResult.fadeOut(300);
			return;
		}

		elements.membersSearchResult.children().remove();

		elements.fillMembersSearch(response.ocs.data.exact.users, response.ocs.data.users);

		$('.members_search').on('click', function () {
			api.addMember(curr.circle, $(this).attr('searchresult'),
				actions.addMemberResult);
		});
		elements.membersSearchResult.fadeIn(300);

	},


	addMemberResult: function (result) {

		if (result.status == 1) {
			OCA.notification.onSuccess(
				"Member '" + result.name + "' successfully added to the circle");

			nav.displayMembers(result.members);
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

			elements.mainUIMembers.children("[member-id='" + result.name + "']").each(
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

		elements.navigation.hide('slide', 800);
		elements.circlesList.children('div').removeClass('selected');
		elements.emptyContent.show(800);
		elements.mainUI.fadeOut(800);
	},

	/**
	 *
	 */
	onEventNewCircleName: function () {
		this.onEventNewCircle();
		nav.displayOptionsNewCircle((elements.newName.val() !== ''));
	},

	/**
	 *
	 */
	onEventNewCircleType: function () {
		this.onEventNewCircle();
		elements.newTypeDefinition.children('div').fadeOut(300);
		$('#circles_new_type_' + elements.newType.children('option:selected').val()).fadeIn(
			300);
	}


};
