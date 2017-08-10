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
/** global: settings */
/** global: resultCircles */
/** global: resultMembers */
/** global: resultGroups */
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
	},


	initElementsAddMemberNavigation: function () {

		elements.addMember.hide();
		elements.addMember.on('input propertychange paste focus', function () {
			var search = $(this).val().trim();
			if (search === '') {
				elements.membersSearchResult.fadeOut(curr.animationMenuSpeed);
				return;
			}

			actions.searchMembersRequest(search);
			if (elements.membersSearchResult.children().length === 0) {
				elements.membersSearchResult.fadeOut(curr.animationMenuSpeed);
			} else {
				elements.membersSearchResult.fadeIn(curr.animationMenuSpeed);
			}
		}).blur(function () {
			setTimeout(function () {
				elements.membersSearchResult.fadeOut(curr.animationMenuSpeed);
				nav.circlesActionReturn();
			}, 100);
		});
		elements.addMember.on('keydown', function (e) {
			if (e.keyCode === 27) {
				nav.circlesActionReturn();
			}
			if (e.keyCode === 13) {

				if (curr.exactMemberSearchType === 'group') {

					OC.dialogs.confirm(
						t('circles',
							'This operation will add/invite all members of the group to the circle'),
						t('circles', 'Please confirm'),
						function (e) {
							if (e === true) {
								api.addGroupMembers(curr.circle, elements.addMember.val(),
									resultMembers.addGroupMembersResult);
							}
						});
				} else {
					if (actions.validateEmail(elements.addMember.val())) {
						api.addEmail(curr.circle, elements.addMember.val(),
							resultMembers.addEmailResult);
					} else {
						api.addMember(curr.circle, elements.addMember.val(),
							resultMembers.addMemberResult);
					}
				}
			}
		});


		elements.linkGroup.on('input propertychange paste focus', function () {
			var search = $(this).val().trim();
			if (search === '') {
				elements.groupsSearchResult.fadeOut(curr.animationMenuSpeed);
				return;
			}

			actions.searchGroupsRequest(search);
			if (elements.groupsSearchResult.children().length === 0) {
				elements.groupsSearchResult.fadeOut(curr.animationMenuSpeed);
			} else {
				elements.groupsSearchResult.fadeIn(curr.animationMenuSpeed);
			}
		}).blur(function () {
			setTimeout(function () {
				elements.groupsSearchResult.fadeOut(curr.animationMenuSpeed);
				nav.circlesActionReturn();
			}, 100);
		});
		elements.linkGroup.on('keydown', function (e) {
			if (e.keyCode === 27) {
				nav.circlesActionReturn();
			}
			if (e.keyCode === 13) {
				api.linkGroup(curr.circle, $(this).val(), resultGroups.linkGroupResult);
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

			api.linkCircle(curr.circle, elements.linkCircle.val().trim(),
				resultLinks.linkCircleResult);
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
			OC.dialogs.confirm(
				t('circles', 'Are you sure you want to leave this circle?'),
				t('circles', 'Please confirm'),
				function (e) {
					if (e === true) {
						api.leaveCircle(curr.circle, resultCircles.leaveCircleResult);
						nav.circlesActionReturn();
					}
				});
		});

		elements.destroyCircle.on('click', function () {
			OC.dialogs.confirm(
				t('circles', 'Are you sure you want to delete this circle?'),
				t('circles', 'This action is irreversible'),
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
		api.listCircles(type, '', curr.searchFilter, resultCircles.listCirclesResult);
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
		settings.displaySettings(false);
		nav.displayAddMemberInput(false);
		nav.displayLinkGroupInput(false);
		nav.displayLinkCircleInput(false);
		nav.displayJoinCircleButton(false);
		nav.displayInviteCircleButtons(false);
	},

	joinCircleAction: function () {
		nav.displayCircleButtons(false);
		nav.displayAddMemberInput(false);
		nav.displayLinkCircleInput(false);
		nav.displayLinkGroupInput(false);
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

	displayLinkGroupInput: function (display) {
		if (display) {
			elements.linkGroup.val('');
			elements.linkGroup.delay(define.animationMenuSpeed).show(define.animationMenuSpeed,
				function () {
					$(this).focus();
				});
		} else {
			elements.linkGroup.hide(define.animationMenuSpeed);
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
		if (members === '') {
			members = curr.circleMembers;
		} else {
			curr.circleMembers = members;
		}

		elements.mainUIMembersTable.emptyTable();
		if (members === null) {
			elements.mainUIMembersTable.hide(200);
			return;
		}

		elements.mainUIMembersTable.show(200);
		for (var i = 0; i < members.length; i++) {
			var tmpl = elements.generateTmplMember(members[i]);
			elements.mainUIMembersTable.append(tmpl);
		}

		for (i = 0; i < 10; i++) {
			if (curr.circleLevel < 9 && curr.circleLevel <= i
				|| curr.circleDetails.type === define.typePersonal) {
				$('.level-select option[value="' + i + '"]').attr('disabled', 'disabled');
			}
		}


		elements.mainUIMembersTable.children('tr.entry').each(function () {

				var userId = $(this).attr('member-id');
				if (userId === curr.userId) {
					$(this).find('td.username').css('font-weight', 'bold').css('font-style', 'italic');
					$(this).css('background', '#e0e0e0');
				} else {
					$(this).css('background', '#fff');
				}

				//
				// level
				if (curr.circleDetails.type === define.typePersonal) {
					var levelString = $(this).attr('member-levelString');
					$(this).find('.level').text(levelString);
				} else {
					var level = Number($(this).attr('member-level'));
					var levelSelect = $(this).find('.level-select');
					if (level === 0) {
						levelSelect.hide();
					}
					else {
						levelSelect.show(200).val(level);
					}
					levelSelect.on('change', function () {
						actions.changeMemberLevel(userId, $(this).val());
					});
				}

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
		);
	},


	displayGroups: function (groups) {
		if (groups === '') {
			groups = curr.circleGroups;
		} else {
			curr.circleGroups = groups;
		}

		if (groups === null || groups.length === 0) {
			elements.mainUIGroupsTable.hide(curr.animationSpeed);
			return;
		}

		elements.mainUIGroupsTable.emptyTable();
		elements.mainUIGroupsTable.show(200);
		for (var i = 0; i < groups.length; i++) {
			var tmpl = elements.generateTmplGroup(groups[i]);
			elements.mainUIGroupsTable.append(tmpl);
		}

		for (i = 0; i < 10; i++) {
			if (curr.circleLevel < 9 && curr.circleLevel <= i) {
				$('.level-select option[value="' + i + '"]').attr('disabled', 'disabled');
			}
			if (i > define.levelMember && curr.circleDetails.type === define.typePersonal) {
				$('.level-select option[value="' + i + '"]').remove();
			}
		}

		elements.mainUIGroupsTable.children('tr.entry').each(function () {

				var groupId = $(this).attr('group-id');
				if (curr.circleDetails.group !== null &&
					groupId === curr.circleDetails.group.group_id) {
					$(this).find('td.username').css('font-weight', 'bold').css('font-style', 'italic');
					$(this).css('background', '#e0e0e0');
				} else {
					$(this).css('background', '#fff');
				}

				var level = Number($(this).attr('group-level'));
				var levelSelect = $(this).find('.level-select');
				if (level === 0) {
					levelSelect.hide();
				}
				else {
					levelSelect.show(200).val(level);
				}
				levelSelect.append($('<option>', {
					value: 'remove_group',
					text: t('circles', 'Unlink this group')
				}));

				levelSelect.on('change', function () {
					actions.changeGroupLevel(groupId, $(this).val());
				});
			}
		);
	},


	displayLinks: function (links) {

		if (links === '') {
			links = curr.circleLinks;
		} else {
			curr.circleLinks = links;
		}

		elements.mainUILinksTable.hide(curr.animationSpeed);
		elements.mainUILinksTable.emptyTable();
		if (links === null || links.length === 0) {
			return;
		}

		elements.mainUILinksTable.show(curr.animationSpeed);
		for (var i = 0; i < links.length; i++) {
			var tmpl = elements.generateTmplLink(links[i]);
			elements.mainUILinksTable.append(tmpl);
		}


		elements.mainUILinksTable.children('tr.entry').each(function () {

			var linkId = $(this).attr('link-id');
			var status = parseInt($(this).attr('link-status'));


			var statusSelect = $(this).find('.link-status-select');

			statusSelect.on('change', function () {
				actions.changeLinkStatus(linkId, $(this).val());
			});
			statusSelect.append($('<option>', {
				value: status,
				text: define.linkStatus(status)
			})).val(status);

			if (curr.circleLevel < define.levelAdmin) {
				return;
			}

			if (status === define.linkSetup || status === define.linkRefused ||
				status === define.linkUp || status === define.linkDown) {
				statusSelect.append($('<option>', {
					value: define.linkRemove,
					text: t('circles', 'Remove this link')
				}));
			}

			if (status === define.linkRequestSent) {
				statusSelect.append($('<option>', {
					value: define.linkRemove,
					text: t('circles', 'Cancel the link request')
				}));
			}

			if (status === define.linkRequested) {
				statusSelect.append($('<option>', {
					value: define.linkUp,
					text: t('circles', 'Accept the link request')
				}));
				statusSelect.append($('<option>', {
					value: define.linkRemove,
					text: t('circles', 'Reject the link request')
				}));
			}
		});
	},


	displayCircleDetails: function (details) {
		elements.circleDetails.children('#name').text(details.name);
		elements.circleDesc.text(details.description);

		elements.circleDetails.children('#type').text(t('circles', details.type_long_string));
		if (details.description !== '') {
			elements.circleDesc.html(
				escapeHTML(details.description).replace(/\n/g, '&nbsp;<br />')).show(
				define.animationSpeed);
		}
		else {
			elements.circleDesc.text('').hide(define.animationSpeed);
		}

		elements.buttonCircleActions.show(300);
		elements.addMember.hide(300);
	},


	displayMembersInteraction: function (details) {
		if (details.viewer.level < define.levelModerator) {
			elements.buttonAddMember.hide();
		} else {
			elements.buttonAddMember.show();
		}

		nav.displayMemberInteractionCircleLinks(details);
		nav.displayMemberInteractionGroupLinks(details);

		elements.joinCircleInteraction.hide();
		elements.buttonJoinCircle.show();

		this.displayNonMemberInteraction(details);

		if (details.viewer.level === define.levelOwner) {
			elements.destroyCircle.show();
			elements.buttonCircleSettings.show();
			elements.buttonJoinCircle.hide();
		}
	},


	displayMemberInteractionGroupLinks: function (details) {
		if (curr.allowed_linked_groups === '0' ||
			details.viewer.level < define.levelAdmin
		) {
			elements.buttonLinkGroup.hide();
		}
		else {
			elements.buttonLinkGroup.show();
		}
	},


	displayMemberInteractionCircleLinks: function (details) {
		if (curr.allowed_federated_circles === '0' ||
			curr.circleSettings['allow_links'] !== 'true' ||
			details.type === define.typePersonal ||
			details.viewer.level < define.levelAdmin
		) {
			elements.buttonLinkCircle.hide();
		}
		else {
			elements.buttonLinkCircle.show();
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

		if (details.user.status === 'Invited') {
			elements.joinCircleInvite.show();
			return;
		}

		if (details.viewer.level > 0) {
			return;
		}

		setTimeout(function () {
			nav.joinCircleAction();
		}, 200);
	}

};

