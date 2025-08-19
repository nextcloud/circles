<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITeam } from '../types.ts'

import { useTemplateRef } from 'vue'
import TeamsListItem from './TeamsListItem.vue'

defineProps<{
	teams: ITeam[]
}>()

defineExpose({ scrollTop })

const teamsListElement = useTemplateRef('teamsList')

/**
 * Scroll the teams to the top (reset scrolling)
 */
function scrollTop() {
	if (teamsListElement.value) {
		teamsListElement.value.scrollTop = 0
	}
}
</script>

<template>
	<div ref="teamsList" class="teams-list">
		<TeamsListItem
			v-for="team of teams"
			:key="team.id"
			:team />
	</div>
</template>

<style scoped lang="scss">
.teams-list {
	display: flex;
	flex-direction: column;
	gap: 2px;
	overflow-y: auto;
	flex: 1;
	scroll-behavior: smooth;
}
</style>
