<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { MemberCandidate } from '../types.ts'

import { mdiAccountMultiplePlusOutline, mdiChevronDown, mdiChevronRight, mdiClose, mdiMagnify } from '@mdi/js'
import { showError, showSuccess, showWarning } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcChip from '@nextcloud/vue/components/NcChip'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { logger } from '../../logger.ts'
import { useTeamsStore } from '../store.ts'

/** Minimal shape accepted by `NcDialog`'s `buttons` prop. */
interface DialogButton {
	label: string
	type?: 'button' | 'submit'
	variant?: 'primary' | 'secondary' | 'tertiary'
	disabled?: boolean
	/** Return `false` to keep the dialog open (e.g. to move to the next step). */
	callback?: () => unknown | false | Promise<unknown | false>
}

const emit = defineEmits<{
	close: []
}>()

const router = useRouter()
const store = useTeamsStore()

type WizardStep = 'name' | 'members'

const open = ref(true)
const step = ref<WizardStep>('name')
const submitting = ref(false)

// `NcDialog` owns the `open` model; forward its close (✕ button, escape,
// backdrop click, "Cancel") to the parent so it can drop this component.
watch(open, (value) => {
	if (!value) {
		emit('close')
	}
})

// --- Step 1: team name ------------------------------------------------------

const name = ref('')
const nameTouched = ref(false)
// The server rejects names shorter than 3 characters.
const NAME_MIN_LENGTH = 3
const isNameValid = computed(() => name.value.trim().length >= NAME_MIN_LENGTH)
const nameError = computed(() => {
	if (!nameTouched.value || isNameValid.value) {
		return ''
	}
	if (name.value.trim().length === 0) {
		return t('circles', 'Please enter a team name')
	}
	return t('circles', 'The team name must be at least {min} characters long', { min: NAME_MIN_LENGTH })
})

/** Global provisioning flag; when off, the wizard skip option is irrelevant. */
const teamFolderProvisioningEnabled = Boolean(loadState('circles', 'teamFolderProvisioningEnabled', true))
/** Default on: teams are meant to come with a space. Advanced on step 1 can opt out. */
const createTeamFolder = ref(true)
const showAdvanced = ref(false)

// --- Step 2: initial member selection (restored legacy feature) ------------

const searchQuery = ref('')
const searching = ref(false)
const candidates = ref<MemberCandidate[]>([])
const selectedMembers = ref<Map<string, MemberCandidate>>(new Map())
const selectedList = computed(() => Array.from(selectedMembers.value.values()))

let searchTimeout: ReturnType<typeof setTimeout> | undefined

/**
 * Debounce the sharee search so we don't hit the API on every keystroke.
 *
 * @param term - The search query
 */
function scheduleSearch(term: string): void {
	clearTimeout(searchTimeout)
	searchTimeout = setTimeout(async () => {
		searching.value = true
		try {
			candidates.value = await store.searchMemberCandidates(term)
		} catch (error) {
			logger.error('Failed to search for members', { error })
		} finally {
			searching.value = false
		}
	}, 300)
}

watch(searchQuery, (term) => scheduleSearch(term))

/**
 * Toggle a candidate in/out of the current selection.
 *
 * @param candidate - The candidate to toggle
 */
function toggleCandidate(candidate: MemberCandidate): void {
	const next = new Map(selectedMembers.value)
	if (next.has(candidate.key)) {
		next.delete(candidate.key)
	} else {
		next.set(candidate.key, candidate)
	}
	selectedMembers.value = next
}

/** Clear the search and show the default recommendations again. */
function clearSearch(): void {
	searchQuery.value = ''
}

/**
 * Group caption for a sharee type, mirroring the sections of Talk's
 * participant selector ("Add users", "Add groups", …).
 *
 * @param shareType - The sharee share type
 */
function candidateGroupCaption(shareType: number): string {
	switch (shareType) {
		case 0:
			return t('circles', 'Add users')
		case 1:
			return t('circles', 'Add groups')
		case 4:
			return t('circles', 'Add emails')
		case 7:
			return t('circles', 'Add teams')
		default:
			return t('circles', 'Other')
	}
}

/**
 * Search results grouped by sharee type, with already selected candidates
 * filtered out (they are shown as chips above the list).
 */
const groupedCandidates = computed(() => {
	const groups: { caption: string, items: MemberCandidate[] }[] = []
	for (const candidate of candidates.value) {
		if (selectedMembers.value.has(candidate.key)) {
			continue
		}
		const caption = candidateGroupCaption(candidate.shareType)
		let group = groups.find((existing) => existing.caption === caption)
		if (!group) {
			group = { caption, items: [] }
			groups.push(group)
		}
		group.items.push(candidate)
	}
	return groups
})

// --- Wizard navigation / submission -----------------------------------------

/**
 * Advance from the name step to the member step, validating the name first.
 * Always returns `false` so the dialog stays open (this is a "Next" action).
 */
function goToMembers(): false {
	if (!isNameValid.value) {
		nameTouched.value = true
		return false
	}
	step.value = 'members'
	scheduleSearch(searchQuery.value)
	return false
}

/**
 * Move back to the name step. Always returns `false` so the dialog stays open.
 */
function goToName(): false {
	step.value = 'name'
	return false
}

/**
 * Create the team, add any picked members and navigate in. Returns `false` to
 * keep the dialog open on failure so the user can retry; on success it
 * closes the dialog itself (the `open` watcher then notifies the parent).
 */
async function createTeam(): Promise<false | void> {
	if (!isNameValid.value || submitting.value) {
		return false
	}

	submitting.value = true
	try {
		const team = await store.createTeam(name.value, createTeamFolder.value)
		if (!team) {
			throw new Error('Team creation did not return a team')
		}

		if (selectedList.value.length > 0) {
			try {
				const added = await store.addTeamMembers(team.id, selectedList.value)
				if (added < selectedList.value.length) {
					showWarning(t('circles', 'Some members could not be added to the team'))
				}
			} catch (error) {
				logger.error('Failed to add initial members', { error })
				showWarning(t('circles', 'Team created, but the initial members could not be added'))
			}
		}

		showSuccess(t('circles', 'Team "{name}" created', { name: name.value.trim() }))
		router.push({ name: 'team', params: { teamId: team.id } })
		open.value = false
	} catch (error) {
		logger.error('Failed to create team', { error })
		// Prefer the server's reason (e.g. a name rejected after cleaning)
		// over the generic message.
		const serverMessage = (error as { response?: { data?: { ocs?: { meta?: { message?: string } } } } })
			?.response?.data?.ocs?.meta?.message
		showError(serverMessage || t('circles', 'Could not create the team'))
		return false
	} finally {
		submitting.value = false
	}
}

/** The dialog's footer buttons, adapted to the current step. */
const buttons = computed<DialogButton[]>(() => {
	const cancelButton: DialogButton = {
		label: t('circles', 'Cancel'),
		disabled: submitting.value,
	}

	if (step.value === 'name') {
		return [
			cancelButton,
			{
				label: t('circles', 'Next'),
				type: 'submit',
				variant: 'primary',
				callback: goToMembers,
			},
		]
	}

	return [
		{
			label: t('circles', 'Back'),
			disabled: submitting.value,
			callback: goToName,
		},
		cancelButton,
		{
			label: t('circles', 'Create team'),
			type: 'submit',
			variant: 'primary',
			callback: createTeam,
		},
	]
})

/** Handle the form's native submit event (e.g. pressing enter in the name field). */
function onFormSubmit(): void {
	if (step.value === 'name') {
		goToMembers()
	} else {
		createTeam()
	}
}
</script>

<template>
	<NcDialog
		v-model:open="open"
		isForm
		size="normal"
		:name="t('circles', 'Create a new team')"
		:buttons="buttons"
		@submit="onFormSubmit">
		<div class="team-wizard">
			<!-- Step 1: team name + optional team folder -->
			<section v-if="step === 'name'" class="team-wizard__step">
				<h3>{{ t('circles', 'Name your team') }}</h3>
				<NcTextField
					v-model="name"
					:label="t('circles', 'Team name')"
					:error="!!nameError"
					:helperText="nameError"
					:placeholder="t('circles', 'e.g. Design')" />

				<div v-if="teamFolderProvisioningEnabled" class="team-wizard__advanced">
					<NcButton
						variant="tertiary"
						:aria-expanded="showAdvanced ? 'true' : 'false'"
						@click="showAdvanced = !showAdvanced">
						<template #icon>
							<NcIconSvgWrapper :path="showAdvanced ? mdiChevronDown : mdiChevronRight" :size="20" />
						</template>
						{{ t('circles', 'Advanced') }}
					</NcButton>
					<div v-if="showAdvanced" class="team-wizard__advanced-body">
						<NcCheckboxRadioSwitch v-model="createTeamFolder">
							{{ t('circles', 'Create a team space') }}
						</NcCheckboxRadioSwitch>
						<p class="team-wizard__hint">
							{{ t('circles', 'A shared folder for this team. You can also add one later from the team page.') }}
						</p>
					</div>
				</div>
			</section>

			<!-- Step 2: initial member selection, modeled after Talk's
				participant selector in the new conversation dialog. -->
			<section v-else-if="step === 'members'" class="team-wizard__step">
				<h3>{{ t('circles', 'Add initial members') }}</h3>
				<NcTextField
					v-model="searchQuery"
					:label="t('circles', 'Search people, groups, teams…')"
					:showTrailingButton="searchQuery !== ''"
					:trailingButtonLabel="t('circles', 'Cancel search')"
					@trailingButtonClick="clearSearch"
					@keydown.enter.prevent>
					<template #icon>
						<NcIconSvgWrapper :path="mdiMagnify" :size="20" />
					</template>
					<template #trailing-button-icon>
						<NcIconSvgWrapper :path="mdiClose" :size="20" />
					</template>
				</NcTextField>

				<div v-if="selectedList.length > 0" class="team-wizard__selection">
					<NcChip
						v-for="candidate in selectedList"
						:key="candidate.key"
						:text="candidate.displayName"
						:ariaLabelClose="t('circles', 'Remove {name}', { name: candidate.displayName })"
						@close="toggleCandidate(candidate)">
						<template #icon>
							<NcAvatar
								:user="candidate.isUser ? candidate.shareWith : undefined"
								:displayName="candidate.displayName"
								:isNoUser="!candidate.isUser"
								:size="24"
								disableMenu
								hideStatus />
						</template>
					</NcChip>
				</div>

				<NcLoadingIcon v-if="searching" class="team-wizard__loading" :size="32" />
				<NcEmptyContent
					v-else-if="groupedCandidates.length === 0"
					:name="t('circles', 'Search for people to add')"
					:description="t('circles', 'You can always add or remove members later from the team page.')">
					<template #icon>
						<NcIconSvgWrapper :path="mdiAccountMultiplePlusOutline" />
					</template>
				</NcEmptyContent>
				<div v-else class="team-wizard__results">
					<template v-for="group in groupedCandidates" :key="group.caption">
						<div class="team-wizard__caption">
							{{ group.caption }}
						</div>
						<ul class="team-wizard__result-list">
							<NcListItem
								v-for="candidate in group.items"
								:key="candidate.key"
								:name="candidate.displayName"
								compact
								@click="toggleCandidate(candidate)">
								<template #icon>
									<NcAvatar
										:user="candidate.isUser ? candidate.shareWith : undefined"
										:displayName="candidate.displayName"
										:isNoUser="!candidate.isUser"
										:size="32"
										disableMenu
										hideStatus />
								</template>
							</NcListItem>
						</ul>
					</template>
				</div>
			</section>
		</div>
	</NcDialog>
</template>

<style scoped lang="scss">
.team-wizard {
	display: flex;
	flex-direction: column;
	gap: calc(3 * var(--default-grid-baseline));
	box-sizing: border-box;
	min-height: 280px;

	&__step {
		display: flex;
		flex: 1 1 auto;
		flex-direction: column;
		gap: calc(2 * var(--default-grid-baseline));

		h3 {
			margin: 0;
		}
	}

	// Selected members as chips, capped and scrollable like Talk's
	// participant selector.
	&__selection {
		display: flex;
		flex-wrap: wrap;
		align-content: flex-start;
		gap: var(--default-grid-baseline);
		flex-shrink: 0;
		max-height: 97px;
		overflow-y: auto;
		padding: var(--default-grid-baseline) 0;
		border-bottom: 1px solid var(--color-background-darker);
	}

	&__loading {
		margin-block: calc(4 * var(--default-grid-baseline));
	}

	&__results {
		flex: 1 1 auto;
		min-height: 0;
		max-height: 300px;
		overflow-y: auto;
	}

	&__caption {
		padding: calc(2 * var(--default-grid-baseline)) var(--default-grid-baseline) var(--default-grid-baseline);
		color: var(--color-primary-element);
		font-weight: bold;
	}

	&__result-list {
		margin: 0;
		padding: 0;
		list-style: none;
	}

	&__advanced {
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		gap: calc(2 * var(--default-grid-baseline));
		margin-block-start: auto;
	}

	&__advanced-body {
		display: flex;
		flex-direction: column;
		gap: var(--default-grid-baseline);
		padding-inline-start: calc(2 * var(--default-grid-baseline));
	}

	&__hint {
		margin: 0;
		color: var(--color-text-maxcontrast);
	}
}
</style>
