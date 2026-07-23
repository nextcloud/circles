<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import GlobalNavigation from './components/GlobalNavigation.vue'
import TeamCreationWizard from './components/TeamCreationWizard.vue'
import { useTeamsStore } from './store.ts'

const store = useTeamsStore()
const { createWizardOpen } = storeToRefs(store)

onMounted(() => store.loadTeams())
</script>

<template>
	<NcContent appName="teams">
		<GlobalNavigation />

		<NcAppContent>
			<div :class="$style.teamsContent">
				<RouterView />
			</div>
		</NcAppContent>

		<TeamCreationWizard v-if="createWizardOpen" @close="createWizardOpen = false" />
	</NcContent>
</template>

<style module lang="scss">
.teams-content {
	height: 100%;
	box-sizing: border-box;
	// Offset all content below the floating navigation toggle button,
	// which is pinned to the top-left of the content area.
	padding-block-start: var(--default-clickable-area);
}
</style>
