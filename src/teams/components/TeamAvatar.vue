<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import NcAvatar from '@nextcloud/vue/components/NcAvatar'

withDefaults(defineProps<{
	displayName: string
	size?: number
}>(), {
	size: 32,
})
</script>

<template>
	<!-- Teams have no user account, so render a generated initials avatar. -->
	<NcAvatar
		:displayName="displayName"
		:isNoUser="true"
		:size="size"
		hideStatus
		disableMenu
		disableTooltip />
</template>
