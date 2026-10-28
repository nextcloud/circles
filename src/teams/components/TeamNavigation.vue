<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { NavigationEntry } from '../firstTab.ts'
import type { TeamAppAction } from '../integrations/types.ts'

import { mdiAccountMultiple, mdiAccountMultipleOutline, mdiArrowTopRight, mdiCogOutline, mdiFolder, mdiFolderOutline, mdiFolderPlusOutline, mdiPlus, mdiRefresh, mdiShareVariant, mdiShareVariantOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import TeamHeader from './TeamHeader.vue'
import TeamSettingsDialog from './TeamSettingsDialog.vue'
import { logger } from '../../logger.ts'
import { canCreateTeamFolder, useTeamActions } from '../composables/useTeamActions.ts'
import { appEntryRoute, orderEntries } from '../firstTab.ts'
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
const settingsOpen = ref(false)
const dragOrder = ref<string[] | null>(null)
const draggedId = ref<string | null>(null)
const slot = computed(() => resources.forTeam(teamId.value))

const entries = computed<NavigationEntry[]>(() => [
	{
		id: 'team-folder',
		label: t('circles', 'Team folder'),
		icon: route.name === 'team-folder' ? mdiFolder : mdiFolderOutline,
		to: { name: 'team-folder', params: { teamId: teamId.value } },
	},
	...apps.entries(teamId.value).map((entry) => ({
		...entry,
		to: entry.href ? undefined : appEntryRoute(teamId.value, entry),
	})),
	{
		id: 'home',
		label: t('circles', 'Shared with the team'),
		icon: route.name === 'team-home' ? mdiShareVariant : mdiShareVariantOutline,
		to: { name: 'team-home', params: { teamId: teamId.value } },
	},
])
const orderedEntries = computed(() => orderEntries(entries.value, dragOrder.value ?? slot.value.tabOrder))
const createMenuEntries = computed<TeamAppAction[]>(() => {
	const actions = [...apps.actions(teamId.value)]
	if (slot.value.folderChecked && !slot.value.folder && canCreateTeamFolder(circle.value)) {
		actions.unshift({
			id: 'folder',
			label: t('circles', 'Create team folder'),
			icon: mdiFolderPlusOutline,
			run: async () => {
				await resources.createFolder(teamId.value)
				await apps.refresh(teamId.value)
				await router.push({ name: 'team-folder', params: { teamId: teamId.value } })
			},
		})
	}
	return actions
})
const showCreateMenu = computed(() => Boolean(circle.value?.isMember) && createMenuEntries.value.length > 0)

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

/**
 *
 */
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

/**
 *
 */
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
				:aria-label="t('circles', 'Create team resources')">
				<template v-if="showCreateMenu" #actionsTriggerIcon>
					<NcIconSvgWrapper :path="mdiPlus" :size="20" />
				</template>
				<template v-if="showCreateMenu" #actions>
					<NcActionButton
						v-for="action in createMenuEntries"
						:key="action.id"
						closeAfterClick
						:disabled="apps.busy"
						@click="apps.run(action)">
						<template #icon>
							<NcIconSvgWrapper v-if="action.icon" :path="action.icon" :size="20" />
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
				:to="entry.to"
				:href="entry.href"
				:draggable="isTeamAdmin"
				@dragstart="onEntryDragStart(entry, $event)"
				@dragover="onEntryDragOver(entry, $event)"
				@drop.prevent
				@dragend="onEntryDragEnd">
				<template #icon>
					<NcIconSvgWrapper v-if="entry.iconSvg" :svg="entry.iconSvg" :size="20" />
					<NcIconSvgWrapper v-else-if="entry.icon" :path="entry.icon" :size="20" />
					<span v-else-if="entry.iconEmoji" class="team-navigation__entry-emoji">{{ entry.iconEmoji }}</span>
				</template>
				<template v-if="entry.href" #counter>
					<NcIconSvgWrapper inline :path="mdiArrowTopRight" :size="16" />
				</template>
				<template v-if="entry.actions?.length" #actions>
					<NcActionButton
						v-for="action in entry.actions"
						:key="action.id"
						closeAfterClick
						:disabled="apps.busy"
						@click="apps.run(action)">
						<template #icon>
							<NcIconSvgWrapper v-if="action.icon" :path="action.icon" :size="20" />
						</template>
						{{ action.label }}
					</NcActionButton>
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
			<li v-if="showCreateMenu" class="team-navigation__add">
				<NcActions :menuName="t('circles', 'Add to team')" variant="tertiary" wide>
					<template #icon>
						<NcIconSvgWrapper :path="mdiPlus" :size="20" />
					</template>
					<NcActionButton
						v-for="action in createMenuEntries"
						:key="action.id"
						closeAfterClick
						:disabled="apps.busy"
						@click="apps.run(action)">
						<template #icon>
							<NcIconSvgWrapper v-if="action.icon" :path="action.icon" :size="20" />
						</template>
						{{ action.label }}
					</NcActionButton>
				</NcActions>
			</li>
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

.team-navigation__entry-emoji {
	font-size: 20px;
	line-height: 1;
}

.team-navigation__add {
	list-style: none;
	padding-block: var(--default-grid-baseline);

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
