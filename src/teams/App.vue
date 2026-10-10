<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { onMounted, provide, ref } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import TeamCreationWizard from './components/TeamCreationWizard.vue'
import TeamNavigation from './components/TeamNavigation.vue'
import { SIDEBAR_HOST_KEY } from './services/teamFilesView.ts'
import { useTeamsStore } from './store.ts'

const store = useTeamsStore()
const { createWizardOpen } = storeToRefs(store)

const sidebarHost = ref<HTMLElement | null>(null)
provide(SIDEBAR_HOST_KEY, sidebarHost)

onMounted(() => store.loadTeams())
</script>

<template>
	<NcContent appName="teams">
		<TeamNavigation />

		<NcAppContent>
			<div :class="$style.teamsContent">
				<RouterView />
			</div>
		</NcAppContent>

		<!-- The Files sidebar of the embedded file list is rendered here, next to the app content -->
		<div ref="sidebarHost" :class="$style.sidebarHost" />

		<TeamCreationWizard v-if="createWizardOpen" @close="createWizardOpen = false" />
	</NcContent>
</template>

<style module lang="scss">
.teams-content {
	height: 100%;
	box-sizing: border-box;
}

.sidebar-host {
	display: contents;
}
</style>
