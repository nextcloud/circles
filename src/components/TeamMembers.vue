<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITeamMember } from '../types.ts'

import NcAvatar from '@nextcloud/vue/components/NcAvatar'

defineProps<{
	members: ITeamMember[]
}>()
</script>

<template>
	<div class="team-members">
		<ul class="team-members__list">
			<li v-for="member in members.slice(0, 5)" :key="member.userId || member.singleId" class="team-members__item">
				<NcAvatar
					:user="member.isUser ? member.userId : undefined"
					:display-name="member.displayName"
					:is-no-user="!member.isUser"
					:size="36"
					class="team-members__avatar" />
				<span v-if="members.length > 5" class="team-members__more">
					+{{ members.length - 5 }}
				</span>
			</li>
		</ul>
	</div>
</template>

<style scoped lang="scss">
.team-members {
	--member-list-gap: calc(1.5 * var(--default-grid-baseline));

	&__list {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: var(--member-list-gap);
		list-style: none;
	}

	&__avatar {
		transition: transform var(--animation-quick) ease;

		&:hover {
			transform: scale(1.1);
		}
	}

	&__more {
		background-color: var(--color-background-hover);
		border-radius: var(--border-radius-element);
		color: var(--color-text-maxcontrast);
		font-size: var(--font-size-small);
		padding: 2px 6px;
		margin-inline-start: 4px;
	}
}

/* Responsive adjustments */
@media (max-width: 480px) {
	.team-members {
		--members-list-gap: var(--default-grid-baseline);
	}
}
</style>
