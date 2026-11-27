<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { destroyWidget, isWidgetRegistered, renderWidget } from '@nextcloud/vue/functions/registerReference'
import { computed, useTemplateRef, watch } from 'vue'
import { useRouter } from 'vue-router'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import { firstTabRoute, firstTabSettled, isDashboardVisible } from '../firstTab.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const props = defineProps<{ teamId: string }>()
const type = 'dashboard-team'
const store = useTeamResourcesStore()
const router = useRouter()
const resources = computed(() => store.forTeam(props.teamId))
const host = useTemplateRef<HTMLDivElement>('host')
const registered = isWidgetRegistered(type)

watch(() => props.teamId, (teamId) => {
	if (!registered || store.forTeam(teamId).dashboard?.available === false) {
		void store.ensureDashboard(teamId, true)
	}
}, { immediate: true })

watch(host, (el, _, onCleanup) => {
	if (!el) {
		return
	}

	const { teamId } = props
	renderWidget(el, {
		richObjectType: type,
		richObject: {
			teamId,
			loadLayout: async () => {
				await store.ensureDashboard(teamId, true)
				const { dashboard, dashboardError } = store.forTeam(teamId)
				if (dashboardError || !dashboard) {
					throw new Error('Could not load the team dashboard layout')
				}
				return dashboard
			},
			saveLayout: (widgetIds: string[] | null) => store.setDashboardLayout(teamId, widgetIds),
			onUnavailable: () => store.markDashboardUnavailable(teamId),
		},
		openGraphObject: { id: teamId, name: '', description: null, thumb: null, link: '' },
		accessible: true,
		interactive: true,
	})
	onCleanup(() => destroyWidget(type, el))
}, { flush: 'post' })

watch(() => {
	const state = resources.value
	return !state.dashboardLoading && firstTabSettled(state) && !state.dashboardError && !isDashboardVisible(state)
		? props.teamId
		: null
}, (teamId) => {
	if (teamId) {
		void router.replace(firstTabRoute(teamId, resources.value))
	}
}, { immediate: true })
</script>

<template>
	<div class="team-overview">
		<div v-if="registered && resources.dashboard?.available !== false" :key="teamId" ref="host" />
		<NcEmptyContent v-else-if="!registered && (isDashboardVisible(resources) || resources.dashboardError)" :name="t('circles', 'Could not load widgets')" />
	</div>
</template>

<style scoped>
.team-overview { padding: 48px 24px 24px; }
</style>
