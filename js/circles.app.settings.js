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
/** global: nav */

var settings = {

	displaySettings: function (display) {
		if (display) {
			settings.initUISettings();
			elements.circleDesc.hide(define.animationSpeed);
			elements.mainUIMembers.hide(define.animationSpeed);
			elements.settingsPanel.delay(define.animationSpeed).show(define.animationSpeed);
		} else {
			elements.settingsPanel.hide(define.animationSpeed);
			elements.mainUIMembers.delay(define.animationSpeed).show(define.animationSpeed);
		}
	},

	initUISettings: function () {
		elements.settingsName.val(curr.circleName);
		elements.settingsDesc.val(curr.circleDesc);
		elements.settingsLink.prop('checked', (curr.circleSettings['allow_links'] === 'true'));
		elements.settingsLinkAuto.prop('checked',
			(curr.circleSettings['allow_links_auto'] === 'true'));
		elements.settingsLinkFiles.prop('checked',
			(curr.circleSettings['allow_links_files'] === 'true'));
		elements.settingsEnableAudit.prop('checked',
		    (curr.circleSettings['enable_audit'] === 'true'));

		elements.settingsLink.on('change', function () {
			settings.interactUISettings();
		});

		settings.interactUISettings();
	},


	interactUISettings: function () {

		if (curr.allowed_federated_circles !== '1' ||
			curr.circleDetails.type === define.typePersonal) {
			settings.enableSetting(elements.settingsEntryLink, elements.settingsLink, false);
			settings.enableSetting(elements.settingsEntryLinkAuto, elements.settingsLinkAuto,
				false);
			settings.enableSetting(elements.settingsEntryLinkFiles, elements.settingsLinkFiles,
				false);
			return;
		}
		settings.enableSetting(elements.settingsEntryLink, elements.settingsLink, true);
		settings.enableSetting(elements.settingsEntryLinkAuto, elements.settingsLinkAuto,
			(elements.settingsLink.is(":checked")));
		settings.enableSetting(elements.settingsEntryLinkFiles, elements.settingsLinkFiles,
			(elements.settingsLink.is(":checked")));
		settings.enableSetting(elements.settingsEntryEnableAudit, elements.settingsEnableAudit,
		    (elements.settingsEnableAudit.is(":checked")));
		
	},

	enableSetting: function (entry, input, enable) {
		entry.stop().fadeTo(curr.animationSpeed, (enable) ? 1 : 0.3);
		input.prop('disabled', !enable);
	},

	saveSettingsResult: function (result) {
		if (result.status < 1) {
			OCA.notification.onFail(
				t('circles', 'Issue while saving settings') + ': ' +
				((result.error) ? result.error : t('circles', 'no error message')));
			return;
		}

		nav.circlesActionReturn();
		curr.defineCircle(result);
		nav.displayCircleDetails(result.details);
		nav.displayMembersInteraction(result.details);
		OCA.notification.onSuccess(t('circles', "Settings saved."));
	}
};