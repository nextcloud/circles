<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { DiscoverableTeam } from '../types.ts'

import { n, t } from '@nextcloud/l10n'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

defineProps<{
	team: DiscoverableTeam
	joining?: boolean
	disabled?: boolean
}>()

const emit = defineEmits<{ join: [] }>()
</script>

<template>
	<article :aria-label="team.displayName" :class="$style.discoverableTeamCard">
		<div :class="$style.discoverableTeamCardHead">
			<!-- Initials only: the avatar endpoint rejects non-members. -->
			<NcAvatar
				:displayName="team.displayName"
				isNoUser
				:size="44"
				hideStatus
				disableMenu
				disableTooltip />
			<span :class="$style.discoverableTeamCardName">{{ team.displayName }}</span>
		</div>

		<p v-if="team.description" :class="$style.discoverableTeamCardDescription">
			{{ team.description }}
		</p>

		<div :class="$style.discoverableTeamCardFooter">
			<span>{{ n('circles', '%n member', '%n members', team.memberCount) }}</span>
			<span v-if="team.pending">{{ t('circles', 'Request pending') }}</span>
			<NcButton
				v-else-if="team.canJoin"
				:disabled="joining || disabled"
				@click="emit('join')">
				<template v-if="joining" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ t('circles', 'Join') }}
			</NcButton>
		</div>
	</article>
</template>

<style module lang="scss">
.discoverable-team-card {
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
	min-height: 140px;
	padding: calc(3 * var(--default-grid-baseline));
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-container, 16px);
	background-color: var(--color-main-background);
	color: var(--color-main-text);

	&__head {
		display: flex;
		align-items: center;
		gap: calc(2 * var(--default-grid-baseline));
	}

	&__name {
		flex: 1 1 auto;
		min-width: 0;
		font-size: 1.1em;
		font-weight: 600;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	&__description {
		margin: 0;
		color: var(--color-text-maxcontrast);
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}

	&__footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: calc(2 * var(--default-grid-baseline));
		margin-top: auto;
	}
}
</style>
