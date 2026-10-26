<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { TeamPage } from '../api.ts'
import type { NavigationEntry } from '../firstTab.ts'
import type { TeamAppNavigationAction, TeamNavigationEntry } from '../teamAppIntegrations/types.ts'

import { mdiAccountMultiple, mdiAccountMultipleOutline, mdiArrowTopRight, mdiCogOutline, mdiFileDocumentOutline, mdiFolder, mdiFolderOutline, mdiFolderPlusOutline, mdiPlus, mdiRefresh, mdiShareVariant, mdiShareVariantOutline, mdiTrashCanOutline } from '@mdi/js'
import { showError, showSuccess } from '@nextcloud/dialogs'
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
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import TeamAppIcon from './TeamAppIcon.vue'
import TeamHeader from './TeamHeader.vue'
import TeamSettingsDialog from './TeamSettingsDialog.vue'
import { logger } from '../../logger.ts'
import { createTeamPage, deleteTeamPage, renameTeamPage } from '../api.ts'
import { canCreateTeamFolder, useTeamActions } from '../composables/useTeamActions.ts'
import { appEntryRoute, isReferenceEntry } from '../firstTab.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'
import { useTeamsStore } from '../store.ts'
import { useTeamAppsStore } from '../teamAppIntegrations/teamAppsStore.ts'

const route = useRoute()
const router = useRouter()
const store = useStore()
const teamId = computed(() => String(route.params.teamId ?? ''))
const circle = computed(() => teamId.value ? store.getters.getCircle(teamId.value) : null)
const teams = useTeamsStore()
const { canManage, isTeamAdmin } = useTeamActions(() => teams.getTeam(teamId.value))
const resources = useTeamResourcesStore()
const apps = useTeamAppsStore()
let navigationActive = true
const appActions = computed(() => apps.actions(teamId.value))
const settingsOpen = ref(false)
const pageDialogOpen = ref(false)
const newPageName = ref('')
const pageToDelete = ref<TeamPage | null>(null)
const dragOrder = ref<TeamNavigationEntry[] | null>(null)
const draggedId = ref<string | null>(null)
const teamResources = computed(() => resources.forTeam(teamId.value))
watch(teamId, () => {
	dragOrder.value = null
	draggedId.value = null
	pageDialogOpen.value = false
	pageToDelete.value = null
})
onBeforeUnmount(() => {
	navigationActive = false
})

const orderedEntries = computed<NavigationEntry[]>(() => {
	const appEntries = new Map(apps.entries(teamId.value).map((entry) => [entry.id, entry]))
	const dragging = dragOrder.value
	const navigation = dragging
		? [...dragging, ...teamResources.value.navigation.filter(({ id }) => !dragging.some((entry) => entry.id === id))]
		: teamResources.value.navigation
	return navigation.flatMap((item): NavigationEntry[] => {
		if (item.id === 'team-folder') {
			return [{
				id: 'team-folder',
				label: t('circles', 'Team folder'),
				icon: route.name === 'team-folder' ? mdiFolder : mdiFolderOutline,
				to: { name: 'team-folder', params: { teamId: teamId.value } },
			}]
		}
		if (item.id === 'home') {
			return [{
				id: 'home',
				label: t('circles', 'Shared with the team'),
				icon: route.name === 'team-home' ? mdiShareVariant : mdiShareVariantOutline,
				to: { name: 'team-home', params: { teamId: teamId.value } },
			}]
		}
		const page = teamResources.value.pages.find((page) => item.id === `page-${page.fileId}`)
		if (page) {
			return [{
				id: item.id,
				label: page.title,
				icon: mdiFileDocumentOutline,
				page,
				to: { name: 'team-page', params: { teamId: teamId.value, fileId: String(page.fileId) } },
			}]
		}
		const entry = appEntries.get(item.id)
		if (!entry) {
			return []
		}
		const openInNewTab = !isReferenceEntry(entry)
		return [{
			...entry,
			openInNewTab,
			to: openInNewTab ? undefined : appEntryRoute(teamId.value, entry),
		}]
	})
})
const createMenuEntries = computed<TeamAppNavigationAction[]>(() => {
	const actions = [...appActions.value]
	const creatingTeamId = teamId.value
	if (teamResources.value.folder && !teamResources.value.folderError) {
		actions.push({
			id: 'page',
			label: t('circles', 'New page'),
			icon: mdiFileDocumentOutline,
			run: () => {
				newPageName.value = ''
				pageDialogOpen.value = true
			},
		})
	}
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

/** Create the named page and open it inside Circles. */
async function onCreatePage(): Promise<void> {
	const id = teamId.value
	const folder = teamResources.value.folder
	const name = newPageName.value.trim()
	if (!folder || !name) {
		return
	}
	await createTeamPage(folder.mountPoint, name)
	await resources.ensurePages(id, true)
	const slot = resources.forTeam(id)
	if (slot.pagesError) {
		throw new Error('The page was created but the page list could not be refreshed')
	}
	const page = slot.pages.find((page) => page.title === name)
	if (!page) {
		throw new Error('The created page could not be found')
	}
	showSuccess(t('circles', 'Page "{name}" created in the team folder', { name }))
	if (navigationActive && teamId.value === id) {
		pageDialogOpen.value = false
		await router.push({ name: 'team-page', params: { teamId: id, fileId: String(page.fileId) } })
	}
}

/**
 * Rename a page, preserving its file ID and navigation position.
 *
 * @param page - The page to rename
 * @param name - The new title
 */
async function onRenamePage(page: TeamPage, name: string): Promise<void> {
	if (name.trim() === page.title) {
		return
	}
	const id = teamId.value
	await apps.run({
		id: 'rename-page',
		label: t('circles', 'Rename page'),
		run: async () => {
			await renameTeamPage(page, name.trim())
			await resources.ensurePages(id, true)
			if (resources.forTeam(id).pagesError) {
				throw new Error('Could not refresh renamed page')
			}
		},
	})
}

/** Delete the confirmed page and leave its editor if it was open. */
async function onDeletePage(): Promise<void> {
	const page = pageToDelete.value
	const id = teamId.value
	if (!page) {
		return
	}
	await deleteTeamPage(page)
	await resources.ensurePages(id, true)
	if (resources.forTeam(id).pagesError) {
		throw new Error('Could not refresh deleted page')
	}
	if (navigationActive && teamId.value === id) {
		pageToDelete.value = null
		if (route.name === 'team-page' && route.params.fileId === String(page.fileId)) {
			await router.push({ name: 'team-folder', params: { teamId: id } })
		}
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
	const visible = new Set(orderedEntries.value.map(({ id }) => id))
	dragOrder.value = teamResources.value.navigation.filter(({ id }) => visible.has(id))
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
	const from = order.findIndex(({ id }) => id === draggedId.value)
	const over = order.findIndex(({ id }) => id === entry.id)
	if (from === -1 || over === -1) {
		return
	}
	const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
	let to = event.clientY > rect.top + rect.height / 2 ? over + 1 : over
	if (from < to) {
		to--
	}
	if (to !== from) {
		const [dragged] = order.splice(from, 1)
		order.splice(to, 0, dragged!)
	}
}

/** Persist a changed navigation order after dragging ends. */
async function onEntryDragEnd(): Promise<void> {
	const order = dragOrder.value
	draggedId.value = null
	dragOrder.value = null
	if (!order || order.map(({ id }) => id).join('\n') === teamResources.value.navigation.map(({ id }) => id).join('\n')) {
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

/** Retry the native Pages source without interrupting other app providers. */
async function retryPages(): Promise<void> {
	const id = teamId.value
	await apps.run({
		id: 'retry-pages',
		label: t('circles', 'Retry'),
		run: async () => {
			await resources.ensurePages(id, true)
			if (resources.forTeam(id).pagesError) {
				throw new Error('Could not load team pages')
			}
		},
	})
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
							<span class="team-navigation__action-icon" aria-hidden="true">
								<span
									v-if="action.iconUrl"
									class="team-navigation__app-icon"
									:style="{ maskImage: `url(${JSON.stringify(action.iconUrl)})` }" />
								<TeamAppIcon v-else :icon="action.icon" />
							</span>
						</template>
						{{ action.label }}
					</NcActionButton>
				</template>
			</NcAppNavigationCaption>
			<NcAppNavigationItem
				v-for="entry in orderedEntries"
				:key="entry.id"
				class="team-navigation__entry"
				:class="{ 'team-navigation__entry--dragging': entry.id === draggedId }"
				:name="entry.label"
				:editable="Boolean(entry.page) && isTeamAdmin"
				:editLabel="t('circles', 'Rename page')"
				:editPlaceholder="t('circles', 'Page name')"
				:to="entry.to"
				:href="entry.to ? undefined : entry.href"
				:target="entry.openInNewTab ? '_blank' : undefined"
				:rel="entry.openInNewTab ? 'noopener noreferrer' : undefined"
				:draggable="isTeamAdmin"
				@update:name="entry.page && onRenamePage(entry.page, $event)"
				@dragstart="onEntryDragStart(entry, $event)"
				@dragover="onEntryDragOver(entry, $event)"
				@drop.prevent
				@dragend="onEntryDragEnd">
				<template #icon>
					<TeamAppIcon :icon="entry.icon" :svg="entry.iconSvg" :url="entry.iconURL" />
				</template>
				<template v-if="entry.href && entry.openInNewTab" #counter>
					<NcIconSvgWrapper inline :path="mdiArrowTopRight" :size="16" />
				</template>
				<template v-if="entry.page && isTeamAdmin" #actions>
					<NcActionButton closeAfterClick :disabled="apps.busy" @click="pageToDelete = entry.page">
						<template #icon>
							<NcIconSvgWrapper :path="mdiTrashCanOutline" :size="20" />
						</template>
						{{ t('circles', 'Delete page') }}
					</NcActionButton>
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				v-if="teamResources.pagesError"
				:name="t('circles', 'Could not load team pages')"
				@click="retryPages">
				<template #icon>
					<NcIconSvgWrapper :path="mdiRefresh" :size="20" />
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

	<NcDialog v-model:open="pageDialogOpen" :name="t('circles', 'New page')">
		<NcTextField
			v-model="newPageName"
			:label="t('circles', 'Page name')"
			:disabled="apps.busy"
			@keyup.enter="apps.run({ id: 'create-page', label: t('circles', 'Create page'), run: onCreatePage })" />
		<template #actions>
			<NcButton :disabled="apps.busy || !newPageName.trim()" @click="apps.run({ id: 'create-page', label: t('circles', 'Create page'), run: onCreatePage })">
				{{ t('circles', 'Create page') }}
			</NcButton>
		</template>
	</NcDialog>

	<NcDialog :open="pageToDelete !== null" :name="t('circles', 'Delete page')" @update:open="!$event && (pageToDelete = null)">
		<p>{{ t('circles', 'Are you sure you want to delete the page "{name}"? It can be restored from the trash bin.', { name: pageToDelete?.title ?? '' }) }}</p>
		<template #actions>
			<NcButton variant="error" :disabled="apps.busy" @click="apps.run({ id: 'delete-page', label: t('circles', 'Delete page'), run: onDeletePage })">
				{{ t('circles', 'Delete page') }}
			</NcButton>
		</template>
	</NcDialog>
	<TeamSettingsDialog v-if="settingsOpen && circle" v-model:open="settingsOpen" :circle="circle" />
</template>

<style lang="scss" scoped>
.team-navigation__action-icon {
	display: flex;
	flex: 0 0 var(--default-clickable-area);
	align-items: center;
	justify-content: center;
	width: var(--default-clickable-area);
	height: var(--default-clickable-area);
}

.team-navigation__app-icon {
	display: block;
	flex: 0 0 20px;
	width: 20px;
	height: 20px;
	background-color: currentColor;
	mask-position: center;
	mask-repeat: no-repeat;
	mask-size: contain;
}

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
</style>
