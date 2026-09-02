<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcFormBox>
		<NcFormBoxButton
			v-if="!invitationUrl"
			:label="t('circles', 'Create link')"
			@click="createInvitationLink()">
			<template #icon>
				<LinkPlus :size="20" />
			</template>
		</NcFormBoxButton>
		<template v-else>
			<NcFormBoxCopyButton
				:label="t('circles', 'Copy link')"
				:value="invitationUrl" />

			<NcFormBoxButton
				:label="t('circles', 'Reset link')"
				@click="confirm(
					t('circles', 'This action will make it impossible to join the team using the current link. Do we really want to change the link?'),
					() => createInvitationLink(),
				)">
				<template #icon>
					<Autorenew :size="20" />
				</template>
			</NcFormBoxButton>

			<NcFormBoxButton
				:label="t('circles', 'Reject link')"
				@click="confirm(
					t('circles', 'This action will make it impossible to join the team using the current link. Do we really want to delete the link?'),
					() => revokeInvitationLink(),
				)">
				<template #icon>
					<LinkOff :size="20" />
				</template>
			</NcFormBoxButton>
		</template>
	</NcFormBox>
</template>

<script>
import { generateUrl, getBaseUrl } from '@nextcloud/router'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcFormBoxCopyButton from '@nextcloud/vue/components/NcFormBoxCopyButton'
import Autorenew from 'vue-material-design-icons/Autorenew.vue'
import LinkOff from 'vue-material-design-icons/LinkOff.vue'
import LinkPlus from 'vue-material-design-icons/LinkPlus.vue'
import CopyToClipboardMixin from '../../../mixins/CopyToClipboardMixin.js'
import Circle from '../../../models/circle.ts'

export default {
	name: 'CircleConfigInvitationLink',
	components: {
		Autorenew,
		LinkOff,
		LinkPlus,
		NcFormBox,
		NcFormBoxButton,
		NcFormBoxCopyButton,
	},

	mixins: [CopyToClipboardMixin],
	props: {
		circle: {
			type: Circle,
			required: true,
		},
	},

	computed: {
		invitationUrl() {
			if (!this.circle.invitationCode) {
				return null
			}

			return getBaseUrl() + generateUrl(
				'apps/circles/teams/join/{invitationCode}',
				{ invitationCode: this.circle.invitationCode.match(/.{1,4}/g).join('-') },
			)
		},
	},

	methods: {
		async createInvitationLink() {
			await this.$store.dispatch('createInvitationLink', { circleId: this.circle.id })
			await this.copyToClipboard(this.invitationUrl)
		},

		async revokeInvitationLink() {
			await this.$store.dispatch('revokeInvitationLink', { circleId: this.circle.id })
		},

		confirm(message, action) {
			if (window.confirm(message)) {
				action()
			}
		},
	},
}
</script>
