<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAlertCircleOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { logger } from '../../logger.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const props = defineProps<{ teamId: string, fileId: string }>()
const resources = useTeamResourcesStore()
const editorContainer = ref<HTMLElement | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
let editor: { destroy(): void } | undefined
let unmounted = false

/** Wait for the initial Pages load owned by the team route. */
function waitForPages(): Promise<void> {
	const settled = () => {
		const slot = resources.forTeam(props.teamId)
		return slot.pagesChecked || slot.pagesError
	}
	if (settled()) {
		return Promise.resolve()
	}
	return new Promise((resolve) => {
		const stop = watch(settled, (done) => {
			if (done) {
				stop()
				resolve()
			}
		})
	})
}

/** Resolve the page within the team folder and mount Text's inline editor. */
async function loadPage(): Promise<void> {
	try {
		const text = window.OCA.Text
		if (!text) {
			error.value = t('circles', 'The Text app is required to show team pages')
			return
		}
		await waitForPages()
		let slot = resources.forTeam(props.teamId)
		if (slot.pagesError || slot.folderError || !slot.folder) {
			throw new Error('The team pages are not available')
		}
		let page = slot.pages.find((page) => String(page.fileId) === props.fileId)
		if (!page) {
			await resources.ensurePages(props.teamId, true)
			slot = resources.forTeam(props.teamId)
			if (slot.pagesError) {
				throw new Error('Could not refresh the team pages')
			}
			page = slot.pages.find((page) => String(page.fileId) === props.fileId)
		}
		if (unmounted) {
			return
		}
		if (!page) {
			error.value = t('circles', 'This page no longer exists')
			return
		}
		if (!editorContainer.value) {
			throw new Error('The page editor container is missing')
		}
		const created = await text.createCollaborativeEditor({
			el: editorContainer.value,
			context: { type: 'file', id: page.fileId },
			filePath: page.filePath,
		})
		if (unmounted) {
			created.destroy()
			return
		}
		editor = created
	} catch (err) {
		logger.error('Could not open the team page', { err, teamId: props.teamId, fileId: props.fileId })
		error.value = t('circles', 'Could not open the page')
	} finally {
		loading.value = false
	}
}

onMounted(loadPage)
onBeforeUnmount(() => {
	unmounted = true
	editor?.destroy()
})
</script>

<template>
	<div class="page-view">
		<div v-if="loading" class="page-view__loading">
			<NcLoadingIcon :size="44" />
		</div>
		<NcEmptyContent v-else-if="error" :name="error">
			<template #icon>
				<NcIconSvgWrapper :path="mdiAlertCircleOutline" />
			</template>
		</NcEmptyContent>
		<div v-show="!loading && !error" ref="editorContainer" class="page-view__editor" />
	</div>
</template>

<style lang="scss" scoped>
.page-view {
	height: 100%;
	display: flex;
	flex-direction: column;

	&__loading {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	&__editor {
		flex: 1;
		min-height: 0;
		overflow-y: auto;
	}
}
</style>
