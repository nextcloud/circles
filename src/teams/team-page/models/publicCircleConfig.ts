/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'
import CircleConfigCheckboxesList from '../components/CircleDetails/CircleConfigs/CircleConfigCheckboxesList.vue'
import CircleConfigInvitationLink from '../components/CircleDetails/CircleConfigs/CircleConfigInvitationLink.vue'
import { CircleConfigs } from './constants.ts'

// Available circle configs in the circle details view
export const PUBLIC_CIRCLE_CONFIG = {
	[t('circles', 'Invites')]: {
		component: CircleConfigCheckboxesList,
		props: {
			configs: {
				[CircleConfigs.OPEN]: t('circles', 'Anyone can request membership (requires "Visible to everyone")'),
				[CircleConfigs.INVITE]: t('circles', 'Members need to accept invitation'),
				[CircleConfigs.REQUEST]: t('circles', 'Memberships must be confirmed/accepted by a Moderator (requires "Anyone can request membership")'),
				[CircleConfigs.FRIEND]: t('circles', 'Members can also invite'),
			},
		},
	},

	[t('circles', 'Invitation links')]: {
		component: CircleConfigInvitationLink,
		props: {},
	},

	[t('circles', 'Membership')]: {
		component: CircleConfigCheckboxesList,
		props: {
			configs: {
				// TODO: implement backend
				// [CircleConfigs.CIRCLE_INVITE]: t('circles', 'Team must confirm when invited in another circle'),
				[CircleConfigs.ROOT]: t('circles', 'Prevent teams from being a member of another team'),
			},
		},
	},

	[t('circles', 'Federation')]: {
		component: CircleConfigCheckboxesList,
		props: {
			configs: {
				[CircleConfigs.FEDERATED]: t('circles', 'Allow federated members'),
			},
		},
	},

	[t('circles', 'Privacy')]: {
		component: CircleConfigCheckboxesList,
		props: {
			configs: {
				[CircleConfigs.VISIBLE]: t('circles', 'Visible to everyone'),
			},
		},
	},
}
