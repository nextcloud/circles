<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActions :inline="3" force-name variant="tertiary">
		<NcActionButton
			v-if="!invitationUrl"
			@click="createInvitationLink()">
			<template #icon>
				<LinkPlus :size="20" />
			</template>
			{{ t('circles', 'Create link') }}
		</NcActionButton>
		<template v-else>
			<NcActionLink
				:href="invitationUrl"
				:icon="copyLinkIcon"
				@click.stop.prevent="copyToClipboard(invitationUrl)">
				{{ copyButtonText }}
			</NcActionLink>

			<NcActionButton
				@click="confirm(
					t('circles', 'This action will make it impossible to join the team using the current link. Do we really want to change the link?'),
					() => createInvitationLink(),
				)">
				<template #icon>
					<Autorenew :size="20" />
				</template>
				{{ t('circles', 'Reset link') }}
			</NcActionButton>

			<NcActionButton
				@click="confirm(
					t('circles', 'This action will make it impossible to join the team using the current link. Do we really want to delete the link?'),
					() => revokeInvitationLink(),
				)">
				<template #icon>
					<LinkOff :size="20" />
				</template>
				{{ t('circles', 'Reject link') }}
			</NcActionButton>
		</template>
	</NcActions>
</template>

<script>
import { generateUrl, getBaseUrl } from '@nextcloud/router'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionLink from '@nextcloud/vue/components/NcActionLink'
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
		NcActions,
		NcActionLink,
		NcActionButton,
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

		copyButtonText() {
			if (this.copied) {
				return this.copySuccess
					? t('circles', 'Copied')
					: t('circles', 'Could not copy')
			}
			return t('circles', 'Copy link')
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
