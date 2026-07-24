<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { MemberCandidate } from '../types.ts'

import { mdiAccountMultiplePlusOutline, mdiMagnify } from '@mdi/js'
import { showError, showSuccess, showWarning } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'
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

/** The wizard's steps, in order. Kept as data so the template can stay generic. */
const STEPS = ['name', 'members'] as const
type WizardStep = typeof STEPS[number]

const open = ref(true)
const step = ref<WizardStep>('name')
const submitting = ref(false)

const stepIndex = computed(() => STEPS.indexOf(step.value))

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
const isNameValid = computed(() => name.value.trim().length > 0)
const nameError = computed(() => (nameTouched.value && !isNameValid.value
	? t('circles', 'Please enter a team name')
	: ''))

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

/**
 * Whether a candidate is currently part of the selection.
 *
 * @param candidate - The candidate to check
 */
function isSelected(candidate: MemberCandidate): boolean {
	return selectedMembers.value.has(candidate.key)
}

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
		const team = await store.createTeam(name.value)
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
		showError(t('circles', 'Could not create the team'))
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
			<p class="team-wizard__step-indicator">
				{{ t('circles', 'Step {current} of {total}', { current: stepIndex + 1, total: STEPS.length }) }}
			</p>

			<!-- Step 1: team name -->
			<section v-if="step === 'name'" class="team-wizard__step">
				<h3>{{ t('circles', 'Name your team') }}</h3>
				<NcTextField
					v-model="name"
					:label="t('circles', 'Team name')"
					:error="!!nameError"
					:helperText="nameError"
					:placeholder="t('circles', 'e.g. Design')" />
			</section>

			<!-- Step 2: initial member selection -->
			<section v-else-if="step === 'members'" class="team-wizard__step">
				<h3>{{ t('circles', 'Add initial members') }}</h3>
				<NcTextField
					v-model="searchQuery"
					:label="t('circles', 'Search people, groups, teams…')"
					:placeholder="t('circles', 'Optional, you can also add members later')"
					@keydown.enter.prevent>
					<template #icon>
						<NcIconSvgWrapper :path="mdiMagnify" :size="20" />
					</template>
				</NcTextField>

				<ul v-if="selectedList.length > 0" class="team-wizard__selection">
					<li v-for="candidate in selectedList" :key="candidate.key">
						<NcUserBubble
							:displayName="candidate.displayName"
							:user="candidate.isUser ? candidate.shareWith : undefined"
							@click="toggleCandidate(candidate)" />
					</li>
				</ul>

				<NcLoadingIcon v-if="searching" :size="32" />
				<NcEmptyContent
					v-else-if="candidates.length === 0"
					:name="t('circles', 'Search for people to add')"
					:description="t('circles', 'You can always add or remove members later from the team page.')">
					<template #icon>
						<NcIconSvgWrapper :path="mdiAccountMultiplePlusOutline" />
					</template>
				</NcEmptyContent>
				<ul v-else class="team-wizard__results">
					<li v-for="candidate in candidates" :key="candidate.key">
						<NcButton
							:variant="isSelected(candidate) ? 'primary' : 'tertiary'"
							@click="toggleCandidate(candidate)">
							{{ candidate.displayName }}
						</NcButton>
					</li>
				</ul>
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

	&__step-indicator {
		margin: 0;
		color: var(--color-text-maxcontrast);
	}

	&__step {
		display: flex;
		flex: 1 1 auto;
		flex-direction: column;
		gap: calc(2 * var(--default-grid-baseline));

		h3 {
			margin: 0;
		}
	}

	&__selection,
	&__results {
		display: flex;
		flex-wrap: wrap;
		gap: calc(2 * var(--default-grid-baseline));
		margin: 0;
		padding: 0;
		list-style: none;
	}
}
</style>
