<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { TeamDashboardItems, TeamDashboardWidget } from '../types.ts'

import { mdiCogOutline, mdiRefresh } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import TeamDashboardSettingsDialog from '../components/TeamDashboardSettingsDialog.vue'
import TeamDashboardWidgetComponent from '../components/TeamDashboardWidget.vue'
import { fetchTeamDashboardItems, fetchTeamDashboardWidgets } from '../api.ts'
import { firstTabRoute, firstTabSettled, isDashboardVisible } from '../firstTab.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const props = defineProps<{ teamId: string }>()
const store = useTeamResourcesStore()
const router = useRouter()
const resources = computed(() => store.forTeam(props.teamId))
const catalog = ref<TeamDashboardWidget[]>([])
const items = ref<Record<string, TeamDashboardItems>>({})
const missing = ref(new Set<string>())
const loading = ref(true)
const syncing = ref(false)
const error = ref(false)
const customizing = ref(false)
const saving = ref(false)
const selected = ref<TeamDashboardWidget[]>([])
const visible = computed(() => selected.value.filter((widget) => !missing.value.has(widget.id)))
let controller: AbortController | undefined
let refreshing = false
let disposed = false
const nextRefresh = new Map<string, number>()

/**
 * Remove protected content on access loss and handle Dashboard disappearing.
 *
 * @param reason - Failed request
 */
function handleError(reason: unknown) {
	const status = (reason as { response?: { status?: number } })?.response?.status
	if (status === 401 || status === 403 || status === 404) {
		catalog.value = []
		items.value = {}
	}
	if (status === 404) {
		store.markDashboardUnavailable(props.teamId)
	} else {
		error.value = true
	}
}

/**
 * Refresh a batch and hide widgets the API no longer considers applicable.
 *
 * @param widgets - Widgets due for loading
 * @param signal - Current team request signal
 */
async function loadItems(widgets: TeamDashboardWidget[], signal: AbortSignal) {
	if (widgets.length === 0) {
		return
	}
	const data = await fetchTeamDashboardItems(props.teamId, widgets.map((widget) => widget.id), signal)
	if (signal.aborted) {
		return
	}
	items.value = { ...items.value, ...data }
	for (const widget of widgets) {
		if (!Object.hasOwn(data, widget.id)) {
			missing.value.add(widget.id)
		}
		nextRefresh.set(widget.id, Date.now() + (widget.reload_interval ?? 0) * 1000)
	}
}

/**
 * Revalidate the shared layout and catalog before fetching the selection.
 *
 * @param background - Keep the current content visible until the refresh completes
 */
async function sync(background: boolean = false) {
	if (disposed) {
		return
	}
	controller?.abort()
	controller = new AbortController()
	const { signal } = controller
	const teamId = props.teamId
	syncing.value = true
	if (!background) {
		loading.value = true
		error.value = false
		items.value = {}
		catalog.value = []
		selected.value = []
		missing.value = new Set()
		nextRefresh.clear()
	}
	try {
		await store.ensureDashboard(teamId, true)
		if (signal.aborted) {
			return
		}
		if (resources.value.dashboardError) {
			throw new Error('Dashboard layout unavailable')
		}
		if (!isDashboardVisible(resources.value)) {
			return
		}
		const widgets = await fetchTeamDashboardWidgets(teamId, signal)
		if (signal.aborted) {
			return
		}
		const widgetIds = resources.value.dashboard?.widgetIds ?? null
		const selection = widgets.filter((widget) => widgetIds === null || widgetIds.includes(widget.id))
			.sort((left, right) => left.order - right.order || left.id.localeCompare(right.id))
		const data = selection.length ? await fetchTeamDashboardItems(teamId, selection.map((widget) => widget.id), signal) : {}
		if (signal.aborted) {
			return
		}
		catalog.value = widgets
		selected.value = selection
		items.value = data
		missing.value = new Set(selection.filter((widget) => !Object.hasOwn(data, widget.id)).map((widget) => widget.id))
		nextRefresh.clear()
		for (const widget of selection) {
			nextRefresh.set(widget.id, Date.now() + (widget.reload_interval ?? 0) * 1000)
		}
		error.value = false
	} catch (reason) {
		if (!signal.aborted) {
			handleError(reason)
		}
	} finally {
		if (!signal.aborted) {
			loading.value = false
			syncing.value = false
		}
	}
}

/**
 * Refresh due items without reloading the layout or catalog.
 */
async function refreshItems() {
	if (document.hidden || syncing.value || loading.value || refreshing || error.value || !controller || controller.signal.aborted) {
		return
	}
	const { signal } = controller
	const due = visible.value.filter((widget) => (widget.reload_interval ?? 0) > 0 && (nextRefresh.get(widget.id) ?? 0) <= Date.now())
	refreshing = true
	try {
		await loadItems(due, signal)
	} catch (reason) {
		if (!signal.aborted) {
			handleError(reason)
		}
	} finally {
		refreshing = false
	}
}

/**
 * Save the shared selection and resynchronize the current team.
 *
 * @param widgetIds - Explicit selection, or null for automatic defaults
 */
async function saveLayout(widgetIds: string[] | null): Promise<boolean> {
	const teamId = props.teamId
	saving.value = true
	try {
		await store.setDashboardLayout(teamId, widgetIds)
		if (teamId === props.teamId) {
			await sync()
		}
		return true
	} catch {
		return false
	} finally {
		saving.value = false
	}
}

/**
 * Revalidate metadata when returning to the page.
 */
function onVisibilityChange() {
	if (!document.hidden) {
		void sync(true)
	}
}

watch(() => props.teamId, () => {
	customizing.value = false
	void sync()
}, { immediate: true })
watch(() => !loading.value && !syncing.value && firstTabSettled(resources.value) && !resources.value.dashboardError && !isDashboardVisible(resources.value), (redirect) => {
	if (redirect) {
		controller?.abort()
		void router.replace(firstTabRoute(props.teamId, resources.value))
	}
})
const timer = window.setInterval(() => void refreshItems(), 1000)
document.addEventListener('visibilitychange', onVisibilityChange)
onBeforeUnmount(() => {
	disposed = true
	controller?.abort()
	window.clearInterval(timer)
	document.removeEventListener('visibilitychange', onVisibilityChange)
})
</script>

<template>
	<div class="team-overview">
		<header class="team-overview__header">
			<h1>{{ t('circles', 'Overview') }}</h1>
			<NcButton v-if="resources.dashboard?.canEdit && isDashboardVisible(resources)" :disabled="loading || error" @click="customizing = true">
				<template #icon>
					<NcIconSvgWrapper :path="mdiCogOutline" />
				</template>
				{{ t('circles', 'Customize dashboards') }}
			</NcButton>
		</header>
		<NcLoadingIcon v-if="loading" :size="44" />
		<NcEmptyContent v-else-if="error" :name="t('circles', 'Could not load dashboards')">
			<template #action>
				<NcButton @click="sync()">
					<template #icon>
						<NcIconSvgWrapper :path="mdiRefresh" />
					</template>
					{{ t('circles', 'Try again') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<NcEmptyContent v-else-if="!visible.length" :name="resources.dashboard?.widgetIds?.length === 0 ? t('circles', 'No dashboards selected') : t('circles', 'No dashboards available')" />
		<div v-else class="team-overview__widgets">
			<TeamDashboardWidgetComponent
				v-for="widget in visible"
				:key="widget.id"
				:widget="widget"
				:items="items[widget.id]" />
		</div>
		<TeamDashboardSettingsDialog
			v-if="customizing"
			:catalog="catalog"
			:widgetIds="resources.dashboard?.widgetIds ?? null"
			:saving="saving"
			:saveLayout="saveLayout"
			@close="customizing = false" />
	</div>
</template>

<style scoped>
.team-overview { padding: 48px 24px 24px; }
.team-overview__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 24px; }
.team-overview__widgets { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 24px; }
</style>
