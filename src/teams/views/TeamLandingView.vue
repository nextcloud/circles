<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { firstTabRoute, firstTabSettled } from '../firstTab.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const props = defineProps<{
	teamId: string
}>()

const router = useRouter()

// The team page loads the per-team resources; this view only waits for the
// tab order and pages to settle, then forwards to the first navigation entry.
const resourcesStore = useTeamResourcesStore()
const resources = computed(() => resourcesStore.forTeam(props.teamId))

watch(() => firstTabSettled(resources.value), (settled) => {
	if (settled) {
		router.replace(firstTabRoute(props.teamId, resources.value))
	}
}, { immediate: true })
</script>

<template>
	<div class="team-landing">
		<NcLoadingIcon :size="44" />
	</div>
</template>

<style lang="scss" scoped>
.team-landing {
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
}
</style>
