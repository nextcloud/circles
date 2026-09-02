<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import type { TeamPage } from '../api.ts'

import { mdiAccountMultipleOutline, mdiArrowTopRight, mdiBookOpenPageVariantOutline, mdiCogOutline, mdiFileDocumentOutline, mdiFolderOutline, mdiFolderPlusOutline, mdiPlus, mdiShareVariantOutline, mdiTextBoxPlusOutline, mdiTrashCanOutline, mdiViewDashboardOutline } from '@mdi/js'
import { showConfirmation, showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, nextTick, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionCaption from '@nextcloud/vue/components/NcActionCaption'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import TeamHeader from './TeamHeader.vue'
import TeamSettingsDialog from './TeamSettingsDialog.vue'
import { logger } from '../../logger.ts'
import { createCollective, createDeckBoard, createTeamPage, deleteTeamPage, renameTeamPage } from '../api.ts'
import { canCreateTeamFolder, useTeamActions } from '../composables/useTeamActions.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'
import { useTeamsStore } from '../store.ts'

interface NavigationEntry {
	id: string
	label: string
	icon: string
	to?: RouteLocationRaw
	href?: string
	/** Set for page entries, which carry per-entry actions. */
	page?: TeamPage
}

const route = useRoute()
const router = useRouter()
const store = useStore()

// The navigation frames every team-scoped page; the teams home has no
// team context at all, so it renders without a sidebar. Only the
// /team/:teamId subtree defines the param, so its presence is the scope.
const teamId = computed(() => String(route.params.teamId ?? ''))

const circle = computed(() => (teamId.value ? store.getters.getCircle(teamId.value) : null))

const teamsStore = useTeamsStore()
const team = computed(() => teamsStore.getTeam(teamId.value))
const { canManage, isTeamAdmin } = useTeamActions(() => team.value)

const settingsOpen = ref(false)

const enabledApps = (window as unknown as { OC?: { appswebroots?: Record<string, unknown> } }).OC?.appswebroots ?? {}

// Per-team resource state (folder, resources, pages, order) lives in the
// shared store, loaded by the team page (the owner of the team scope) and
// only read here; the store is still called for targeted refreshes after
// the mutations below.
const resourcesStore = useTeamResourcesStore()
const teamResources = computed(() => resourcesStore.forTeam(teamId.value))

// Pessimistic defaults: a create option only appears once the corresponding
// resource is confirmed to be missing (or, for pages, to exist).
const teamFolder = computed(() => teamResources.value.folder)
const teamFolderChecked = computed(() => teamResources.value.folderChecked)
const collective = computed(() => teamResources.value.resources.find((resource) => resource.provider.id === 'collectives') ?? null)
const resourcesChecked = computed(() => teamResources.value.resourcesChecked)
const creating = ref(false)

// Team pages: markdown files in the team folder's hidden pages subfolder,
// pinned as navigation entries.
const pages = computed(() => teamResources.value.pages)

// Deck boards attached to the team itself, pinned as navigation entries;
// personally-shared boards stay in "Shared with the team".
const boards = computed(() => teamResources.value.boards)

// Transient inline input at the end of the list, summoned by the create
// menu's "New page" option.
const addingPage = ref(false)
const newPageName = ref('')
const newPageInput = ref<{ focus?: () => void } | null>(null)

// Team-level entry order, shared by all members and shown in the saved
// sequence; entries the order does not know yet (e.g. new pages) go last.
const entryOrder = computed(() => teamResources.value.tabOrder)
// While a drag is in progress the entries follow this order live.
const dragOrder = ref<string[] | null>(null)
const draggedId = ref<string | null>(null)

const entries = computed<NavigationEntry[]>(() => {
	const items: NavigationEntry[] = [
		{
			id: 'team-folder',
			label: t('circles', 'Team folder'),
			icon: mdiFolderOutline,
			to: { name: 'team-folder', params: { teamId: teamId.value } },
		},
	]
	if (collective.value) {
		items.push({
			id: 'collective',
			label: t('circles', 'Collective'),
			icon: mdiBookOpenPageVariantOutline,
			href: collective.value.url,
		})
	}
	for (const board of boards.value) {
		items.push({
			id: `board-${board.id}`,
			label: board.title,
			icon: mdiViewDashboardOutline,
			href: board.url,
		})
	}
	for (const page of pages.value) {
		items.push({
			id: `page-${page.fileId}`,
			label: page.title,
			icon: mdiFileDocumentOutline,
			to: { name: 'team-page', params: { teamId: teamId.value, fileId: String(page.fileId) } },
			page,
		})
	}
	items.push({
		id: 'home',
		label: t('circles', 'Shared with the team'),
		icon: mdiShareVariantOutline,
		to: { name: 'team-home', params: { teamId: teamId.value } },
	})
	return items
})

/** The entries in team-level order (live order while a drag is in progress). */
const orderedEntries = computed<NavigationEntry[]>(() => {
	const order = dragOrder.value ?? entryOrder.value
	if (order.length === 0) {
		return entries.value
	}
	const position = new Map(order.map((id, index) => [id, index]))
	const known = entries.value
		.filter((entry) => position.has(entry.id))
		.sort((a, b) => position.get(a.id)! - position.get(b.id)!)
	const unknown = entries.value.filter((entry) => !position.has(entry.id))
	return [...known, ...unknown]
})

// Reordering entries changes the team-level order, so team admins and above
// only — the server enforces the same level on the save endpoint.
const canReorder = computed(() => isTeamAdmin.value)

const showTeamFolderOption = computed(() => teamFolderChecked.value
	&& teamFolder.value === null && canCreateTeamFolder(circle.value))
const showCollectiveOption = computed(() => enabledApps.collectives !== undefined
	&& isTeamAdmin.value
	&& resourcesChecked.value && collective.value === null)
// Pages live in the team folder, so the option requires one to exist.
// Creating and deleting pages is reserved for team admins and owners.
const showPageOption = computed(() => enabledApps.text !== undefined
	&& isTeamAdmin.value
	&& teamFolder.value !== null)
// A team can have any number of boards, so the option only needs the app.
const showBoardOption = computed(() => enabledApps.deck !== undefined
	&& isTeamAdmin.value)

interface CreateMenuEntry {
	id: string
	icon: string
	label: string
	action: () => void | Promise<void>
}

// The create menu is offered twice — next to the caption and as the
// "Add to team" button at the end of the list — so its entries are data.
const createMenuEntries = computed<CreateMenuEntry[]>(() => {
	const items: CreateMenuEntry[] = []
	if (showTeamFolderOption.value) {
		items.push({ id: 'folder', icon: mdiFolderPlusOutline, label: t('circles', 'Create team folder'), action: onCreateTeamFolder })
	}
	if (showCollectiveOption.value) {
		items.push({ id: 'collective', icon: mdiBookOpenPageVariantOutline, label: t('circles', 'Create collective'), action: onCreateCollective })
	}
	if (showPageOption.value) {
		items.push({ id: 'page', icon: mdiTextBoxPlusOutline, label: t('circles', 'New page'), action: onNewPageFromMenu })
	}
	if (showBoardOption.value) {
		items.push({ id: 'board', icon: mdiViewDashboardOutline, label: t('circles', 'New Deck board'), action: onNewBoardFromMenu })
	}
	return items
})

const showCreateMenu = computed(() => Boolean(circle.value?.isMember)
	&& createMenuEntries.value.length > 0)

/** Create the team folder and show its navigation entry. */
async function onCreateTeamFolder(): Promise<void> {
	creating.value = true
	try {
		await resourcesStore.createFolder(teamId.value)
		if (route.name !== 'team-folder') {
			await router.push({ name: 'team-folder', params: { teamId: teamId.value } })
		}
	} catch (error) {
		logger.error('Could not create the team folder', { error, teamId: teamId.value })
		showError(t('circles', 'Could not create the team folder'))
	} finally {
		creating.value = false
	}
}

/** Create a collective named after the team. */
async function onCreateCollective(): Promise<void> {
	creating.value = true
	try {
		const name = circle.value.sanitizedName || circle.value.name || circle.value.displayName
		if (!name) {
			throw new Error('Cannot create collective: team has no valid name')
		}
		await createCollective(name)
		// Re-fetch to pick up the new collective's URL, which makes its entry appear.
		await resourcesStore.ensureResources(teamId.value, true)
		showSuccess(t('circles', 'Collective "{name}" created and linked to the team', { name }))
	} catch (error) {
		logger.error('Could not create the collective', { error, teamId: teamId.value })
		showError(t('circles', 'Could not create the collective'))
	} finally {
		creating.value = false
	}
}

/** Show and focus the inline name input for a new page. */
async function onNewPageFromMenu(): Promise<void> {
	addingPage.value = true
	await nextTick()
	newPageInput.value?.focus?.()
}

// Dialog naming a new Deck board, summoned by the create menu.
const boardDialogOpen = ref(false)
const newBoardName = ref('')

/** Open the naming dialog for a new Deck board. */
function onNewBoardFromMenu(): void {
	newBoardName.value = ''
	boardDialogOpen.value = true
}

/** Create the Deck board named in the dialog; deck links it to the team. */
async function onCreateBoard(): Promise<void> {
	const title = newBoardName.value.trim()
	if (!title) {
		return
	}
	creating.value = true
	try {
		await createDeckBoard(teamId.value, title)
		boardDialogOpen.value = false
		// The board lands among the resources (its team share) and from
		// there among the boards, which pin it as a navigation entry.
		await resourcesStore.ensureResources(teamId.value, true)
		await resourcesStore.ensureBoards(teamId.value, true)
		showSuccess(t('circles', 'Deck board "{name}" created', { name: title }))
	} catch (error) {
		logger.error('Could not create the Deck board', { error, teamId: teamId.value })
		showError(t('circles', 'Could not create the Deck board'))
	} finally {
		creating.value = false
	}
}

/** Create the page named in the inline input. */
async function submitNewPage(): Promise<void> {
	const name = newPageName.value.trim()
	if (!name) {
		return
	}
	await onCreatePage(name)
	addingPage.value = false
	newPageName.value = ''
}

/** Dismiss the inline input without creating a page. */
function cancelNewPage(): void {
	addingPage.value = false
	newPageName.value = ''
}

/**
 * Create a team page in the team folder and show its navigation entry.
 *
 * @param name - The page name from the inline input
 */
async function onCreatePage(name: string): Promise<void> {
	name = name.trim()
	if (!name || !teamFolder.value) {
		return
	}
	if (name.includes('/')) {
		showError(t('circles', 'A page name cannot contain "/"'))
		return
	}
	creating.value = true
	try {
		await createTeamPage(teamFolder.value.mountPoint, name)
		await resourcesStore.ensurePages(teamId.value, true)
		showSuccess(t('circles', 'Page "{name}" created in the team folder', { name }))
		const created = pages.value.find((page) => page.title === name)
		if (created) {
			await router.push({ name: 'team-page', params: { teamId: teamId.value, fileId: String(created.fileId) } })
		}
	} catch (error) {
		logger.error('Could not create the team page', { error, teamId: teamId.value })
		// 412 Precondition Failed: refused to overwrite an existing page
		if ((error as { status?: number })?.status === 412) {
			showError(t('circles', 'A page named "{name}" already exists', { name }))
		} else {
			showError(t('circles', 'Could not create the page'))
		}
	} finally {
		creating.value = false
	}
}

/**
 * Rename a team page from its entry's inline edit form. The file id is
 * stable across the move, so the entry and route survive the rename.
 *
 * @param page - The team page to rename
 * @param name - The new name from the inline input
 */
async function onRenamePage(page: TeamPage, name: string): Promise<void> {
	name = name.trim()
	if (!name || name === page.title) {
		return
	}
	if (name.includes('/')) {
		showError(t('circles', 'A page name cannot contain "/"'))
		return
	}
	creating.value = true
	try {
		await renameTeamPage(page, name)
		showSuccess(t('circles', 'Page renamed to "{name}"', { name }))
		await resourcesStore.ensurePages(teamId.value, true)
	} catch (error) {
		logger.error('Could not rename the team page', { error, teamId: teamId.value })
		// 412 Precondition Failed: refused to overwrite an existing page
		if ((error as { status?: number })?.status === 412) {
			showError(t('circles', 'A page named "{name}" already exists', { name }))
		} else {
			showError(t('circles', 'Could not rename the page'))
		}
	} finally {
		creating.value = false
	}
}

/**
 * Delete a team page file after confirmation; its navigation entry
 * disappears with it.
 *
 * @param page - The team page to delete
 */
async function onDeletePage(page: TeamPage): Promise<void> {
	const confirmed = await showConfirmation({
		name: t('circles', 'Delete page'),
		text: t('circles', 'Are you sure you want to delete the page "{name}"? It can be restored from the trash bin.', { name: page.title }),
		labelConfirm: t('circles', 'Delete page'),
		labelReject: t('circles', 'Cancel'),
		severity: 'warning',
	})
	if (!confirmed) {
		return
	}
	try {
		await deleteTeamPage(page)
		showSuccess(t('circles', 'Page "{name}" deleted', { name: page.title }))
		const wasActive = route.name === 'team-page' && route.params.fileId === String(page.fileId)
		await resourcesStore.ensurePages(teamId.value, true)
		if (wasActive) {
			await router.push({ name: 'team-folder', params: { teamId: teamId.value } })
		}
	} catch (error) {
		logger.error('Could not delete the team page', { error, teamId: teamId.value })
		showError(t('circles', 'Could not delete the page'))
	}
}

/**
 * Start dragging an entry (admins and above only).
 *
 * @param entry - The dragged entry
 * @param event - The dragstart event
 */
function onEntryDragStart(entry: NavigationEntry, event: DragEvent): void {
	if (!canReorder.value) {
		return
	}
	draggedId.value = entry.id
	dragOrder.value = orderedEntries.value.map((item) => item.id)
	if (event.dataTransfer) {
		event.dataTransfer.effectAllowed = 'move'
		// Firefox refuses to start a drag without data
		event.dataTransfer.setData('text/plain', entry.id)
	}
}

/**
 * Accept drops anywhere in the navigation while a drag is in progress — a
 * rejected drop makes the browser animate the ghost snapping back to the
 * entry's original place on mouseup.
 *
 * @param event - The dragover event
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
 * Reorder live while the dragged entry hovers another entry.
 *
 * @param entry - The hovered entry
 * @param event - The dragover event
 */
function onEntryDragOver(entry: NavigationEntry, event: DragEvent): void {
	const order = dragOrder.value
	if (!order || !draggedId.value) {
		return
	}
	// Also accepts the drop on the dragged entry itself, where the pointer
	// usually sits after the live reorder moved it under the pointer.
	onListDragOver(event)
	if (entry.id === draggedId.value) {
		return
	}

	const from = order.indexOf(draggedId.value)
	const over = order.indexOf(entry.id)
	if (from === -1 || over === -1) {
		return
	}
	// Insert before or after the hovered entry depending on which half the
	// pointer is in, so the reorder follows the pointer without jitter.
	const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
	const inLowerHalf = event.clientY > rect.top + rect.height / 2
	let to = inLowerHalf ? over + 1 : over
	if (from < to) {
		to--
	}
	if (to !== from) {
		order.splice(from, 1)
		order.splice(to, 0, draggedId.value)
	}
}

/** Persist the new order when the drag ends. */
async function onEntryDragEnd(): Promise<void> {
	const order = dragOrder.value
	draggedId.value = null
	dragOrder.value = null
	if (!order || order.join('\n') === orderedEntries.value.map((entry) => entry.id).join('\n')) {
		return
	}
	try {
		// The store applies the order optimistically and rolls back on failure.
		await resourcesStore.saveOrder(teamId.value, order)
	} catch (error) {
		logger.error('Could not save the navigation order', { error, teamId: teamId.value })
		showError(t('circles', 'Could not save the navigation order'))
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
			<TeamHeader
				v-if="circle"
				class="team-navigation__team-header"
				:circle="circle" />
		</template>

		<template #list>
			<NcAppNavigationCaption
				class="team-navigation__caption"
				:name="t('circles', 'Team resources')"
				:aria-label="t('circles', 'Create team resources')">
				<template v-if="showCreateMenu" #actionsTriggerIcon>
					<NcIconSvgWrapper :path="mdiPlus" :size="20" />
				</template>
				<template v-if="showCreateMenu" #actions>
					<NcActionButton
						v-for="entry in createMenuEntries"
						:key="entry.id"
						closeAfterClick
						:disabled="creating"
						@click="entry.action">
						<template #icon>
							<NcIconSvgWrapper :path="entry.icon" :size="20" />
						</template>
						{{ entry.label }}
					</NcActionButton>

					<NcActionCaption :name="t('circles', 'More coming soon')" />
				</template>
			</NcAppNavigationCaption>

			<NcAppNavigationItem
				v-for="entry in orderedEntries"
				:key="entry.id"
				class="team-navigation__entry"
				:class="{ 'team-navigation__entry--dragging': entry.id === draggedId }"
				:name="entry.label"
				:to="entry.to"
				:href="entry.href"
				:editable="Boolean(entry.page) && isTeamAdmin"
				:editLabel="t('circles', 'Rename page')"
				:editPlaceholder="t('circles', 'Page name')"
				:draggable="canReorder"
				@update:name="onRenamePage(entry.page!, $event)"
				@dragstart="onEntryDragStart(entry, $event)"
				@dragover="onEntryDragOver(entry, $event)"
				@drop.prevent
				@dragend="onEntryDragEnd">
				<template #icon>
					<NcIconSvgWrapper :path="entry.icon" :size="20" />
				</template>

				<!-- External entries (the collective) open outside the app -->
				<template v-if="entry.href" #counter>
					<NcIconSvgWrapper inline :path="mdiArrowTopRight" :size="16" />
				</template>

				<template v-if="entry.page && isTeamAdmin" #actions>
					<NcActionButton closeAfterClick @click="onDeletePage(entry.page)">
						<template #icon>
							<NcIconSvgWrapper :path="mdiTrashCanOutline" :size="20" />
						</template>
						{{ t('circles', 'Delete page') }}
					</NcActionButton>
				</template>
			</NcAppNavigationItem>

			<li v-if="addingPage" class="team-navigation__new-page">
				<NcTextField
					ref="newPageInput"
					v-model="newPageName"
					:label="t('circles', 'Page name')"
					:disabled="creating"
					showTrailingButton
					trailingButtonIcon="arrowEnd"
					:trailingButtonLabel="t('circles', 'Create page')"
					@trailingButtonClick="submitNewPage"
					@keyup.enter="submitNewPage"
					@keyup.esc="cancelNewPage" />
			</li>

			<li v-if="showCreateMenu" class="team-navigation__add">
				<NcActions
					:menuName="t('circles', 'Add to team')"
					variant="tertiary"
					wide>
					<template #icon>
						<NcIconSvgWrapper :path="mdiPlus" :size="20" />
					</template>

					<NcActionButton
						v-for="entry in createMenuEntries"
						:key="entry.id"
						closeAfterClick
						:disabled="creating"
						@click="entry.action">
						<template #icon>
							<NcIconSvgWrapper :path="entry.icon" :size="20" />
						</template>
						{{ entry.label }}
					</NcActionButton>

					<NcActionCaption :name="t('circles', 'More coming soon')" />
				</NcActions>
			</li>
		</template>

		<!-- Members and settings pinned at the bottom, like Talk's settings entry -->
		<template #footer>
			<ul v-if="circle?.isMember" class="team-navigation__footer">
				<NcActionSeparator />

				<NcAppNavigationItem
					:name="t('circles', 'Members')"
					:to="{ name: 'team-members', params: { teamId } }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiAccountMultipleOutline" :size="20" />
					</template>
				</NcAppNavigationItem>

				<NcAppNavigationItem
					v-if="canManage"
					:name="t('circles', 'Team settings')"
					@click="settingsOpen = true">
					<template #icon>
						<NcIconSvgWrapper :path="mdiCogOutline" :size="20" />
					</template>
				</NcAppNavigationItem>
			</ul>
		</template>
	</NcAppNavigation>

	<TeamSettingsDialog
		v-if="settingsOpen && circle"
		v-model:open="settingsOpen"
		:circle="circle" />

	<NcDialog
		v-model:open="boardDialogOpen"
		:name="t('circles', 'New Deck board')"
		size="small">
		<NcTextField
			v-model="newBoardName"
			:label="t('circles', 'Board name')"
			:disabled="creating"
			@keyup.enter="onCreateBoard" />
		<template #actions>
			<NcButton :disabled="creating" @click="boardDialogOpen = false">
				{{ t('circles', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="creating || !newBoardName.trim()"
				@click="onCreateBoard">
				{{ t('circles', 'Create board') }}
			</NcButton>
		</template>
	</NcDialog>

</template>

<style lang="scss" scoped>
.team-navigation__team-header {
	padding: calc(2 * var(--default-grid-baseline));
	padding-block-end: var(--default-grid-baseline);
}

.team-navigation__entry--dragging {
	opacity: 0.4;
}

.team-navigation__new-page {
	list-style: none;
	padding: var(--default-grid-baseline) calc(2 * var(--default-grid-baseline));
}

.team-navigation__add {
	list-style: none;
	padding-block: var(--default-grid-baseline);

	// NcActions forwards `wide` to the trigger but not `alignment`, so the
	// wrapper is aligned by hand: start-aligned like the entries, nudged to
	// line the icon up with theirs, in the entries' subdued color.
	:deep(.button-vue__wrapper) {
		justify-content: start;
		margin-inline-start: -6px;
		color: var(--color-text-maxcontrast);
	}
}

.team-navigation__footer {
	padding: var(--default-grid-baseline) calc(2 * var(--default-grid-baseline)) calc(2 * var(--default-grid-baseline));
}
</style>
