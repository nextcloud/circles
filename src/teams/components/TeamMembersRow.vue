<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type Member from '../team-page/models/member.ts'

import NcAvatar from '@nextcloud/vue/components/NcAvatar'

defineProps<{
	/** The members to show as avatars (already capped to the available width). */
	members: Member[]
	/** How many further members are collapsed into the "+n" label. */
	hiddenCount: number
}>()
</script>

<template>
	<span class="team-members-row">
		<NcAvatar
			v-for="member in members"
			:key="member.singleId"
			:user="member.isUser ? member.userId : undefined"
			:displayName="member.displayName"
			:isNoUser="!member.isUser"
			:size="24"
			hideStatus
			disableMenu />
		<span v-if="hiddenCount > 0" class="team-members-row__more">
			+{{ hiddenCount }}
		</span>
	</span>
</template>

<style lang="scss" scoped>
.team-members-row {
	display: flex;
	align-items: center;
	justify-content: flex-start;
	gap: var(--default-grid-baseline);
	min-width: 0;
	overflow: hidden;
	width: 100%;

	&__more {
		flex: 0 0 auto;
		color: var(--color-text-maxcontrast);
		font-size: 0.9em;
		// The row may sit inside a button, which the server styles bold
		font-weight: normal;
	}
}
</style>
