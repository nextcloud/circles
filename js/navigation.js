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

var api = OCA.Circles.api;
var curr = {
	circlesType: '',
	circle: 0,
	circleLevel: 0,
	searchCircle: '',
	searchUser: ''
};


var nav = {

	displayCirclesList: function (type) {

		curr.circlesType = type;
		curr.searchCircle = '';
		curr.searchUser = '';

		curr.circle = 0;
		curr.circleLevel = 0;

		navdiv.navigation.show('slide', 800);
		navdiv.emptyContent.show(800);
		navdiv.mainUI.fadeOut(800);

		$('#circles_search').val('');
		$('#addmember').val('');

		this.UIresetCirclesList(type);
		api.listCircles(type, self.listCirclesResult);
	},

	selectCircle: function (circle_id) {
		curr.searchUser = '';
		navdiv.addMember.val('');

		api.detailsCircle(circle_id, this.selectCircleResult);
	}


};


$(document).ready(function () {

	/**
	 * @constructs Navigation
	 */



	var Navigation = function () {

			$.extend(Navigation.prototype, curr);
			$.extend(Navigation.prototype, nav);
			$.extend(Navigation.prototype, navdiv);
			$.extend(Navigation.prototype, actions);

			this.initialize();

		} || {};


	Navigation.prototype = {


		initialize: function () {

			var self = this;

			navdiv.initElements();
			navdiv.initUI();

			//
			// /**
			//  *
			//  * @constructor
			//  */
			// this.UIReset = function () {
			// 	navdiv.newTypeDefinition.children('div').fadeOut(0);
			// 	$('#circles_new_type_' +
			// navdiv.newType.children('option:selected').val()).fadeIn( 0);
			// navdiv.newType.hide(); navdiv.newSubmit.hide(); navdiv.newTypeDefinition.hide();
			// $('.icon-circles').css('background-image', 'url(' + OC.imagePath('circles',
			// 'colored') + ')');  navdiv.membersSearchResult.hide(); };

			/**
			 *
			 */
			this.initTweaks = function () {
				$.fn.emptyTable = function () {
					this.children('tr').each(function () {
						if ($(this).attr('class') != 'header') {
							$(this).remove();
						}
					});
				};


			};


			//
			// this.displayMembersInteraction = function (details) {
			// 	if (details.user.level < 6) {
			// 		divAddMember.hide();
			// 	} else {
			// 		divAddMember.show();
			// 	}
			//
			// 	this.displayNonMemberInteraction(details);
			//
			// 	if (details.user.level == 9) {
			// 		navdiv.joinCircle.hide();
			// 		navdiv.leaveCircle.hide();
			// 		return;
			// 	}
			//
			// 	if (details.user.level >= 1) {
			// 		navdiv.joinCircle.hide();
			// 		navdiv.leaveCircle.show();
			// 	}
			//
			// };


			this.displayNonMemberInteraction = function (details) {
				navdiv.joinCircleAccept.hide();
				navdiv.joinCircleReject.hide();
				navdiv.joinCircleRequest.hide();
				navdiv.joinCircleInvite.hide();

				if (details.user.status == 'Invited') {
					navdiv.joinCircleInvite.show();
					navdiv.joinCircleAccept.show();
					navdiv.joinCircleReject.show();
					navdiv.joinCircle.hide();
					navdiv.leaveCircle.hide();
					return;
				}

				if (details.user.status == 'Requesting') {
					navdiv.joinCircleRequest.show();
					navdiv.joinCircle.hide();
					navdiv.leaveCircle.show();
					return;
				}

				navdiv.joinCircle.show();
				navdiv.leaveCircle.hide();
			};


			// /**
			//  *
			//  */
			// this.initAnimationNewCircle = function () {
			//
			// 	navdiv.newName.on('keyup', function () {
			// 		self.onEventNewCircleName();
			// 	});
			//
			// 	navdiv.newType.on('change', function () {
			// 		self.onEventNewCircleType();
			// 	});
			//
			// 	navdiv.newSubmit.on('click', function () {
			// 		api.createCircle(navdiv.newType.val(), navdiv.newName.val(),
			// 			self.createCircleResult);
			// 	});
			//
			// };


			/**
			 *
			 * @param display
			 */
			this.displayOptionsNewCircle = function (display) {
				if (display) {
					navdiv.newType.fadeIn(300);
					navdiv.newSubmit.fadeIn(500);
					navdiv.newTypeDefinition.fadeIn(700);
				}
				else {
					navdiv.newType.fadeOut(700);
					navdiv.newSubmit.fadeOut(500);
					navdiv.newTypeDefinition.fadeOut(300);
				}
			};


			/**
			 *
			 */
			this.initExperienceCirclesList = function () {

				navdiv.circlesList.children('div').on('click', function () {
					self.displayCirclesList($(this).attr('circle-type'));
				});

				$('#circles_search').on('input propertychange paste focus', function () {
					if (curr.searchCircle == $(this).val().trim()) {
						return;
					}

					curr.searchCircle = $(this).val().trim();
					api.searchCircles(curr.circlesType, $(this).val().trim(),
						self.listCirclesResult);
				});

			};


			/**
			 *
			 * @param type
			 */
			this.displayCirclesList = function (type) {

				curr.circlesType = type;
				curr.searchCircle = '';
				curr.searchUser = '';

				curr.circle = 0;
				curr.circleLevel = 0;

				navdiv.navigation.show('slide', 800);
				navdiv.emptyContent.show(800);
				navdiv.mainUI.fadeOut(800);

				$('#circles_search').val('');
				$('#addmember').val('');

				this.UIresetCirclesList(type);
				api.listCircles(type, self.listCirclesResult);
			};


			/**
			 *
			 * @constructor
			 */
			this.UIresetCirclesList = function (type) {

				navdiv.circlesList.children('div').removeClass('selected');
				navdiv.circlesList.children().each(function () {
					if ($(this).attr('circle-type') == type.toLowerCase()) {
						$(this).addClass('selected');
					}
				});

				navdiv.navigation.addClass('selected');
				navdiv.navigation.children().each(function () {
					if ($(this).attr('id') != 'circles_search') {
						$(this).remove();
					}
				});
			};


			/**
			 *
			 * @param result
			 */
			this.listCirclesResult = function (result) {

				if (result.status < 1) {
					OCA.notification.onFail(
						'Issue while retreiving the list of the Circles: ' +
						((result.error) ? result.error : 'no error message'));
					return;
				}

				var data = result.data;
				for (var i = 0; i < data.length; i++) {
					var tmpl = self.generateTmplCircle(data[i]);
					navdiv.navigation.append(
						'<div class="circle" circle-id="' + data[i].id + '">' + tmpl +
						'</div>');
				}

				navdiv.navigation.children('.circle').on('click', function () {
					self.selectCircle($(this).attr('circle-id'));
				});
			};


			/**
			 *
			 * @returns {*|jQuery}
			 * @param entry
			 */
			this.generateTmplCircle = function (entry) {
				var tmpl = $('#tmpl_circle').html();

				tmpl = tmpl.replace(/%title%/, entry.name);
				tmpl = tmpl.replace(/%type%/, entry.type);
				tmpl = tmpl.replace(/%owner%/, entry.owner.user_id);
				tmpl = tmpl.replace(/%status%/, entry.user.status);
				tmpl = tmpl.replace(/%level_string%/, entry.user.level_string);
				tmpl = tmpl.replace(/%count%/, entry.count);
				tmpl = tmpl.replace(/%creation%/, entry.creation);

				return tmpl;
			};

			//
			// /**
			//  *
			//  * @param circle_id
			//  */
			// this.selectCircle = function (circle_id) {
			// 	curr.searchUser = '';
			// 	$('#addmember').val('');
			//
			// 	api.detailsCircle(circle_id, this.selectCircleResult);
			// };
			//

			this.selectCircleResult = function (result) {

				navdiv.mainUIMembers.emptyTable();

				if (result.status < 1) {
					OCA.notification.onFail(
						'Issue while retreiving the details of a circle: ' +
						((result.error) ? result.error : 'no error message'));
					return;
				}

				navdiv.navigation.children('.circle').removeClass('selected');
				navdiv.navigation.children(".circle[circle-id='" + result.circle_id + "']").each(
					function () {
						$(this).addClass('selected');
					});

				navdiv.emptyContent.hide(800);
				navdiv.mainUI.fadeIn(800);
				curr.circle = result.circle_id;
				curr.circleLevel = result.details.user.level;

				self.displayMembersInteraction(result.details);
				self.displayMembers(result.details.members);
			};

			//
			// /**
			//  *
			//  * @param search
			//  */
			// this.searchMembersRequest = function (search) {
			//
			// 	if (curr.searchUser == search) {
			// 		return;
			// 	}
			//
			// 	curr.searchUser = search;
			//
			// 	$.get(OC.linkToOCS('apps/files_sharing/api/v1', 1) + 'sharees',
			// 		{
			// 			format: 'json',
			// 			search: search,
			// 			perPage: 200,
			// 			itemType: 'principals'
			// 		}, self.searchMembersResult);
			// };
			//
			//
			// this.searchMembersResult = function (response) {
			//
			// 	if (response === null ||
			// 		(response.ocs.data.users === 0 && response.ocs.data.exact.users === 0)) {
			// 		navdiv.membersSearchResult.fadeOut(300);
			// 		return;
			// 	}
			//
			// 	navdiv.membersSearchResult.children().remove();
			//
			// 	self.searchMembersResultFill(response.ocs.data.exact.users,
			// 		response.ocs.data.users);
			//
			// 	$('.members_search').on('click', function () {
			// 		api.addMember(curr.circle, $(this).attr('searchresult'),
			// 			self.addMemberResult);
			// 	});
			// 	navdiv.membersSearchResult.fadeIn(300);
			//
			// };


			// this.searchMembersResultFill = function (exact, partial) {
			// 	$.each(exact, function (index, value) {
			// 		navdiv.membersSearchResult.append(
			// 			'<div class="members_search exact" searchresult="' +
			// 			value.value.shareWith + '">' + value.label + '   (' +
			// 			value.value.shareWith + ')</div>');
			// 	});
			//
			// 	$.each(partial, function (index, value) {
			// 		var currSearch = $('#addmember').val().trim();
			// 		var line = value.label + '   (' + value.value.shareWith + ')';
			// 		if (currSearch.length > 0) {
			// 			line =
			// 				line.replace(new RegExp('(' + currSearch + ')', 'gi'),
			// 					'<b>$1</b>');
			// 		}
			//
			// 		navdiv.membersSearchResult.append(
			// 			'<div class="members_search" searchresult="' +
			// 			value.value.shareWith +
			// 			'">' + line + '</div>');
			// 	});
			//
			// 	navdiv.membersSearchResult.children().first().css('border-top-width', '0px');
			// };


			// /**
			//  *
			//  * @param result
			//  */
			// this.addMemberResult = function (result) {
			//
			// 	if (result.status == 1) {
			// 		OCA.notification.onSuccess(
			// 			"Member '" + result.name + "' successfully added to the circle");
			//
			// 		self.displayMembers(result.members);
			// 		return;
			// 	}
			// 	OCA.notification.onFail(
			// 		"Member '" + result.name + "' NOT added to the circle: " +
			// 		((result.error) ? result.error : 'no error message'));
			// };


			this.generateTmplMember = function (entry) {
				var tmpl = $('#tmpl_member').html();

				tmpl = tmpl.replace(/%username%/g, entry.user_id);
				tmpl = tmpl.replace(/%level%/g, entry.level);
				tmpl = tmpl.replace(/%levelstring%/g, entry.level_string);
				tmpl = tmpl.replace(/%status%/, entry.status);
				tmpl = tmpl.replace(/%joined%/, entry.joined);
				tmpl = tmpl.replace(/%note%/, entry.note);

				return tmpl;
			};





			this.joinCircleResult = function (result) {
				if (result.status == 1) {

					navdiv.mainUIMembers.children("[member-id='" + result.name + "']").each(
						function () {
							$(this).hide(300);
						});

					if (result.member.level == 1) {
						OCA.notification.onSuccess(
							"You have successfully joined this circle");
					} else {
						OCA.notification.onSuccess(
							"You have requested an invitation to join this circle");
					}
					self.selectCircle(result.circle_id);
					return;
				}

				OCA.notification.onFail(
					"Cannot join this circle: " +
					((result.error) ? result.error : 'no error message'));

			};

			this.leaveCircleResult = function (result) {
				if (result.status == 1) {

					navdiv.mainUIMembers.children("[member-id='" + result.name + "']").each(
						function () {
							$(this).hide(300);
						});

					self.selectCircle(result.circle_id);
					OCA.notification.onSuccess(
						"You have successfully left this circle");
					return;
				}

				OCA.notification.onFail(
					"Cannot leave this circle: " +
					((result.error) ? result.error : 'no error message'));

			};


			/**
			 * Inits
			 */
			this.initTweaks();
		//	this.UIReset();
			this.initAnimationNewCircle();
			this.initExperienceCirclesList();
			// this.initExperienceMembersManagment();
		}
	};


	/**
	 * @constructs Notification
	 */
	var Notification = function () {
		this.initialize();
	};

	Notification.prototype = {

		initialize: function () {

			//noinspection SpellCheckingInspection
			var notyf = new Notyf({
				delay: 5000
			});

			this.onSuccess = function (text) {
				notyf.confirm(text);
			};

			this.onFail = function (text) {
				notyf.alert(text);
			};

		}

	};

	OCA.Circles.Navigation = Navigation;
	OCA.Circles.navigation = new Navigation();

	OCA.Notification = Notification;
	OCA.notification = new Notification();

});

