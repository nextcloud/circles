<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { destroyWidget, isWidgetRegistered, renderWidget } from '@nextcloud/vue/functions/registerReference'
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { logger } from '../../logger.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

type Reference = Omit<Parameters<typeof renderWidget>[1], 'interactive'>

const props = defineProps<{
	teamId: string
	providerId: string
	resourceId: string
}>()

const resourcesStore = useTeamResourcesStore()
const resources = computed(() => resourcesStore.forTeam(props.teamId))
const resource = computed(() => resources.value.resources.find((candidate) => candidate.provider.id === props.providerId && String(candidate.id) === props.resourceId))
const resourcesLoading = computed(() => !resources.value.resourcesChecked && !resources.value.boardsChecked)
const status = ref<'loading' | 'ready' | 'unavailable' | 'error'>('loading')
const retryKey = ref(0)
const resourceUrl = ref('')
const widgetRoot = useTemplateRef<HTMLDivElement>('widgetRoot')

watch([resource, retryKey], async ([selected], _, onCleanup) => {
	status.value = 'loading'
	resourceUrl.value = ''
	if (!selected) {
		return
	}
	const controller = new AbortController()
	let mounted: { type: string, element: HTMLElement } | undefined
	onCleanup(() => {
		controller.abort()
		if (mounted) {
			try {
				destroyWidget(mounted.type, mounted.element)
			} catch (error) {
				logger.error('Could not dispose the team resource widget', { error })
			}
			mounted.element.remove()
		}
	})

	try {
		const url = new URL(selected.url, window.location.origin)
		if (url.protocol !== 'http:' && url.protocol !== 'https:') {
			throw new Error('Unsupported team resource URL')
		}
		resourceUrl.value = url.href
		const response = await axios.get<{ ocs: { data: { references: Record<string, Reference | null> } } }>(
			generateOcsUrl('references/resolve'),
			{ params: { reference: url.href }, signal: controller.signal },
		)
		if (controller.signal.aborted) {
			return
		}
		const reference = response.data.ocs.data.references[url.href]
		if (!reference?.accessible || !isWidgetRegistered(reference.richObjectType)) {
			status.value = 'unavailable'
			return
		}
		await nextTick()
		if (controller.signal.aborted || !widgetRoot.value) {
			return
		}
		// Use the same element for rendering and cleanup, independent of preview wrappers.
		const element = document.createElement('div')
		widgetRoot.value.appendChild(element)
		mounted = { type: reference.richObjectType, element }
		renderWidget(element, { ...reference, interactive: true })
		status.value = 'ready'
	} catch (error) {
		if (!controller.signal.aborted) {
			logger.error('Could not load the team resource widget', { error, teamId: props.teamId, resourceId: props.resourceId })
			status.value = 'error'
		}
	}
}, { immediate: true })
</script>

<template>
	<div class="team-resource">
		<div v-if="resourcesLoading" class="team-resource__loading">
			<NcLoadingIcon :size="44" />
		</div>
		<NcEmptyContent
			v-else-if="!resources.resourcesChecked"
			:name="t('circles', 'Team resources unavailable')"
			:description="t('circles', 'Could not load the team resources. Please try again later.')" />
		<NcEmptyContent
			v-else-if="!resource"
			:name="t('circles', 'Resource not available in this team')"
			:description="t('circles', 'This resource is not shared with the current team.')" />
		<NcEmptyContent
			v-else-if="status === 'unavailable'"
			:name="t('circles', 'Embedded view unavailable')"
			:description="t('circles', 'This resource does not provide an accessible widget.')">
			<template #action>
				<NcButton :href="resourceUrl" target="_blank" rel="noopener noreferrer">
					{{ t('circles', 'Open in app') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<NcEmptyContent
			v-else-if="status === 'error'"
			:name="t('circles', 'Resource unavailable')"
			:description="t('circles', 'Could not load the resource. Please try again.')">
			<template #action>
				<NcButton @click="retryKey++">
					{{ t('circles', 'Retry') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<div v-else-if="status === 'loading'" class="team-resource__loading">
			<NcLoadingIcon :size="44" />
		</div>
		<div v-show="resource && status === 'ready'" ref="widgetRoot" class="team-resource__widget" />
	</div>
</template>

<style scoped lang="scss">
.team-resource {
	height: 100%;
	min-height: 0;
	overflow: auto;

	&__widget {
		width: 100%;
		min-height: 100%;
	}

	&__loading {
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
	}
}
</style>
