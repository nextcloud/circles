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

/** global: actions */
/** global: nav */
/** global: elements */
/** global: resultCircles */
/** global: resultLinks */
/** global: curr */
/** global: api */
/** global: define */

var nav = {

	initNavigation: function () {
		this.initElementsAddMemberNavigation();
		this.initElementsLinkCircleNavigation();
		this.initElementsCircleNavigation();

		this.displayCirclesList('all');

		var circleId = window.location.hash.substr(1);
		if (circleId) {
			actions.selectCircle(circleId);
		}
	},


	initElementsAddMemberNavigation: function () {

		elements.addMember.hide();
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
			nav.circlesActionReturn();
		});
		elements.addMember.on('keydown', function (e) {
			if (e.keyCode === 27) {
				nav.circlesActionReturn();
			}
			if (e.keyCode === 13) {
				api.addMember(curr.circle, $(this).val(), resultMembers.addMemberResult);
			}

		});

	},


	initElementsLinkCircleNavigation: function () {

		elements.linkCircle.hide();
		elements.linkCircle.on('keydown', function (e) {

			if (e.keyCode === 27) {
				nav.circlesActionReturn();
			}
			if (e.keyCode !== 13) {
				return;
			}

			api.linkCircle(curr.circle, elements.linkCircle.val(), resultLinks.linkCircleResult);
		}).blur(function () {
			nav.circlesActionReturn();
		});
	},


	initElementsCircleNavigation: function () {

		elements.joinCircle.hide();
		elements.joinCircle.on('click', function () {
			api.joinCircle(curr.circle, resultCircles.joinCircleResult);
			nav.circlesActionReturn();
		});

		elements.leaveCircle.hide();
		elements.leaveCircle.on('click', function () {
			api.leaveCircle(curr.circle, resultCircles.leaveCircleResult);
			nav.circlesActionReturn();
		});

		elements.destroyCircle.on('click', function () {
			OC.dialogs.confirm(
				t('circles', 'Are you sure you want to delete this circle?'),
				t('circles', 'Please confirm'),
				function (e) {
					if (e === true) {
						api.destroyCircle(curr.circle, resultCircles.destroyCircleResult);
					}
				});
		});

		elements.joinCircleAccept.on('click', function () {
			api.joinCircle(curr.circle, resultCircles.joinCircleResult);
		});

		elements.joinCircleReject.on('click', function () {
			api.leaveCircle(curr.circle, resultCircles.leaveCircleResult);
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
		elements.linkCircle.val('');

		this.resetCirclesTypeSelection(type);
		elements.resetCirclesList();
		api.listCircles(type, '', 0, resultCircles.listCirclesResult);
	},


	resetCirclesTypeSelection: function (type) {
		elements.circlesList.children('div').removeClass('selected');
		elements.circlesList.children().each(function () {
			if ($(this).attr('circle-type') === type.toLowerCase()) {
				$(this).addClass('selected');
			}
		});
	},


	circlesActionReturn: function () {
		nav.displayCircleButtons(true);
		nav.displayAddMemberInput(false);
		nav.displayLinkCircleInput(false);
		nav.displayJoinCircleButton(false);
		nav.displayInviteCircleButtons(false);
	},

	joinCircleAction: function () {
		nav.displayCircleButtons(false);
		nav.displayAddMemberInput(false);
		nav.displayLinkCircleInput(false);
		nav.displayJoinCircleButton(true);
	},

	displayCircleButtons: function (display) {
		if (display) {
			elements.buttonCircleActionReturn.hide(define.animationMenuSpeed);
			elements.buttonCircleActions.delay(define.animationMenuSpeed).show(
				define.animationMenuSpeed);
		} else {
			elements.buttonCircleActions.hide(define.animationMenuSpeed);
			elements.buttonCircleActionReturn.delay(define.animationMenuSpeed).show(
				define.animationMenuSpeed);
		}
	},

	displayAddMemberInput: function (display) {
		if (display) {
			elements.addMember.val('');
			elements.addMember.delay(define.animationMenuSpeed).show(define.animationMenuSpeed,
				function () {
					$(this).focus();
				});
		} else {
			elements.addMember.hide(define.animationMenuSpeed);
		}
	},

	displayLinkCircleInput: function (display) {
		if (display) {
			elements.linkCircle.val('');
			elements.linkCircle.delay(define.animationMenuSpeed).show(define.animationMenuSpeed,
				function () {
					$(this).focus();
				});
		} else {
			elements.linkCircle.hide(define.animationMenuSpeed);
		}
	},

	displayInviteCircleButtons: function (display) {
		if (display) {
			elements.joinCircleAccept.show(define.animationMenuSpeed);
			elements.joinCircleReject.delay(define.animationMenuSpeed).show(
				define.animationMenuSpeed);
		} else {
			elements.joinCircleAccept.hide(define.animationMenuSpeed);
			elements.joinCircleReject.hide(define.animationMenuSpeed);
		}
	},

	displayJoinCircleButton: function (display) {
		if (display) {
			if (curr.circleStatus === 'Invited') {
				elements.joinCircle.hide(define.animationMenuSpeed);
				elements.leaveCircle.hide(define.animationMenuSpeed);
				nav.displayInviteCircleButtons(true);

			} else {
				nav.displayInviteCircleButtons(false);

				if (curr.circleLevel === 0 && curr.circleStatus !== 'Requesting') {
					elements.joinCircle.delay(define.animationMenuSpeed).show(
						define.animationMenuSpeed);
					elements.leaveCircle.hide(define.animationMenuSpeed);
					elements.joinCircleAccept.hide(define.animationMenuSpeed);
					elements.joinCircleReject.hide(define.animationMenuSpeed);

				}
				else {
					elements.leaveCircle.delay(define.animationMenuSpeed).show(
						define.animationMenuSpeed);
					elements.joinCircle.hide(define.animationMenuSpeed);
				}
			}
		} else {
			elements.joinCircle.hide(define.animationMenuSpeed);
			elements.leaveCircle.hide(define.animationMenuSpeed);
		}
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

		for (i = 0; i < 10; i++) {
			if (curr.circleLevel < 9 && curr.circleLevel <= i) {
				$('.level-select option[value="' + i + '"]').attr('disabled', 'disabled');
			}
		}


		elements.mainUIMembers.children('tr.entry').each(function () {

				var userId = $(this).attr('member-id');

				//
				// level
				var level = $(this).attr('member-level');
				var levelSelect = $(this).find('.level-select');
				if (level === '0') {
					levelSelect.hide();
				}
				else {
					levelSelect.show(200).val(level);
				}
				levelSelect.on('change', function () {
					actions.changeMemberLevel(userId, $(this).val());
				});

				//
				// status
				var status = $(this).attr('member-status');
				var statusSelect = $(this).find('.status-select');

				statusSelect.on('change', function () {
					actions.changeMemberStatus(userId, $(this).val());
				});
				statusSelect.append($('<option>', {
					value: status,
					text: t('circles', status)
				})).val(status);

				if (curr.circleLevel <= $(this).attr('member-level')) {
					return;
				}

				if (status === 'Member' || status === 'Invited') {
					statusSelect.append($('<option>', {
						value: 'remove_member',
						text: t('circles', 'Kick this member')
					}));
				}

				if (status === 'Requesting') {
					statusSelect.append($('<option>', {
						value: 'accept_request',
						text: t('circles', 'Accept the request')
					}));
					statusSelect.append($('<option>', {
						value: 'dismiss_request',
						text: t('circles', 'Dismiss the request')
					}));
				}

			}
		)
	},


	displayCircleDetails: function (details) {
		elements.circlesDetails.children('#name').text(details.name);
		elements.circlesDetails.children('#type').text(t('circles', details.typeLongString));

		elements.buttonCircleActions.show(300);
		elements.addMember.hide(300);
	},


	displayMembersInteraction: function (details) {
		if (details.user.level < define.levelModerator) {
			elements.buttonAddMember.hide();
		} else {
			elements.buttonAddMember.show();
		}

		if (curr.allowed_federated === '0' || details.type === 'Personal' ||
			details.user.level < define.levelAdmin) {
			elements.buttonLinkCircle.hide();
		} else {
			elements.buttonLinkCircle.show();
		}

		elements.joinCircleInteraction.hide();
		this.displayNonMemberInteraction(details);

		if (details.user.level === define.levelOwner) {
			elements.destroyCircle.show();
			elements.buttonCircleSettings.show();
			elements.buttonJoinCircle.hide();
			return;
		}

	},


	displayNonMemberInteraction: function (details) {
		elements.joinCircleAccept.hide();
		elements.joinCircleReject.hide();
		elements.joinCircleRequest.hide();
		elements.joinCircleInvite.hide();
		elements.buttonCircleSettings.hide();
		elements.destroyCircle.hide();

		if (details.user.status === 'Requesting') {
			elements.joinCircleRequest.show();
			return;
		}

		if (details.user.level > 0) {
			return;
		}

		setTimeout(function () {
			nav.joinCircleAction();
		}, 200);
	}

};

