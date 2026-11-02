<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script lang="ts">
import type { Event } from '@nextcloud/event-bus'

import { subscribe } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { reactive } from 'vue'

// Caching is left to the browser: the endpoint serves the picture with 24h
// HTTP cache headers. A per-circle version only busts that cache after an
// avatar update, for every consumer mounted before or after the event.
const avatarVersions = reactive(new Map<string, number>())

subscribe('circles:avatar:updated', (circleId: Event) => {
	if (typeof circleId === 'string') {
		avatarVersions.set(circleId, Date.now())
	}
})

/**
 * URL of a circle's avatar picture. Answers 404 when no picture was
 * uploaded; NcAvatar falls back to initials on its own.
 *
 * @param circleId - The circle the avatar belongs to
 */
export function getAvatarUrl(circleId: string): string {
	const version = avatarVersions.get(circleId)
	return generateOcsUrl(`/apps/circles/circles/${circleId}/avatar`)
		+ (version === undefined ? '' : `?v=${version}`)
}
</script>

<script setup lang="ts">
import { mdiStar } from '@mdi/js'
import { computed } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'

const props = withDefaults(defineProps<{
	displayName: string
	circleId: string
	size?: number
	isFavorite?: boolean
}>(), {
	size: 32,
	isFavorite: false,
})

const avatarUrl = computed(() => getAvatarUrl(props.circleId))
</script>

<template>
	<span class="team-avatar">
		<NcAvatar
			:displayName="displayName"
			:url="avatarUrl"
			:isNoUser="true"
			:size="size"
			hideStatus
			disableMenu
			disableTooltip />
		<svg
			v-if="isFavorite"
			class="team-avatar__favorite"
			viewBox="0 0 24 24"
			aria-hidden="true">
			<path :d="mdiStar" />
		</svg>
	</span>
</template>

<style lang="scss" scoped>
.team-avatar {
	position: relative;
	display: inline-flex;
	flex: 0 0 auto;

	&__favorite {
		position: absolute;
		right: -2px;
		bottom: -2px;
		width: 12px;
		height: 12px;
		padding: 1px;
		border-radius: 50%;
		background-color: var(--color-main-background);
		fill: var(--color-element-warning, #c88800);
		pointer-events: none;
	}
}
</style>
