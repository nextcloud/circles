<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { NavigationEntry } from '../firstTab.ts'
import type { TeamAppNavigationAction } from '../integrations/types.ts'

import { mdiAccountMultiple, mdiAccountMultipleOutline, mdiArrowTopRight, mdiCogOutline, mdiFolder, mdiFolderOutline, mdiFolderPlusOutline, mdiMagnify, mdiMinus, mdiPlus, mdiRefresh, mdiShareVariant, mdiShareVariantOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import TeamAppIcon from './TeamAppIcon.vue'
import TeamHeader from './TeamHeader.vue'
import TeamSettingsDialog from './TeamSettingsDialog.vue'
import { logger } from '../../logger.ts'
import { canCreateTeamFolder, useTeamActions } from '../composables/useTeamActions.ts'
import { appEntryRoute, isReferenceEntry, orderEntries } from '../firstTab.ts'
import { getTeamAppProvider } from '../integrations/registry.ts'
import { useTeamAppsStore } from '../integrations/store.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'
import { useTeamsStore } from '../store.ts'

const route = useRoute()
const router = useRouter()
const store = useStore()
const teamId = computed(() => String(route.params.teamId ?? ''))
const circle = computed(() => teamId.value ? store.getters.getCircle(teamId.value) : null)
const teams = useTeamsStore()
const { canManage, isTeamAdmin } = useTeamActions(() => teams.getTeam(teamId.value))
const resources = useTeamResourcesStore()
const apps = useTeamAppsStore()
const promptOpen = ref(false)
const promptTitle = ref('')
const promptValue = ref('')
let settlePrompt: ((value: string | null) => void) | undefined
let navigationActive = true
const appActions = computed(() => apps.actions(teamId.value, promptForName))
const existingResourceGroups = computed(() => apps.existingResources(teamId.value))
const resourceDialogMode = ref<'add' | 'remove' | null>(null)
const selectedRemovalIds = ref<string[]>([])
const savingRemovals = ref(false)
const removableResources = computed(() => apps.entries(teamId.value))
const resourceSearch = ref('')
const filteredExistingResources = computed(() => {
	const query = resourceSearch.value.trim().toLocaleLowerCase()
	const candidates = resourceDialogMode.value === 'remove'
		? removableResources.value.map((entry) => ({
				label: entry.label,
				icon: entry.icon,
				appName: getTeamAppProvider(entry.providerId)?.name ?? entry.providerId,
				key: entry.id,
				addAction: undefined,
			}))
		: existingResourceGroups.value.flatMap((group) => group.actions
				.map((action) => ({ label: action.label, icon: action.icon, addAction: action, appName: group.name, key: `${group.id}:${action.id}` })))
	return candidates.filter(({ label, appName }) => `${appName} ${label}`.toLocaleLowerCase().includes(query))
})
watch(resourceDialogMode, () => {
	resourceSearch.value = ''
	selectedRemovalIds.value = []
})
watch(isTeamAdmin, (admin) => {
	if (!admin && resourceDialogMode.value === 'remove') {
		resourceDialogMode.value = null
	}
})
const settingsOpen = ref(false)
const dragOrder = ref<string[] | null>(null)
const draggedId = ref<string | null>(null)
const teamResources = computed(() => resources.forTeam(teamId.value))
watch(teamId, () => {
	resourceDialogMode.value = null
	dragOrder.value = null
	draggedId.value = null
	finishPrompt(null)
})
onBeforeUnmount(() => {
	navigationActive = false
	finishPrompt(null)
})

const entries = computed<NavigationEntry[]>(() => [
	{
		id: 'team-folder',
		label: t('circles', 'Team folder'),
		icon: route.name === 'team-folder' ? mdiFolder : mdiFolderOutline,
		to: { name: 'team-folder', params: { teamId: teamId.value } },
	},
	...apps.entries(teamId.value).map((entry) => {
		const openInNewTab = !isReferenceEntry(entry)
		return {
			...entry,
			openInNewTab,
			to: openInNewTab ? undefined : appEntryRoute(teamId.value, entry),
		}
	}),
	{
		id: 'home',
		label: t('circles', 'Shared with the team'),
		icon: route.name === 'team-home' ? mdiShareVariant : mdiShareVariantOutline,
		to: { name: 'team-home', params: { teamId: teamId.value } },
	},
])
const orderedEntries = computed(() => orderEntries(entries.value, dragOrder.value ?? teamResources.value.tabOrder))
const createMenuEntries = computed<TeamAppNavigationAction[]>(() => {
	const actions = [...appActions.value]
	const creatingTeamId = teamId.value
	if (teamResources.value.folderChecked && !teamResources.value.folder && canCreateTeamFolder(circle.value)) {
		actions.unshift({
			id: 'folder',
			label: t('circles', 'Create team folder'),
			icon: mdiFolderPlusOutline,
			run: async () => {
				await resources.createFolder(creatingTeamId)
				if (!navigationActive || teamId.value !== creatingTeamId) {
					return
				}
				await apps.refresh(creatingTeamId)
				if (navigationActive && teamId.value === creatingTeamId) {
					await router.push({ name: 'team-folder', params: { teamId: creatingTeamId } })
				}
			},
		})
	}
	return actions
})
const showCreateMenuSeparator = computed(() => createMenuEntries.value.length > 0
	&& (existingResourceGroups.value.length > 0 || removableResources.value.length > 0))

/**
 * Resolve the active name prompt and close its dialog.
 *
 * @param value Submitted name, or null when cancelled
 */
function finishPrompt(value: string | null): void {
	promptOpen.value = false
	const settle = settlePrompt
	settlePrompt = undefined
	settle?.(value)
}

/**
 * Request a resource name within the active team scope.
 *
 * @param title Dialog heading and field label
 * @param signal Team-scope cancellation signal
 */
function promptForName(title: string, signal: AbortSignal): Promise<string | null> {
	signal.throwIfAborted()
	if (!navigationActive) {
		return Promise.resolve(null)
	}
	if (settlePrompt) {
		throw new Error('Another Teams app dialog is already open')
	}
	promptTitle.value = title
	promptValue.value = ''
	promptOpen.value = true
	return new Promise((resolve) => {
		const cancel = () => finishPrompt(null)
		signal.addEventListener('abort', cancel, { once: true })
		settlePrompt = (value) => {
			signal.removeEventListener('abort', cancel)
			resolve(value)
		}
	})
}

/**
 * Close the resource picker and execute its selected add action.
 *
 * @param action Existing-resource action selected in the dialog
 */
function runExistingResourceAction(action: TeamAppNavigationAction): void {
	if (apps.busy) {
		return
	}
	resourceDialogMode.value = null
	void apps.run(action)
}

/** Remove the selected entries while the current team and admin role remain valid. */
async function saveRemovals(): Promise<void> {
	if (!isTeamAdmin.value || apps.busy || savingRemovals.value) {
		return
	}
	const selected = removableResources.value.filter(({ id }) => selectedRemovalIds.value.includes(id))
	if (!selected.length) {
		return
	}
	const savingTeamId = teamId.value
	savingRemovals.value = true
	try {
		await apps.run({
			id: 'remove-navigation-resources',
			label: t('circles', 'Remove resources from navigation'),
			run: async () => {
				for (const resource of selected) {
					if (!isTeamAdmin.value || teamId.value !== savingTeamId) {
						return
					}
					await apps.removeFromNavigation(savingTeamId, resource.providerId, resource.entryId)
				}
				if (teamId.value === savingTeamId) {
					resourceDialogMode.value = null
				}
			},
		})
	} finally {
		savingRemovals.value = false
	}
}

/**
 *
 * @param entry Dragged entry
 * @param event Drag event
 */
function onEntryDragStart(entry: NavigationEntry, event: DragEvent): void {
	if (!isTeamAdmin.value) {
		return
	}
	draggedId.value = entry.id
	dragOrder.value = orderedEntries.value.map((item) => item.id)
	if (event.dataTransfer) {
		event.dataTransfer.effectAllowed = 'move'
		event.dataTransfer.setData('text/plain', entry.id)
	}
}

/**
 *
 * @param event Drag event
 */
function onListDragOver(event: DragEvent): void {
	if (!draggedId.value) {
		return
	}
	event.preventDefault()
	if (event.dataTransfer) {
		event.dataTransfer.dropEffect = 'move'
	}
}

/**
 *
 * @param entry Hovered entry
 * @param event Drag event
 */
function onEntryDragOver(entry: NavigationEntry, event: DragEvent): void {
	const order = dragOrder.value
	if (!order || !draggedId.value) {
		return
	}
	onListDragOver(event)
	if (entry.id === draggedId.value) {
		return
	}
	const from = order.indexOf(draggedId.value)
	const over = order.indexOf(entry.id)
	if (from === -1 || over === -1) {
		return
	}
	const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
	let to = event.clientY > rect.top + rect.height / 2 ? over + 1 : over
	if (from < to) {
		to--
	}
	if (to !== from) {
		order.splice(from, 1)
		order.splice(to, 0, draggedId.value)
	}
}

/** Persist a changed navigation order after dragging ends. */
async function onEntryDragEnd(): Promise<void> {
	const order = dragOrder.value
	draggedId.value = null
	dragOrder.value = null
	if (!order || order.join('\n') === orderedEntries.value.map((entry) => entry.id).join('\n')) {
		return
	}
	try {
		await resources.saveOrder(teamId.value, order)
	} catch (error) {
		logger.error('Could not save the navigation order', { error, teamId: teamId.value })
		showError(t('circles', 'Could not save the navigation order'))
	}
}

/** Refresh a failed provider, or reload when no team context is available. */
async function retry(): Promise<void> {
	const context = apps.context(teamId.value, apps.errors(teamId.value)[0] ?? '')
	if (context) {
		await apps.run({ id: 'retry', label: t('circles', 'Retry'), run: context.refresh })
	} else {
		window.location.reload()
	}
}
</script>

<template>
	<NcAppNavigation
		v-if="teamId"
		:aria-label="t('circles', 'Team sections')"
		@dragover="onListDragOver"
		@drop.prevent>
		<template #search>
			<TeamHeader v-if="circle" class="team-navigation__team-header" :circle="circle" />
		</template>
		<template #list>
			<NcAppNavigationCaption
				:name="t('circles', 'Team resources')"
				:aria-label="t('circles', 'Manage team resources')">
				<template v-if="isTeamAdmin" #actionsTriggerIcon>
					<NcIconSvgWrapper :path="mdiPlus" :size="20" />
				</template>
				<template v-if="isTeamAdmin" #actions>
					<NcActionButton
						v-for="action in createMenuEntries"
						:key="action.id"
						closeAfterClick
						:disabled="apps.busy"
						@click="apps.run(action)">
						<template #icon>
							<TeamAppIcon :icon="action.icon" />
						</template>
						{{ action.label }}
					</NcActionButton>
					<NcActionSeparator v-if="showCreateMenuSeparator" class="team-action-separator" />
					<NcActionButton
						v-if="existingResourceGroups.length > 0"
						closeAfterClick
						:disabled="apps.busy"
						@click="resourceDialogMode = 'add'">
						<template #icon>
							<NcIconSvgWrapper :path="mdiPlus" :size="20" />
						</template>
						{{ t('circles', 'Add existing resource') }}
					</NcActionButton>
					<NcActionButton
						v-if="removableResources.length > 0"
						closeAfterClick
						:disabled="apps.busy"
						@click="resourceDialogMode = 'remove'">
						<template #icon>
							<NcIconSvgWrapper :path="mdiMinus" :size="20" />
						</template>
						{{ t('circles', 'Remove resource from navigation') }}
					</NcActionButton>
				</template>
			</NcAppNavigationCaption>
			<NcAppNavigationItem
				v-for="entry in orderedEntries"
				:key="entry.id"
				class="team-navigation__entry"
				:class="{ 'team-navigation__entry--dragging': entry.id === draggedId }"
				:name="entry.label"
				:to="entry.to"
				:href="entry.to ? undefined : entry.href"
				:target="entry.openInNewTab ? '_blank' : undefined"
				:rel="entry.openInNewTab ? 'noopener noreferrer' : undefined"
				:draggable="isTeamAdmin"
				@dragstart="onEntryDragStart(entry, $event)"
				@dragover="onEntryDragOver(entry, $event)"
				@drop.prevent
				@dragend="onEntryDragEnd">
				<template #icon>
					<TeamAppIcon :icon="entry.icon" />
				</template>
				<template v-if="entry.href && entry.openInNewTab" #counter>
					<NcIconSvgWrapper inline :path="mdiArrowTopRight" :size="16" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				v-for="provider in apps.errors(teamId)"
				:key="`error-${provider}`"
				:name="t('circles', 'Could not load {app}', { app: provider })"
				@click="retry">
				<template #icon>
					<NcIconSvgWrapper :path="mdiRefresh" :size="20" />
				</template>
			</NcAppNavigationItem>
		</template>
		<template #footer>
			<ul v-if="circle?.isMember" class="team-navigation__footer">
				<NcActionSeparator />
				<NcAppNavigationItem :name="t('circles', 'Members')" :to="{ name: 'team-members', params: { teamId } }">
					<template #icon>
						<NcIconSvgWrapper :path="route.name === 'team-members' ? mdiAccountMultiple : mdiAccountMultipleOutline" :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem v-if="canManage" :name="t('circles', 'Team settings')" @click="settingsOpen = true">
					<template #icon>
						<NcIconSvgWrapper :path="mdiCogOutline" :size="20" />
					</template>
				</NcAppNavigationItem>
			</ul>
		</template>
	</NcAppNavigation>
	<NcDialog
		v-if="resourceDialogMode"
		:noClose="savingRemovals"
		:name="resourceDialogMode === 'remove' ? t('circles', 'Remove resource from navigation') : t('circles', 'Add existing resource')"
		size="normal"
		@update:open="!$event && (resourceDialogMode = null)">
		<div class="team-navigation__existing-resources">
			<p v-if="resourceDialogMode === 'remove'">
				{{ t('circles', 'Select resources to remove from this team\'s navigation. The resources themselves will not be deleted.') }}
			</p>
			<p v-else>
				{{ t('circles', 'Choose a resource to add to this team\'s navigation.') }}
			</p>
			<NcTextField
				v-model="resourceSearch"
				type="search"
				:disabled="savingRemovals"
				:label="t('circles', 'Search resources')"
				autofocus
				:showTrailingButton="resourceSearch.length > 0"
				trailingButtonIcon="close"
				:trailingButtonLabel="t('circles', 'Clear search')"
				@trailingButtonClick="resourceSearch = ''">
				<template #icon>
					<NcIconSvgWrapper :path="mdiMagnify" :size="20" />
				</template>
			</NcTextField>
			<div class="team-navigation__resource-results">
				<ul v-if="filteredExistingResources.length" :aria-label="resourceDialogMode === 'remove' ? t('circles', 'Navigation resources') : t('circles', 'Existing resources')">
					<template v-for="resource in filteredExistingResources" :key="resource.key">
						<li v-if="resourceDialogMode === 'remove'">
							<NcCheckboxRadioSwitch
								v-model="selectedRemovalIds"
								:value="resource.key"
								:disabled="apps.busy || savingRemovals">
								<span class="team-navigation__removal-label">
									<span>{{ resource.label }}</span>
									<span class="team-navigation__removal-app">{{ resource.appName }}</span>
								</span>
							</NcCheckboxRadioSwitch>
						</li>
						<NcListItem
							v-else
							:name="resource.label"
							:aria-disabled="apps.busy"
							@click="resource.addAction && runExistingResourceAction(resource.addAction)">
							<template #icon>
								<TeamAppIcon :icon="resource.icon" />
							</template>
							<template #subname>
								{{ resource.appName }}
							</template>
						</NcListItem>
					</template>
				</ul>
				<NcEmptyContent v-else :name="t('circles', 'No resources found')">
					<template #icon>
						<NcIconSvgWrapper :path="mdiMagnify" />
					</template>
				</NcEmptyContent>
			</div>
		</div>
		<template #actions>
			<NcButton :disabled="savingRemovals" @click="resourceDialogMode = null">
				{{ t('circles', 'Cancel') }}
			</NcButton>
			<NcButton
				v-if="resourceDialogMode === 'remove'"
				variant="primary"
				:disabled="apps.busy || savingRemovals || selectedRemovalIds.length === 0"
				@click="saveRemovals">
				{{ t('circles', 'Remove') }}
			</NcButton>
		</template>
	</NcDialog>
	<NcDialog
		v-if="promptOpen"
		:name="promptTitle"
		size="small"
		@update:open="!$event && finishPrompt(null)">
		<NcTextField
			v-model="promptValue"
			:label="promptTitle"
			autofocus
			@keyup.enter="promptValue.trim() && finishPrompt(promptValue.trim())" />
		<template #actions>
			<NcButton @click="finishPrompt(null)">
				{{ t('circles', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="!promptValue.trim()"
				@click="finishPrompt(promptValue.trim())">
				{{ t('circles', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
	<TeamSettingsDialog v-if="settingsOpen && circle" v-model:open="settingsOpen" :circle="circle" />
</template>

<style lang="scss" scoped>
.team-navigation__team-header {
	padding: calc(2 * var(--default-grid-baseline));
	padding-block-end: var(--default-grid-baseline);
}

.team-navigation__entry--dragging {
	opacity: 0.4;
}

.team-navigation__entry :deep(.app-navigation-entry:has(> .app-navigation-entry-link:focus-visible)) {
	box-shadow: 0 0 0 4px var(--color-main-background);
	outline: 2px solid var(--color-main-text);
}

.team-navigation__entry :deep(.app-navigation-entry > .app-navigation-entry-link:focus-visible) {
	box-shadow: none;
	outline: none;
}

.team-navigation__footer {
	padding: var(--default-grid-baseline) calc(2 * var(--default-grid-baseline)) calc(2 * var(--default-grid-baseline));
}

.team-navigation__existing-resources {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.team-navigation__resource-results {
	min-height: 180px;
	max-height: 50vh;
	overflow-y: auto;

	ul {
		list-style: none;
		padding: 0;
		margin: 0;
	}
}

.team-navigation__removal-label {
	display: flex;
	flex-direction: column;
	padding-block: 4px;
	overflow-wrap: anywhere;
}

.team-navigation__removal-app {
	color: var(--color-text-maxcontrast);
	font-size: var(--default-font-size-small);
}
</style>
