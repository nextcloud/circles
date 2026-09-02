<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { TeamPage } from '../api.ts'

import { mdiAlertCircleOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { logger } from '../../logger.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

/** The inline collaborative editor exposed by the Text app. */
interface TextEditorEmbed {
	destroy(): void
}

const props = defineProps<{
	teamId: string
	fileId: string
}>()

const resourcesStore = useTeamResourcesStore()

const editorContainer = ref<HTMLElement | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

let editor: TextEditorEmbed | null = null

// The keyed router-view mounts a fresh instance per page, so loadPage runs
// once per instance; only the unmount can overtake it mid-await.
let unmounted = false

/** Tear down the mounted editor, if any. */
function destroyEditor(): void {
	editor?.destroy()
	editor = null
	if (editorContainer.value) {
		editorContainer.value.innerHTML = ''
	}
}

/**
 * Resolve once the initial pages load (owned by the team page) has settled,
 * with the pages either known or failed.
 */
function waitForPages(): Promise<void> {
	const settled = (): boolean => {
		const slot = resourcesStore.forTeam(props.teamId)
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

/** Find the page behind the tab and mount the Text editor on it. */
async function loadPage(): Promise<void> {
	if (window.OCA.Text === undefined) {
		error.value = t('circles', 'The Text app is required to show team pages')
		loading.value = false
		return
	}

	try {
		await waitForPages()
		let slot = resourcesStore.forTeam(props.teamId)
		if (slot.folderError || slot.pagesError || !slot.folder) {
			throw new Error('The team pages are not available')
		}
		let page: TeamPage | undefined = slot.pages.find((candidate) => String(candidate.fileId) === props.fileId)
		if (!page) {
			// The cached list may predate this page (e.g. a deep link to a
			// page created since) — refresh once before giving up.
			await resourcesStore.ensurePages(props.teamId, true)
			slot = resourcesStore.forTeam(props.teamId)
			page = slot.pages.find((candidate) => String(candidate.fileId) === props.fileId)
		}
		if (!page) {
			error.value = t('circles', 'This page no longer exists')
			return
		}
		if (!editorContainer.value) {
			return
		}
		// The guard above cannot narrow across the awaits, but the Text app
		// does not vanish mid-run.
		const created = await window.OCA.Text!.createCollaborativeEditor({
			el: editorContainer.value,
			fileId: page.fileId,
			filePath: page.filePath,
		})
		// The unmount may have hit while the editor was being created.
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
	destroyEditor()
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

		<div
			v-show="!loading && !error"
			ref="editorContainer"
			class="page-view__editor" />
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
		// The embed grows with its content; this container is the scroller.
		overflow-y: auto;
	}
}
</style>
