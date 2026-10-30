<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { TeamDashboardResult, TeamDashboardWidget as TeamDashboardWidgetInfo } from '../types.ts'

import { mdiPencilOutline, mdiRefresh, mdiViewDashboardOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useStore } from 'vuex'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import TeamDashboardSettingsDialog from '../components/TeamDashboardSettingsDialog.vue'
import TeamDashboardWidget from '../components/TeamDashboardWidget.vue'
import { fetchTeamDashboardItems, fetchTeamDashboardLayout, fetchTeamDashboardWidgets, saveTeamDashboardLayout } from '../api.ts'

const props = defineProps<{ teamId: string }>()

const REFRESH_INTERVAL = 33_000

const store = useStore()
const isMember = computed(() => Boolean(store.getters.getCircle(props.teamId)?.isMember))
const catalog = ref<TeamDashboardWidgetInfo[]>([])
const widgetIds = ref<string[] | null>(null)
const canEdit = ref(false)
const saving = ref(false)
const results = ref<Record<string, TeamDashboardResult>>({})
const pending = ref<string[]>([])
const loading = ref(false)
const hasError = ref(false)
const settingsOpen = ref(false)
let controller: AbortController | undefined
let timer: ReturnType<typeof setInterval> | undefined
let synchronizing = false

const enabledWidgets = computed(() => catalog.value.filter((widget) => widgetIds.value === null || widgetIds.value.includes(widget.id)))
const widgets = computed(() => enabledWidgets.value.filter((widget) => results.value[widget.id]?.status !== 'unavailable'))

/**
 * @param widgetId - Widget to refresh independently
 */
async function loadWidget(widgetId: string) {
	const request = controller
	if (!request || request.signal.aborted || pending.value.includes(widgetId) || !enabledWidgets.value.some((widget) => widget.id === widgetId)) {
		return
	}
	pending.value.push(widgetId)
	try {
		const result = await fetchTeamDashboardItems(props.teamId, widgetId, request.signal)
		if (!request.signal.aborted) {
			results.value[widgetId] = result
		}
	} catch (error) {
		if (request.signal.aborted) {
			return
		}
		const status = (error as { response?: { status?: number } }).response?.status
		if (status === 401 || status === 403 || status === 404) {
			request.abort()
			catalog.value = []
			canEdit.value = false
			results.value = {}
			pending.value = []
			hasError.value = true
		} else {
			results.value[widgetId] = { status: 'error' }
		}
	} finally {
		if (controller === request) {
			pending.value = pending.value.filter((id) => id !== widgetId)
		}
	}
}

/**
 * @param clear - Whether to clear the current team's visible state
 */
async function synchronize(clear: boolean = true) {
	controller?.abort()
	const request = new AbortController()
	controller = request
	synchronizing = true
	if (clear) {
		catalog.value = []
		widgetIds.value = null
		canEdit.value = false
		results.value = {}
		saving.value = false
	}
	pending.value = []
	hasError.value = false
	loading.value = false
	if (!isMember.value) {
		synchronizing = false
		return
	}
	loading.value = clear
	try {
		const metadataRequest = fetchTeamDashboardWidgets(props.teamId, request.signal)
		const layoutRequest = fetchTeamDashboardLayout(props.teamId, request.signal).then((layout) => {
			if (!request.signal.aborted) {
				widgetIds.value = layout.widgetIds
				canEdit.value = layout.canEdit
			}
			return layout
		})
		const [metadata] = await Promise.all([metadataRequest, layoutRequest])
		if (request.signal.aborted) {
			return
		}
		catalog.value = metadata.sort((left, right) => left.order - right.order || left.id.localeCompare(right.id))
		const enabledIds = enabledWidgets.value.map((widget) => widget.id)
		results.value = Object.fromEntries(Object.entries(results.value).filter(([id]) => enabledIds.includes(id)))
		await Promise.all(enabledIds.map((id) => loadWidget(id)))
	} catch {
		if (!request.signal.aborted) {
			catalog.value = []
			results.value = {}
			hasError.value = true
		}
	} finally {
		if (controller === request) {
			loading.value = false
			synchronizing = false
		}
	}
}

/**
 *
 */
async function reload() {
	await synchronize()
}

/**
 * @param selection - Shared widget IDs, or null for automatic defaults
 */
async function saveLayout(selection: string[] | null): Promise<boolean> {
	const request = controller
	if (!request || request.signal.aborted || !canEdit.value || saving.value) {
		return false
	}
	saving.value = true
	try {
		const layout = await saveTeamDashboardLayout(props.teamId, selection, request.signal)
		if (request.signal.aborted) {
			return false
		}
		widgetIds.value = layout.widgetIds
		canEdit.value = layout.canEdit
		saving.value = false
		void synchronize(false)
		return true
	} finally {
		if (controller === request) {
			saving.value = false
		}
	}
}

/**
 *
 */
function refresh() {
	if (!document.hidden && isMember.value && !synchronizing && !saving.value && !hasError.value && pending.value.length === 0) {
		void synchronize(false)
	}
}

watch([() => props.teamId, isMember], reload, { immediate: true, flush: 'sync' })
watch([() => props.teamId, canEdit], () => {
	settingsOpen.value = false
})
onMounted(() => {
	timer = setInterval(refresh, REFRESH_INTERVAL)
	document.addEventListener('visibilitychange', refresh)
})
onBeforeUnmount(() => {
	controller?.abort()
	clearInterval(timer)
	document.removeEventListener('visibilitychange', refresh)
})
</script>

<template>
	<div class="team-overview">
		<header class="team-overview__header">
			<h1>{{ t('circles', 'Overview') }}</h1>
			<NcButton v-if="canEdit" @click="settingsOpen = true">
				<template #icon>
					<NcIconSvgWrapper :path="mdiPencilOutline" />
				</template>
				{{ t('circles', 'Customize dashboards') }}
			</NcButton>
		</header>
		<TeamDashboardSettingsDialog
			v-if="settingsOpen && canEdit"
			:key="teamId"
			:catalog="catalog"
			:widgetIds="widgetIds"
			:saving="saving"
			:saveLayout="saveLayout"
			@close="settingsOpen = false" />
		<NcLoadingIcon v-if="loading" class="team-overview__loading" :size="44" />
		<NcEmptyContent v-else-if="hasError" :name="t('circles', 'Overview unavailable')">
			<template #action>
				<NcButton @click="reload">
					<template #icon>
						<NcIconSvgWrapper :path="mdiRefresh" />
					</template>
					{{ t('circles', 'Try again') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<div v-else-if="widgets.length" class="team-overview__widgets">
			<TeamDashboardWidget
				v-for="widget in widgets"
				:key="`${teamId}:${widget.id}`"
				:widget="widget"
				:result="results[widget.id]"
				:loading="pending.includes(widget.id)"
				@retry="loadWidget(widget.id)" />
		</div>
		<NcEmptyContent v-else :name="widgetIds?.length === 0 ? t('circles', 'No dashboards selected') : t('circles', 'No content yet')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiViewDashboardOutline" />
			</template>
		</NcEmptyContent>
	</div>
</template>

<style scoped lang="scss">
.team-overview {
  padding: 24px;
  height: 100%;
  overflow-y: auto;
  box-sizing: border-box;

	&__header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		flex-wrap: wrap;
		gap: 12px;
		margin-bottom: 24px;
	}

  h1 {
		display: flex;
		align-items: center;
		min-height: 44px;
		padding-inline-start: 36px;
		margin: 0;
		font-size: 24px;
		font-weight: 600;
  }

  &__widgets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
    gap: 32px;
    max-width: 1200px;
  }

  &__loading {
    margin: 32px auto;
  }
}
</style>
