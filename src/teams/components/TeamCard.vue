<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Team } from '../types.ts'

import { mdiStar, mdiStarOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import TeamAvatar from './TeamAvatar.vue'

const props = defineProps<{
	team: Team
	draggable?: boolean
	favoriteBusy?: boolean
}>()

const emit = defineEmits<{
	toggleFavorite: [team: Team]
	dragStart: [team: Team, event: DragEvent]
	dragOver: [team: Team, event: DragEvent]
	dragEnd: [event: DragEvent]
}>()

const MAX_AVATARS = 5

/** Toggle the favorite without starting a drag. */
function toggleFavorite() {
	emit('toggleFavorite', props.team)
}

/**
 * Keep action buttons out of native card dragging.
 *
 * @param event - Native drag event
 */
function preventFavoriteDrag(event: DragEvent) {
	event.preventDefault()
}
</script>

<template>
	<article
		:class="$style.teamCard"
		:draggable="draggable"
		@dragstart="emit('dragStart', team, $event)"
		@dragover="emit('dragOver', team, $event)"
		@dragend="emit('dragEnd', $event)">
		<div :class="$style.teamCardHead">
			<TeamAvatar
				:circleId="team.id"
				:displayName="team.displayName"
				:size="40" />
			<RouterLink
				:class="$style.teamCardName"
				:to="{ name: 'team', params: { teamId: props.team.id } }">
				{{ team.displayName }}
			</RouterLink>
		</div>
		<RouterLink
			:class="$style.teamCardLink"
			:to="{ name: 'team', params: { teamId: props.team.id } }">
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
							:user="member.isUser ? member.userId ?? undefined : undefined"
							:displayName="member.displayName"
							:isNoUser="!member.isUser"
							:size="28"
							hideStatus
							disableMenu />
					</li>
					<li v-if="team.memberCount > team.members.length" :class="$style.teamCardMemberMore">
						+{{ team.memberCount - team.members.length }}
					</li>
				</ul>
			</div>
		</RouterLink>
		<div :class="$style.teamCardActions" @mousedown.stop @dragstart.prevent.stop>
			<button
				type="button"
				draggable="false"
				:disabled="favoriteBusy"
				:aria-pressed="team.isFavorite"
				:class="$style.teamCardFavorite"
				:title="team.isFavorite ? t('circles', 'Remove from favorites') : t('circles', 'Add to favorites')"
				:aria-label="team.isFavorite ? t('circles', 'Remove from favorites') : t('circles', 'Add to favorites')"
				@click="toggleFavorite"
				@mousedown.stop
				@dragstart="preventFavoriteDrag">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path :d="team.isFavorite ? mdiStar : mdiStarOutline" />
				</svg>
			</button>
		</div>
	</article>
</template>

<style module lang="scss">
.team-card {
	position: relative;
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

	&[draggable='true'] {
		cursor: grab;
	}

	&__head {
		display: flex;
		align-items: center;
		gap: var(--default-grid-baseline);
	}

	&__link {
		display: flex;
		flex: 1;
		flex-direction: column;
		gap: calc(2 * var(--default-grid-baseline));
		min-width: 0;
		color: inherit;
		text-decoration: none;
		cursor: pointer;

		* {
			cursor: pointer !important;
		}
	}

	&__actions {
		position: absolute;
		right: calc(2 * var(--default-grid-baseline));
		bottom: calc(3 * var(--default-grid-baseline) + 14px - var(--default-clickable-area) / 2);
		display: flex;
		align-items: center;
	}

	&__favorite {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-width: 0 !important;
		min-height: 0 !important;
		width: var(--default-clickable-area) !important;
		height: var(--default-clickable-area) !important;
		padding: 1px !important;
		margin: 0 !important;
		border: 0 !important;
		border-radius: 50%;
		appearance: none;
		box-shadow: none !important;
		background: transparent !important;
		color: var(--color-element-warning, #c88800) !important;

		&,
		* {
			cursor: pointer !important;
		}

		svg {
			width: 16px;
			height: 16px;
			fill: var(--color-element-warning, #c88800) !important;
		}

		&:focus-visible {
			outline: 2px solid var(--color-element-warning, #c88800);
			outline-offset: 2px;
		}

		&:disabled,
		&:disabled * {
			cursor: default !important;
		}
	}

	&__name {
		flex: 1 1 auto;
		min-width: 0;
		font-size: 1.1em;
		font-weight: 600;
		color: inherit;
		text-decoration: none;
		cursor: pointer;
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
		padding-inline-end: calc(2 * var(--default-clickable-area));
	}

	&__members {
		display: flex;
		align-items: center;
		list-style: none;
		padding: 0;
		margin: 0;
		min-width: 0;
		flex-wrap: wrap;
	}

	// Each item overlaps the previous one to form a compact stack.
	&__member:not(:first-child),
	&__member-more {
		margin-inline-start: -10px;
	}

	&__member {
		position: relative;
		// collapse the list item to the avatar's box, otherwise line-height
		// makes it taller than wide and the ring below turns into an ellipse
		display: flex;
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
		cursor: pointer;
		background-color: var(--color-background-dark);
		color: var(--color-text-maxcontrast);
		font-size: 0.8em;
		font-weight: 600;
	}
}
</style>
