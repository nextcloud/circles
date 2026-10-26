<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAccountClockOutline, mdiAccountGroupOutline, mdiAlertCircleOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { logger } from '../../logger.ts'
import { useTeamAppsStore } from '../integrations/store.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'
import { joinCircle } from '../team-page/services/circles.ts'

const props = defineProps<{
	teamId: string
}>()

const store = useStore()
const router = useRouter()
const apps = useTeamAppsStore()

const loading = ref(true)
const circle = computed(() => store.getters.getCircle(props.teamId))
const isMember = computed(() => Boolean(circle.value?.isMember))
const isTeamAdmin = computed(() => Boolean(circle.value?.isAdmin || circle.value?.isOwner))

// This route component owns the team scope, so it is the single loader of
// the per-team resources; the sidebar and the child views only read the
// store. Loads on every team switch, and again when the circle finishes
// loading (membership — and with it most permissions — arrives with it).
// A switch back to a cached team revalidates.
const resourcesStore = useTeamResourcesStore()
let resourceGeneration = 0
/**
 *
 * @param id Team to load
 * @param refresh Whether to revalidate native resources
 */
async function loadResources(id: string, refresh: boolean): Promise<void> {
	const generation = ++resourceGeneration
	const before = apps.entries(id)
	await resourcesStore.loadTeam(id, refresh)
	if (props.teamId !== id || !isMember.value || generation !== resourceGeneration) {
		return
	}
	const resources = resourcesStore.forTeam(id)
	await apps.loadTeam({
		teamId: id,
		teamName: circle.value.sanitizedName || circle.value.name || circle.value.displayName,
		isAdmin: isTeamAdmin.value,
		folder: resources.folder,
		resources: resources.resources,
		sourceError: resources.folderError || resources.resourcesError,
		refresh: () => loadResources(id, true),
		open: (providerId, entryId) => {
			if (props.teamId === id) {
				router.push({ name: 'team-app', params: { teamId: id, providerId, entryId } })
			}
		},
	})
	if (generation !== resourceGeneration) {
		return
	}
	const current = router.currentRoute.value
	if (current.name === 'team-app' && current.params.teamId === id) {
		const wasPresent = before.some((entry) => entry.providerId === current.params.providerId && entry.entryId === current.params.entryId)
		const stillPresent = apps.entries(id).some((entry) => entry.providerId === current.params.providerId && entry.entryId === current.params.entryId)
		if (wasPresent && !stillPresent && !apps.errors(id).includes(String(current.params.providerId))) {
			await router.replace({ name: 'team-folder', params: { teamId: id } })
		}
	}
}

watch([() => props.teamId, isMember, isTeamAdmin], ([id, member, admin], previous) => {
	const changedTeam = previous === undefined || previous[0] !== id
	const changedRole = previous !== undefined && previous[2] !== admin
	if (previous?.[0] && (changedTeam || !member || changedRole)) {
		apps.unloadTeam(previous[0])
	}
	if (id && member) {
		loadResources(id, changedTeam)
	}
}, { immediate: true })
onBeforeUnmount(() => {
	resourceGeneration++
	apps.unloadTeam(props.teamId)
})

/** Load the circle and its members into the Vuex store. */
async function loadCircle(): Promise<void> {
	loading.value = true
	try {
		await store.dispatch('getCircle', props.teamId)
		await store.dispatch('getCircleMembers', { circleId: props.teamId })
	} catch (error) {
		logger.error('Could not load the team', { error })
		showError(t('circles', 'Could not load the team'))
	} finally {
		loading.value = false
	}
}

watch(() => props.teamId, loadCircle, { immediate: true })

const joining = ref(false)

/** Request to join the team; open teams grant membership right away. */
async function onRequestJoin(): Promise<void> {
	joining.value = true
	try {
		await joinCircle(props.teamId)
		// Refresh the circle: either now a member or a pending requester.
		await loadCircle()
	} catch (error) {
		logger.error('Could not join the team', { error })
		showError(t('circles', 'Unable to join the team'))
	} finally {
		joining.value = false
	}
}
</script>

<template>
	<div class="team-page">
		<div v-if="loading && !circle" class="team-page__loading">
			<NcLoadingIcon :size="44" />
		</div>

		<NcEmptyContent
			v-else-if="!circle"
			class="team-page__missing"
			:name="t('circles', 'Team not found')"
			:description="t('circles', 'This team may have been removed.')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiAlertCircleOutline" />
			</template>
		</NcEmptyContent>

		<NcEmptyContent
			v-else-if="circle.isPendingMember"
			class="team-page__not-member"
			:name="t('circles', 'Your request to join this team is pending approval')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiAccountClockOutline" />
			</template>
		</NcEmptyContent>

		<NcEmptyContent
			v-else-if="!circle.isMember"
			class="team-page__not-member"
			:name="t('circles', 'You are not a member of {circle}', { circle: circle.displayName })">
			<template #icon>
				<NcIconSvgWrapper :path="mdiAccountGroupOutline" />
			</template>
			<template v-if="circle.canJoin" #action>
				<NcButton
					variant="primary"
					:disabled="joining"
					@click="onRequestJoin">
					{{ t('circles', 'Request to join') }}
				</NcButton>
			</template>
		</NcEmptyContent>

		<template v-else>
			<div class="team-page__content">
				<!-- Keyed so a param change (e.g. another page's fileId) mounts a
					fresh view instead of reusing the instance with new props. -->
				<router-view :key="$route.path" />
			</div>
		</template>
	</div>
</template>

<style lang="scss" scoped>
.team-page {
	display: flex;
	flex-direction: column;
	height: 100%;

	&__loading,
	&__missing,
	&__not-member {
		flex: 1 1 auto;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	&__content {
		flex: 1 1 auto;
		min-height: 0;
		overflow: hidden;
	}
}
</style>
