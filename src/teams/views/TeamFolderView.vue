<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiFolderOffOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import TeamFolderWidget from '../components/TeamFolderWidget.vue'
import { logger } from '../../logger.ts'
import { canCreateTeamFolder } from '../composables/useTeamActions.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const props = defineProps<{
	teamId: string
}>()

const store = useStore()
const circle = computed(() => store.getters.getCircle(props.teamId))

// The folder state comes from the per-team resources store, loaded by the
// team page; this view only reads it (and writes back on folder creation).
const resourcesStore = useTeamResourcesStore()
const teamResources = computed(() => resourcesStore.forTeam(props.teamId))

const loading = computed(() => !teamResources.value.folderChecked && !teamResources.value.folderError)
const folderId = computed(() => teamResources.value.folder?.id ?? null)
const mountPoint = computed(() => teamResources.value.folder?.mountPoint ?? null)
const loadError = computed(() => teamResources.value.folderError)
const creating = ref(false)

// Provided by the server: whether a team folder backend (the Team Folders
// app) is available at all.
const teamFolderProviderAvailable = Boolean(loadState('circles', 'teamFolderProviderAvailable', true))

const canCreate = computed(() => canCreateTeamFolder(circle.value))

const emptyDescription = computed(() => {
	if (!teamFolderProviderAvailable) {
		return t('circles', 'This team does not have a team folder yet. Ask your administrator to enable the Team Folders app.')
	}
	if (canCreate.value) {
		return t('circles', 'This team does not have a team folder yet. Create one to share files with the whole team.')
	}
	return t('circles', 'This team does not have a team folder yet. Ask a team owner to create one.')
})

/** Create the team folder from the empty state. */
async function createTeamFolder(): Promise<void> {
	creating.value = true
	try {
		// The store update also refreshes the sidebar's create menu and entries.
		await resourcesStore.createFolder(props.teamId)
	} catch (error) {
		logger.error('Could not create team folder', { error, teamId: props.teamId })
		showError(t('circles', 'Could not create the team folder'))
	} finally {
		creating.value = false
	}
}
</script>

<template>
	<div class="team-folder">
		<div v-if="loading" class="team-folder__loading">
			<NcLoadingIcon :size="44" />
		</div>

		<NcEmptyContent
			v-else-if="loadError"
			:name="t('circles', 'Team folder unavailable')"
			:description="t('circles', 'Could not load the team folder. Please try again later.')" />

		<TeamFolderWidget
			v-else-if="mountPoint && folderId"
			:mountPoint="mountPoint"
			:rootFolderId="folderId" />

		<NcEmptyContent
			v-else
			:name="t('circles', 'No team folder yet')"
			:description="emptyDescription">
			<template #icon>
				<NcIconSvgWrapper :path="mdiFolderOffOutline" />
			</template>
			<template #action>
				<NcButton
					v-if="teamFolderProviderAvailable && canCreate"
					variant="primary"
					:disabled="creating"
					class="team-folder__create"
					@click="createTeamFolder">
					{{ t('circles', 'Create team folder') }}
				</NcButton>
			</template>
		</NcEmptyContent>
	</div>
</template>

<style lang="scss" scoped>
.team-folder {
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
