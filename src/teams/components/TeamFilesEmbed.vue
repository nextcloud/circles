<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { inject, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { logger } from '../../logger.ts'
import { getRenderFilesApp, registerTeamFilesView, removeTeamFilesView, SIDEBAR_HOST_KEY, TEAM_FILES_VIEW_ID } from '../services/teamFilesView.ts'

const props = defineProps<{
	mountPoint: string
}>()

const container = ref<HTMLElement | null>(null)
const sidebarHost = inject(SIDEBAR_HOST_KEY, ref(null))

let rendered: { destroy: () => void } | undefined

/** Render the Files app's file list of the team folder. */
function render(): void {
	destroy()
	const renderFilesApp = getRenderFilesApp()
	if (!container.value || !renderFilesApp) {
		return
	}

	registerTeamFilesView(props.mountPoint)
	try {
		rendered = renderFilesApp(container.value, TEAM_FILES_VIEW_ID, {
			rootDir: `/${props.mountPoint}`,
			sidebarEl: sidebarHost.value ?? undefined,
		})
	} catch (error) {
		logger.error('Could not render the team folder file list', { error, mountPoint: props.mountPoint })
		removeTeamFilesView()
	}
}

/** Remove the file list and its view again. */
function destroy(): void {
	rendered?.destroy()
	rendered = undefined
	removeTeamFilesView()
}

onMounted(render)
watch(() => props.mountPoint, render)
onBeforeUnmount(destroy)
</script>

<template>
	<div ref="container" class="team-files" />
</template>

<style lang="scss" scoped>
.team-files {
	height: 100%;
	overflow: hidden;
}
</style>
