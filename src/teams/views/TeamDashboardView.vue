<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import TeamFolderWidget from '../components/TeamFolderWidget.vue'
import { logger } from '../../logger.ts'
import { getTeamFolder } from '../api.ts'
import { router } from '../router.ts'

const props = defineProps<{
	teamId: string
}>()

const route = useRoute()

const loading = ref(true)
const folderId = ref<number | null>(null)
const mountPoint = ref<string | null>(null)
const loadError = ref(false)

/**
 * Load the team folder and redirect to settings when none exists.
 */
async function loadTeamFolder(): Promise<void> {
	loading.value = true
	loadError.value = false
	folderId.value = null
	mountPoint.value = null

	try {
		const folder = await getTeamFolder(props.teamId)
		if (folder !== null) {
			folderId.value = folder.id
			mountPoint.value = folder.mountPoint
		} else {
			await router.replace({
				name: 'team-settings',
				params: { teamId: props.teamId },
				query: route.query,
			})
		}
	} catch (error) {
		logger.error('Could not load team folder', { error, teamId: props.teamId })
		showError(t('circles', 'Could not load team space'))
		loadError.value = true
	} finally {
		loading.value = false
	}
}

watch(() => props.teamId, loadTeamFolder, { immediate: true })
</script>

<template>
	<div class="team-dashboard">
		<div v-if="loading" class="team-dashboard__loading">
			<NcLoadingIcon :size="44" />
		</div>

		<NcEmptyContent
			v-else-if="loadError"
			:name="t('circles', 'Team space unavailable')"
			:description="t('circles', 'Could not load the team space. Please try again later.')" />

		<TeamFolderWidget
			v-else-if="mountPoint && folderId"
			:mountPoint="mountPoint"
			:rootFolderId="folderId" />
	</div>
</template>

<style lang="scss" scoped>
.team-dashboard {
	height: 100%;
	display: flex;
	flex-direction: column;

	&__loading {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
	}
}
</style>
