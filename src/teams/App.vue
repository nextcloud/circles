<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import CreateTeamDialog from './components/CreateTeamDialog.vue'
import GlobalNavigation from './components/GlobalNavigation.vue'
import { useTeamsStore } from './store.ts'

const store = useTeamsStore()
const { createDialogOpen } = storeToRefs(store)

onMounted(() => store.loadTeams())
</script>

<template>
	<NcContent app-name="teams">
		<GlobalNavigation />

		<NcAppContent>
			<div class="teams-content">
				<RouterView />
			</div>
		</NcAppContent>

		<CreateTeamDialog v-if="createDialogOpen" @close="createDialogOpen = false" />
	</NcContent>
</template>

<style scoped lang="scss">
.teams-content {
	height: 100%;
	box-sizing: border-box;
	// Offset all content below the floating navigation toggle button,
	// which is pinned to the top-left of the content area.
	padding-block-start: var(--default-clickable-area);
}
</style>
