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

/** global: curr */
/** global: define */
/** global: elements */

var settings = {

	displaySettings: function (display) {
		if (display) {
			settings.initUISettings();
			elements.mainUIMembers.hide(define.animationSpeed);
			elements.settingsPanel.delay(define.animationSpeed).show(define.animationSpeed);
		} else {
			elements.settingsPanel.hide(define.animationSpeed);
			elements.mainUIMembers.delay(define.animationSpeed).show(define.animationSpeed);
		}
	},

	initUISettings: function () {
		elements.settingsName.val(curr.circleName);

		elements.settingsLink.prop('disabled', true);
		elements.settingsLinkAuto.prop('disabled', true);
		elements.settingsLinkFiles.prop('disabled', true);
		elements.settingsEntryLink.fadeTo(0, 0.3);
		elements.settingsEntryLinkAuto.fadeTo(0, 0.3);
		elements.settingsEntryLinkFiles.fadeTo(0, 0.3);

		if (curr.allowed_federated !== '1') {
			return;
		}

		elements.settingsLink.prop('disabled', false);
//		elements.settingsLinkAuto.prop('disabled', true);
//		elements.settingsLinkFiles.prop('disabled', true);
		elements.settingsEntryLink.fadeTo(0, 1);
//		elements.settingsEntryLinkAuto.fadeTo(0, 1).fadeTo(2000, 0.3);
//		elements.settingsEntryLinkFiles.fadeTo(0, 1).fadeTo(2000, 0.3);

	},

	saveSettingsResult: function (result) {
		console.log(result);
	}
};



