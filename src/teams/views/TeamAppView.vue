<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { onBeforeUnmount, ref, watch } from 'vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { logger } from '../../logger.ts'
import { getTeamAppProvider } from '../integrations/registry.ts'
import { useTeamAppsStore } from '../integrations/store.ts'

const props = defineProps<{ teamId: string, providerId: string, entryId: string }>()
const apps = useTeamAppsStore()
const container = ref<HTMLElement | null>(null)
const loading = ref(true)
const error = ref(false)
let generation = 0
let destroy: (() => void) | undefined

watch([() => apps.providerReady(props.teamId, props.providerId), container, () => props.teamId, () => props.providerId, () => props.entryId], async ([ready, element], _, onCleanup) => {
	if (!ready || !element) {
		return
	}
	const current = ++generation
	let cancelled = false
	onCleanup(() => {
		cancelled = true
		destroy?.()
		destroy = undefined
	})
	loading.value = true
	error.value = false
	try {
		const provider = getTeamAppProvider(props.providerId)
		const context = apps.context(props.teamId, props.providerId)
		const entry = apps.entries(props.teamId).find((entry) => entry.providerId === props.providerId && entry.entryId === props.entryId)
		if (!provider?.mount || !context || !entry || apps.errors(props.teamId).includes(props.providerId)) {
			throw new Error('Teams embedded view is unavailable')
		}
		const cleanup = await provider.mount(element, { teamId: context.teamId, signal: context.signal }, { id: entry.entryId, label: entry.label, viewData: entry.viewData })
		if (cancelled || current !== generation) {
			cleanup()
		} else {
			destroy = cleanup
		}
	} catch (err) {
		if (!cancelled) {
			logger.error('Could not open Teams embedded view', { provider: props.providerId, entryId: props.entryId, err })
			error.value = true
		}
	} finally {
		if (!cancelled) {
			loading.value = false
		}
	}
}, { immediate: true, flush: 'post' })

onBeforeUnmount(() => {
	generation++
	destroy?.()
	destroy = undefined
})
</script>

<template>
	<div class="team-app-view">
		<NcLoadingIcon v-if="loading" :size="44" />
		<NcEmptyContent v-else-if="error" :name="t('circles', 'Could not open the view')" />
		<div v-show="!loading && !error" ref="container" class="team-app-view__content" />
	</div>
</template>

<style scoped>
.team-app-view {
	height: 100%;
	display: flex;
	flex-direction: column;
}

.team-app-view__content {
	flex: 1;
	min-height: 0;
	overflow: auto;
}
</style>
