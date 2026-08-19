<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Event } from '@nextcloud/event-bus'

import axios from '@nextcloud/axios'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'

const props = withDefaults(defineProps<{
	displayName: string
	circleId: string
	size?: number
}>(), {
	size: 32,
})

const avatarUrl = ref<string>()
let avatarRequest: AbortController | undefined

/** Load the team's avatar image for NcAvatar. */
async function loadAvatarUrl() {
	avatarRequest?.abort()
	const request = new AbortController()
	avatarRequest = request
	try {
		const response = await axios.get(
			generateOcsUrl(`/apps/circles/circles/${props.circleId}/avatar`) + `?v=${Date.now()}`,
			{ responseType: 'blob', signal: request.signal },
		)
		if (avatarUrl.value !== undefined) {
			URL.revokeObjectURL(avatarUrl.value)
		}
		avatarUrl.value = URL.createObjectURL(response.data)
	} catch {
		if (request.signal.aborted) {
			return
		}
		avatarUrl.value = undefined
	}
}

/**
 * Reload this avatar when its circle image changes.
 *
 * @param circleId - The circle whose avatar changed
 */
function onAvatarUpdated(circleId: Event) {
	if (typeof circleId === 'string' && circleId === props.circleId) {
		loadAvatarUrl()
	}
}

watch(() => props.circleId, () => {
	loadAvatarUrl()
})

onMounted(() => {
	loadAvatarUrl()
	subscribe('circles:avatar:updated', onAvatarUpdated)
})
onBeforeUnmount(() => {
	unsubscribe('circles:avatar:updated', onAvatarUpdated)
	avatarRequest?.abort()
	if (avatarUrl.value !== undefined) {
		URL.revokeObjectURL(avatarUrl.value)
	}
})
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
