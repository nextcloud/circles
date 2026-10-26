<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	/** Emoji or SVG path data for a 24x24 view box, starting with an M/m move command. */
	icon?: string
	svg?: string
	url?: string
}>()

const icon = computed(() => props.icon?.trim() ?? '')
const isPath = computed(() => /^[Mm]\s*[-+.\d]/.test(icon.value))
</script>

<template>
	<NcIconSvgWrapper
		v-if="isPath"
		:path="icon"
		:size="20"
		aria-hidden="true" />
	<span v-else-if="icon" class="team-app-icon__emoji" aria-hidden="true">{{ icon }}</span>
	<NcIconSvgWrapper
		v-else-if="svg"
		:svg="svg"
		:size="20"
		aria-hidden="true" />
	<img
		v-else-if="url"
		:src="url"
		width="20"
		height="20"
		alt="">
</template>

<style scoped>
.team-app-icon__emoji {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 20px;
	height: 20px;
	flex-shrink: 0;
	font-size: 20px;
	line-height: 1;
}
</style>
