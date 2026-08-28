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
import { computed } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'

const props = withDefaults(defineProps<{
	displayName: string
	circleId: string
	size?: number
}>(), {
	size: 32,
})

const avatarUrl = computed(() => getAvatarUrl(props.circleId))
</script>

<template>
	<NcAvatar
		:displayName="displayName"
		:url="avatarUrl"
		:isNoUser="true"
		:size="size"
		hideStatus
		disableMenu
		disableTooltip />
</template>
