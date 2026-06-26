<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Team } from '../types.ts'

import { mdiFolderMultipleOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import TeamAvatar from './TeamAvatar.vue'

const props = defineProps<{
	team: Team
}>()

const MAX_AVATARS = 5
</script>

<template>
	<RouterLink
		:class="$style.teamCard"
		:to="{ name: 'team', params: { teamId: props.team.id } }">
		<div :class="$style.teamCardHead">
			<TeamAvatar :display-name="team.displayName" :size="44" />
			<span :class="$style.teamCardName">{{ team.displayName }}</span>
		</div>

		<p v-if="team.description" :class="$style.teamCardDescription">
			{{ team.description }}
		</p>

		<div :class="$style.teamCardFooter">
			<ul :class="$style.teamCardMembers" :aria-label="t('circles', 'Members')">
				<li
					v-for="member in team.members.slice(0, MAX_AVATARS)"
					:key="member.id"
					:class="$style.teamCardMember">
					<NcAvatar
						:user="member.isUser ? member.id : undefined"
						:display-name="member.displayName"
						:is-no-user="!member.isUser"
						:size="28"
						hide-status />
				</li>
				<li v-if="team.memberCount > team.members.length" :class="$style.teamCardMemberMore">
					+{{ team.memberCount - team.members.length }}
				</li>
			</ul>

			<span :class="$style.teamCardResources">
				<NcIconSvgWrapper :path="mdiFolderMultipleOutline" :size="18" inline />
				{{ t('circles', '{count} resources', { count: team.resources.length }) }}
			</span>
		</div>
	</RouterLink>
</template>

<style module lang="scss">
.team-card {
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
	min-height: 140px;
	padding: calc(3 * var(--default-grid-baseline));
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-container, 16px);
	background-color: var(--color-main-background);
	color: var(--color-main-text);
	text-decoration: none;

	&:hover {
		background-color: var(--color-background-hover);
		border-color: var(--color-primary-element);

		// keep the avatar rings matching the (now hovered) card background
		.team-card__member,
		.team-card__member-more {
			box-shadow: 0 0 0 2px var(--color-background-hover);
		}
	}

	&:focus-visible {
		outline: 2px solid var(--color-main-text);
		outline-offset: 2px;
	}

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
		// truncate long team names rather than wrap the header
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	&__description {
		margin: 0;
		color: var(--color-text-maxcontrast);
		// clamp to two lines so cards stay a consistent height
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
		// push the footer down so equal-height cards align their footers
		margin-top: auto;
	}

	&__members {
		display: flex;
		align-items: center;
		list-style: none;
		padding: 0;
		margin: 0;
	}

	// Each item overlaps the previous one to form a compact stack.
	&__member:not(:first-child),
	&__member-more {
		margin-inline-start: -10px;
	}

	&__member {
		position: relative;
		border-radius: 50%;
		// ring matches the card background so overlaps read as separate avatars
		box-shadow: 0 0 0 2px var(--color-main-background);

		// earlier avatars sit on top of later ones (member preview capped at 5)
		&:nth-child(1) { z-index: 6; }
		&:nth-child(2) { z-index: 5; }
		&:nth-child(3) { z-index: 4; }
		&:nth-child(4) { z-index: 3; }
		&:nth-child(5) { z-index: 2; }
	}

	&__member-more {
		position: relative;
		// sits behind the avatars, at the back of the stack
		z-index: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		min-width: 28px;
		height: 28px;
		padding-inline: 4px;
		border-radius: 14px;
		box-shadow: 0 0 0 2px var(--color-main-background);
		background-color: var(--color-background-dark);
		color: var(--color-text-maxcontrast);
		font-size: 0.8em;
		font-weight: 600;
	}

	&__resources {
		display: inline-flex;
		align-items: center;
		gap: var(--default-grid-baseline);
		color: var(--color-text-maxcontrast);
		font-size: 0.9em;
		white-space: nowrap;
	}
}
</style>
