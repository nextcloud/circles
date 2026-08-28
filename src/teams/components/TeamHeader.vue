<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type Circle from '../team-page/models/circle.ts'
import type Member from '../team-page/models/member.ts'

import { mdiChevronDown, mdiHomeOutline, mdiLinkVariant, mdiLogout, mdiMagnify, mdiPlus, mdiTrashCanOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useElementSize, useEventListener } from '@vueuse/core'
import { computed, nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import TeamAvatar from './TeamAvatar.vue'
import TeamMembersRow from './TeamMembersRow.vue'
import { useTeamActions } from '../composables/useTeamActions.ts'
import { useTeamsStore } from '../store.ts'

const props = defineProps<{
	circle: Circle
}>()

const router = useRouter()
const store = useStore()
const teamsStore = useTeamsStore()
const team = computed(() => teamsStore.getTeam(props.circle.id))

const { canLeave, canDelete, onCopyLink, onLeave, onDelete } = useTeamActions(() => team.value)

const members = computed<Member[]>(() => Object.values(store.getters.getCircle(props.circle.id)?.members ?? {}))

// Members row: show as many 24px avatars as fit the measured width of the
// (always mounted) trigger row, and collapse the rest into a "+n" label.
// The popover replica reuses the same count; it is a touch wider than the
// trigger (no chevron), so the fit is safe there too.
const AVATAR_SLOT = 24 + 4 // avatar plus trailing gap
const MORE_LABEL_WIDTH = 36 // the "+n" label including its gap

const membersRowEl = ref<InstanceType<typeof TeamMembersRow> | null>(null)
const { width: membersRowWidth } = useElementSize(membersRowEl)

const visibleMembers = computed(() => {
	const width = membersRowWidth.value
	if (!width) {
		return members.value
	}
	// The last avatar carries no trailing gap
	if (members.value.length * AVATAR_SLOT - 4 <= width) {
		return members.value
	}
	const fitWithLabel = Math.max(1, Math.floor((width - MORE_LABEL_WIDTH) / AVATAR_SLOT))
	return members.value.slice(0, fitWithLabel)
})
const hiddenCount = computed(() => members.value.length - visibleMembers.value.length)

// Team switcher: clicking the header opens a panel wrapping the header
// itself, with a searchable list of the teams the user is part of below.
const switcherOpen = ref(false)

// The panel spans the navigation width minus an 8px inset per side, 8px
// from its top, exposed to the (teleported) popover via root CSS vars.
const SWITCHER_INSET = 8

const triggerEl = ref<HTMLElement | null>(null)
const { width: triggerWidth, height: triggerHeight } = useElementSize(triggerEl)

/** Pin the panel to the navigation box, inset by 8px on every side. */
function updateSwitcherGeometry(): void {
	const navigation = triggerEl.value?.closest('#app-navigation-vue')
	if (!navigation) {
		return
	}
	const navigationRect = navigation.getBoundingClientRect()
	const style = document.documentElement.style
	style.setProperty('--team-switcher-top', `${navigationRect.top + SWITCHER_INSET}px`)
	style.setProperty('--team-switcher-left', `${navigationRect.left + SWITCHER_INSET}px`)
	style.setProperty('--team-switcher-width', `${navigationRect.width - 2 * SWITCHER_INSET}px`)
	// Keep the panel inside the viewport on short screens; the teams list
	// scrolls within it.
	const maxHeight = window.innerHeight - navigationRect.top - 2 * SWITCHER_INSET
	style.setProperty('--team-switcher-max-height', `${maxHeight}px`)
}

// Geometry is only consumed while the panel is open: recompute when it
// opens, when the trigger resizes underneath it (sidebar width changes),
// and on window resizes that leave the trigger size untouched (height-only).
watch([triggerWidth, triggerHeight], () => {
	if (switcherOpen.value) {
		updateSwitcherGeometry()
	}
})
watch(switcherOpen, (open) => {
	if (open) {
		updateSwitcherGeometry()
	}
})
useEventListener(window, 'resize', () => {
	if (switcherOpen.value) {
		updateSwitcherGeometry()
	}
})

// The current team is represented by the panel's header, not the list.
const otherTeams = computed(() => teamsStore.teams.filter((item) => item.id !== props.circle.id))

// Teams list search: the "Go to team" caption row swaps for a text field
// that filters the list; aborting the search restores the caption row.
const searchOpen = ref(false)
const searchQuery = ref('')
const searchFieldEl = ref<InstanceType<typeof NcTextField> | null>(null)
const searchButtonEl = ref<InstanceType<typeof NcButton> | null>(null)

const filteredTeams = computed(() => {
	const query = searchQuery.value.trim().toLowerCase()
	if (!query) {
		return otherTeams.value
	}
	return otherTeams.value.filter((item) => item.displayName.toLowerCase().includes(query))
})

// Reopen the panel with the caption row and the unfiltered list
watch(switcherOpen, (open) => {
	if (!open) {
		searchOpen.value = false
		searchQuery.value = ''
	}
})

/** Swap the caption row for the search field and focus it. */
async function openSearch(): Promise<void> {
	searchOpen.value = true
	await nextTick()
	searchFieldEl.value?.focus()
}

/** Abort the search: restore the caption row and the unfiltered list. */
async function closeSearch(): Promise<void> {
	searchOpen.value = false
	searchQuery.value = ''
	await nextTick()
	searchButtonEl.value?.$el.focus()
}

/** Open the teams overview from the panel. */
async function openHome(): Promise<void> {
	switcherOpen.value = false
	await router.push({ name: 'home' })
}

/** Open the team creation wizard from the panel. */
function onNewTeam(): void {
	switcherOpen.value = false
	teamsStore.createWizardOpen = true
}

/** Open the team's members page from the panel. */
async function openMembersPage(): Promise<void> {
	switcherOpen.value = false
	await router.push({ name: 'team-members', params: { teamId: props.circle.id } })
}

</script>

<template>
	<div class="team-header">
		<NcPopover
			popupRole="dialog"
			:shown="switcherOpen"
			popoverBaseClass="team-switcher-popover"
			@update:shown="switcherOpen = $event">
			<template #trigger>
				<button
					ref="triggerEl"
					type="button"
					class="team-header__trigger"
					:title="t('circles', 'Switch team')"
					:aria-label="t('circles', 'Switch team')"
					@click="switcherOpen = true">
					<TeamAvatar
						:displayName="circle.displayName"
						:circleId="circle.id"
						:size="44" />

					<span class="team-header__info">
						<h2 class="team-header__name" :title="circle.displayName">
							{{ circle.displayName }}
						</h2>

						<!-- Purely visual here (a button cannot nest a button);
							the popover replica overlaying it is clickable. -->
						<TeamMembersRow
							v-if="team && members.length > 0"
							ref="membersRowEl"
							:members="visibleMembers"
							:hiddenCount="hiddenCount" />
					</span>

					<NcIconSvgWrapper
						class="team-header__chevron"
						:path="mdiChevronDown"
						:size="20" />
				</button>
			</template>

			<div class="team-header__switcher">
				<!-- Replica of the trigger, so the open panel visually wraps
					the header component. -->
				<div class="team-header__switcher-current">
					<TeamAvatar
						:displayName="circle.displayName"
						:circleId="circle.id"
						:size="44" />

					<div class="team-header__info">
						<span class="team-header__name" :title="circle.displayName">
							{{ circle.displayName }}
						</span>

						<button
							v-if="team && members.length > 0"
							type="button"
							class="team-header__members-button"
							:title="t('circles', 'Show members')"
							:aria-label="t('circles', 'Show members')"
							@click="openMembersPage">
							<TeamMembersRow
								:members="visibleMembers"
								:hiddenCount="hiddenCount" />
						</button>
					</div>

					<NcActions
						v-if="team"
						class="team-header__switcher-actions"
						:aria-label="t('circles', 'Team actions')">
						<NcActionButton closeAfterClick @click="onCopyLink">
							<template #icon>
								<NcIconSvgWrapper :path="mdiLinkVariant" :size="20" />
							</template>
							{{ t('circles', 'Copy team link') }}
						</NcActionButton>
						<NcActionButton
							v-if="canLeave"
							closeAfterClick
							@click="onLeave">
							<template #icon>
								<NcIconSvgWrapper :path="mdiLogout" :size="20" />
							</template>
							{{ t('circles', 'Leave team') }}
						</NcActionButton>
						<NcActionButton
							v-if="canDelete"
							closeAfterClick
							@click="onDelete">
							<template #icon>
								<NcIconSvgWrapper :path="mdiTrashCanOutline" :size="20" />
							</template>
							{{ t('circles', 'Delete team') }}
						</NcActionButton>
					</NcActions>
				</div>

				<hr class="team-header__switcher-divider team-header__switcher-divider--caption">

				<div v-if="!searchOpen" class="team-header__switcher-caption">
					<span class="team-header__switcher-caption-text">
						{{ t('circles', 'Go to team') }}
					</span>
					<NcButton
						ref="searchButtonEl"
						variant="tertiary"
						:title="t('circles', 'Search teams')"
						:aria-label="t('circles', 'Search teams')"
						@click="openSearch">
						<template #icon>
							<NcIconSvgWrapper :path="mdiMagnify" :size="20" />
						</template>
					</NcButton>
				</div>

				<NcTextField
					v-else
					ref="searchFieldEl"
					v-model="searchQuery"
					class="team-header__switcher-search"
					:label="t('circles', 'Search teams')"
					showTrailingButton
					trailingButtonIcon="close"
					:trailingButtonLabel="t('circles', 'Cancel search')"
					@trailingButtonClick="closeSearch"
					@keydown.esc.stop.prevent="closeSearch" />

				<ul class="team-header__switcher-list">
					<NcListItem
						v-for="item in filteredTeams"
						:key="item.id"
						:name="item.displayName"
						:to="{ name: 'team', params: { teamId: item.id } }"
						oneLine
						@click="switcherOpen = false">
						<template #icon>
							<TeamAvatar
								:displayName="item.displayName"
								:circleId="item.id"
								:size="32" />
						</template>
					</NcListItem>
				</ul>

				<hr class="team-header__switcher-divider">

				<div class="team-header__switcher-footer">
					<NcButton
						variant="tertiary"
						:title="t('circles', 'Home')"
						:aria-label="t('circles', 'Home')"
						@click="openHome">
						<template #icon>
							<NcIconSvgWrapper :path="mdiHomeOutline" :size="20" />
						</template>
					</NcButton>

					<NcButton
						class="team-header__switcher-new"
						variant="secondary"
						@click="onNewTeam">
						<template #icon>
							<NcIconSvgWrapper :path="mdiPlus" :size="20" />
						</template>
						{{ t('circles', 'New team') }}
					</NcButton>
				</div>
			</div>
		</NcPopover>
	</div>
</template>

<style lang="scss" scoped>
.team-header {
	display: flex;
	align-items: center;
	gap: var(--default-grid-baseline);

	// The popover wraps its trigger in a .v-popper element — stretch it so
	// the trigger button can span the full header width.
	:deep(.v-popper) {
		flex: 1 1 auto;
		min-width: 0;
	}

	&__trigger {
		width: 100%;
		display: flex;
		align-items: center;
		gap: calc(3 * var(--default-grid-baseline));
		min-width: 0;
		margin: 0;
		padding: var(--default-grid-baseline);
		background: none;
		border: none;
		border-radius: var(--border-radius-element);
		text-align: start;
		cursor: pointer;

		// No visual feedback at all — the pointer cursor is the affordance.
		// The server's global button styles paint hovered/focused/active
		// buttons, so force every state back to transparent.
		&:hover,
		&:focus,
		&:focus-visible,
		&:active {
			background-color: transparent !important;
		}
	}

	&__info {
		flex: 1 1 auto;
		display: flex;
		flex-direction: column;
		min-width: 0;
	}

	&__name {
		flex: 0 1 auto;
		font-size: var(--default-font-size);
		font-weight: bold;
		margin: 0;
		min-width: 0;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	&__chevron.icon-vue {
		flex: 0 0 auto;
		min-width: unset;
		min-height: unset;
	}

	&__members-button {
		display: flex;
		min-width: 0;
		width: 100%;
		// The server's global button styles give buttons a margin
		margin: 0 !important;
		padding: 0;
		background: none;
		border: none;
		border-radius: var(--border-radius-element);
		cursor: pointer;

		// Like the trigger: the pointer cursor is the only affordance.
		&:hover,
		&:focus,
		&:focus-visible,
		&:active {
			background-color: transparent !important;
		}

		// Underline the "+n" label only here, where the row is clickable
		:deep(.team-members-row__more) {
			text-decoration: underline;
		}
	}

	&__switcher {
		display: flex;
		flex-direction: column;
		gap: var(--default-grid-baseline);
		width: 100%;
		max-height: var(--team-switcher-max-height, 80vh);
		padding: calc(2 * var(--default-grid-baseline));
	}

	&__switcher-actions {
		flex: 0 0 auto;
		margin-inline-start: auto;
	}

	&__switcher-current {
		display: flex;
		align-items: center;
		gap: calc(3 * var(--default-grid-baseline));
		width: 100%;
		min-width: 0;
	}

	&__switcher-footer {
		display: flex;
		align-items: center;
		gap: var(--default-grid-baseline);
		width: 100%;
	}

	&__switcher-new {
		flex: 1 1 auto;
	}

	&__switcher-caption {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: var(--default-grid-baseline);
		width: 100%;
		// Mirror the search field's geometry (NcInputField is one clickable
		// area tall plus a 6px block-start margin reserved for its floating
		// label), so swapping the caption for the field does not shift the
		// rows around it.
		min-height: var(--default-clickable-area);
		margin-block-start: 6px;
		margin-inline-start: var(--default-grid-baseline);
	}

	&__switcher-caption-text {
		font-size: var(--default-font-size);
		font-weight: var(--font-weight-heading, bold);
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	&__switcher-divider {
		width: 100%;
		border: none;
		border-top: 1px solid var(--color-border);
		margin: var(--default-grid-baseline) 0;

		&--caption {
			margin-block-end: 0;
		}
	}

	&__switcher-list {
		flex: 1 1 auto;
		min-height: 0;
		margin: 0;
		padding: 0;
		list-style: none;
		max-height: 300px;
		overflow-y: auto;

		// Align the items' hover pills with the panel's 4px padding
		:deep(.list-item__wrapper) {
			padding-inline: 0;
		}
	}
}
</style>

<style lang="scss">
.team-switcher-popover {
	// Pin the panel to the navigation box ourselves, overriding the
	// popper's inline transform; its show/hide lifecycle stays untouched.
	position: fixed !important;
	top: var(--team-switcher-top, 0) !important;
	left: var(--team-switcher-left, 0) !important;
	transform: none !important;
	width: var(--team-switcher-width, 300px);

	.v-popper__arrow-container {
		display: none;
	}
}
</style>
