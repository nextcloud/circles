<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { NcSelectUsersModel } from '@nextcloud/vue/components/NcSelectUsers'
import type { Component } from 'vue'
import type { Member, RichObject, TeamActivity } from '../types.ts'

import { mdiAccountMultipleOutline, mdiHistory } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useDebounceFn } from '@vueuse/core'
import { computed, defineComponent, h, ref, useId, watch } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcRichText from '@nextcloud/vue/components/NcRichText'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'
import CalendarRange from 'vue-material-design-icons/CalendarRange.vue'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import { logger } from '../../logger.ts'
import { fetchTeamActivities, fetchTeamMembers } from '../api.ts'

const props = defineProps<{
	teamId: string
}>()

const activities = ref<TeamActivity[]>([])
const members = ref<Member[]>([])
const loading = ref(false)
const loadingMore = ref(false)
const failed = ref(false)
const nextSince = ref<number>()
let activityRequestId = 0
const search = ref('')
const searchInput = ref('')
const actor = ref('')
const from = ref<Date | null>(null)
const to = ref<Date | null>(null)
const actorInputId = useId()
const fromInputId = useId()
const toInputId = useId()

const MIN_SEARCH_TERM_LENGTH = 2
const MAX_SEARCH_TERM_LENGTH = 255
const SEARCH_DEBOUNCE_MS = 300
type PresetId = 'any' | 'today' | 'week' | 'month' | 'custom'

interface ActorOption {
	id: string
	displayName: string
	user: string
}

interface ActivityDayGroup {
	key: string
	date: Date
	activities: TeamActivity[]
}

const RichTextObject = defineComponent({
	props: {
		link: { type: String, default: '' },
		name: { type: String, required: true },
	},

	setup(props) {
		return () => props.link === ''
			? h('strong', props.name)
			: h('a', { href: props.link }, props.name)
	},
})

const today = new Date()
const presets = computed(() => [
	{ id: 'any' as const, label: t('circles', 'Any time') },
	{ id: 'today' as const, label: t('circles', 'Today') },
	{ id: 'week' as const, label: t('circles', 'Last 7 days') },
	{ id: 'month' as const, label: t('circles', 'Last 30 days') },
	{ id: 'custom' as const, label: t('circles', 'Custom range') },
])
const selectedPreset = ref<PresetId>('any')

const actorOptions = computed<ActorOption[]>(() => members.value
	.filter((member) => member.userId !== null)
	.map((member) => ({
		id: member.userId as string,
		displayName: member.displayName,
		user: member.userId as string,
	})))

const selectedActorOption = computed<ActorOption | undefined>(() => actor.value === ''
	? undefined
	: actorOptions.value.find((option) => option.id === actor.value) ?? {
		id: actor.value,
		displayName: actor.value,
		user: actor.value,
	})

const groupedActivities = computed<ActivityDayGroup[]>(() => {
	const groups = new Map<string, ActivityDayGroup>()
	for (const activity of activities.value) {
		const date = new Date(activity.datetime)
		const key = getDateKey(date)
		const group = groups.get(key)
		if (group === undefined) {
			groups.set(key, { key, date, activities: [activity] })
		} else {
			group.activities.push(activity)
		}
	}
	return [...groups.values()]
})

const dateRangeLabel = computed(() => {
	if (selectedPreset.value !== 'custom') {
		return presets.value.find((preset) => preset.id === selectedPreset.value)?.label ?? t('circles', 'Any time')
	}
	if (from.value !== null && to.value !== null) {
		return t('circles', '{from} to {to}', { from: from.value.toLocaleDateString(), to: to.value.toLocaleDateString() })
	}
	if (from.value !== null) {
		return t('circles', 'Since {from}', { from: from.value.toLocaleDateString() })
	}
	if (to.value !== null) {
		return t('circles', 'Until {to}', { to: to.value.toLocaleDateString() })
	}
	return t('circles', 'Custom range')
})

const hasActiveFilters = computed(() => search.value !== '' || actor.value !== '' || from.value !== null || to.value !== null)

const searchHelperText = computed(() => {
	const term = searchInput.value.trim()
	return term.length > 0 && term.length < MIN_SEARCH_TERM_LENGTH
		? t('circles', 'Enter at least {count} characters to search', { count: MIN_SEARCH_TERM_LENGTH })
		: ''
})

/**
 * Convert a local date to a Unix timestamp for Activity's filters.
 *
 * @param value - A selected date
 * @param endOfDay - Whether to use the end instead of the start of the day
 */
function toTimestamp(value: Date | null, endOfDay = false): number | undefined {
	if (value === null || Number.isNaN(value.getTime())) {
		return undefined
	}
	const timestamp = new Date(value)
	timestamp.setHours(endOfDay ? 23 : 0, endOfDay ? 59 : 0, endOfDay ? 59 : 0, endOfDay ? 999 : 0)
	return Math.floor(timestamp.getTime() / 1000)
}

/**
 * Return a sortable key for a local calendar day.
 *
 * @param date - The date to normalise
 */
function getDateKey(date: Date): string {
	return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

/**
 * Format an Activity-style heading for a day group.
 *
 * @param date - The group's representative date
 */
function getDayHeading(date: Date): string {
	const now = new Date()
	const yesterday = new Date(now)
	yesterday.setDate(now.getDate() - 1)
	if (getDateKey(date) === getDateKey(now)) {
		return t('activity', 'Today')
	}
	if (getDateKey(date) === getDateKey(yesterday)) {
		return t('activity', 'Yesterday')
	}
	return date.toLocaleDateString(undefined, { dateStyle: 'long' })
}

/**
 * Return local midnight a given number of days before today.
 *
 * @param days - The number of days to go back
 */
function daysAgo(days: number): Date {
	const date = new Date()
	date.setHours(0, 0, 0, 0)
	date.setDate(date.getDate() - days)
	return date
}

/**
 * Normalise a search term so it is accepted by Activity's server-side criteria.
 *
 * @param value - The raw search input
 */
function normalizeSearch(value: string): string {
	const term = value.trim()
	return term.length < MIN_SEARCH_TERM_LENGTH ? '' : term.slice(0, MAX_SEARCH_TERM_LENGTH)
}

/**
 * Update the account filter from the Activity-style account picker.
 *
 * @param option - The selected account option
 */
function onActorChange(option: NcSelectUsersModel | NcSelectUsersModel[]): void {
	const selected = Array.isArray(option) ? option[0] : option
	actor.value = selected?.id ?? ''
}

/**
 * Get rich objects for an Activity text field.
 *
 * @param rich - The Activity rich text pair
 */
function getRichObjects(rich: TeamActivity['subject_rich']): Record<string, RichObject> {
	return rich !== undefined && !Array.isArray(rich[1]) ? rich[1] : {}
}

/**
 * Convert Activity rich objects to the components NcRichText renders inline.
 *
 * @param richObjects - The objects embedded in a subject or message
 */
function mapRichObjects(richObjects: Record<string, RichObject>): Record<string, { component: Component, props: RichObject | Record<string, string | boolean> }> {
	return Object.fromEntries(Object.entries(richObjects).map(([key, richObject]) => {
		if (richObject.type === 'user') {
			return [key, {
				component: NcUserBubble,
				props: richObject.server === undefined
					? { displayName: richObject.name, user: String(richObject.id), url: richObject.link ?? '' }
					: { avatarImage: 'icon-user', displayName: richObject.name, user: String(richObject.id), url: richObject.link ?? '' },
			}]
		}
		return [key, { component: RichTextObject, props: richObject }]
	}))
}

/**
 * Return a rich template when available, otherwise the plain Activity text.
 *
 * @param plain - The plain text fallback
 * @param rich - The optional rich text pair
 */
function getRichText(plain: string, rich: TeamActivity['subject_rich']): string {
	return rich?.[0] || plain
}

/**
 * Circle events describe Team roster changes and need a visible members icon.
 *
 * @param activity - The Activity entry to classify
 */
function isRosterActivity(activity: TeamActivity): boolean {
	return activity.object_type === 'circles'
}

/**
 * Format a date for a native date input without converting it to UTC.
 *
 * @param value - The date to format
 */
function formatDateInput(value: Date | null): string {
	if (value === null) {
		return ''
	}
	const year = value.getFullYear()
	const month = String(value.getMonth() + 1).padStart(2, '0')
	const day = String(value.getDate()).padStart(2, '0')
	return `${year}-${month}-${day}`
}

/**
 * Parse the native date input's local ISO value.
 *
 * @param value - The input value to parse
 */
function parseDateInput(value: string): Date | null {
	if (value === '') {
		return null
	}
	const [year, month, day] = value.split('-').map(Number)
	const date = new Date(year, month - 1, day)
	return Number.isNaN(date.getTime()) ? null : date
}

/**
 * Update the lower bound of the custom date range.
 *
 * @param event - The native date input event
 */
function onFromInput(event: Event): void {
	from.value = parseDateInput((event.target as HTMLInputElement).value)
}

/**
 * Update the upper bound of the custom date range.
 *
 * @param event - The native date input event
 */
function onToInput(event: Event): void {
	to.value = parseDateInput((event.target as HTMLInputElement).value)
}

/**
 * Apply a predefined date range or open the custom date controls.
 *
 * @param id - The selected date preset
 */
function applyPreset(id: PresetId): void {
	if (selectedPreset.value === id) {
		return
	}
	selectedPreset.value = id
	if (id === 'custom') {
		return
	}
	to.value = null
	from.value = id === 'any' ? null : daysAgo({ today: 0, week: 6, month: 29 }[id])
}

/** Reset all Activity-specific filters. */
function clearFilters(): void {
	searchInput.value = ''
	search.value = ''
	actor.value = ''
	from.value = null
	to.value = null
	selectedPreset.value = 'any'
}

/** Load the selectable Team members without blocking the activity stream. */
async function loadMembers(): Promise<void> {
	try {
		members.value = await fetchTeamMembers(props.teamId)
	} catch (error) {
		logger.warn('Could not load team members for activity filters', { error, teamId: props.teamId })
		members.value = []
	}
}

/**
 * Load the activity stream for the current team.
 *
 * @param append - Whether to request the next cursor page
 */
async function loadActivities(append = false): Promise<void> {
	const requestId = ++activityRequestId
	if (append) {
		loadingMore.value = true
	} else {
		loading.value = true
		failed.value = false
	}
	try {
		const page = await fetchTeamActivities(props.teamId, {
			actor: actor.value || undefined,
			from: toTimestamp(from.value),
			to: toTimestamp(to.value, true),
			search: search.value || undefined,
			since: append ? nextSince.value : undefined,
		})
		if (requestId !== activityRequestId) {
			return
		}
		activities.value = append ? [...activities.value, ...page.activities] : page.activities
		nextSince.value = page.nextSince
	} catch (error) {
		if (requestId !== activityRequestId) {
			return
		}
		logger.error('Could not load team activity', { error, teamId: props.teamId })
		if (!append) {
			activities.value = []
		}
		failed.value = true
	} finally {
		if (requestId === activityRequestId) {
			loading.value = false
			loadingMore.value = false
		}
	}
}

const updateSearch = useDebounceFn((value: string) => {
	search.value = normalizeSearch(value)
}, SEARCH_DEBOUNCE_MS)

watch(searchInput, (value) => updateSearch(value))
watch([search, actor, from, to], () => {
	nextSince.value = undefined
	void loadActivities()
})

watch(() => props.teamId, () => {
	nextSince.value = undefined
	void Promise.all([loadActivities(), loadMembers()])
}, { immediate: true })
</script>

<template>
	<div class="team-activity">
		<div class="team-activity__filters">
			<NcTextField
				v-model="searchInput"
				class="team-activity__search"
				type="search"
				:label="t('circles', 'Search activity')"
				:showTrailingButton="searchInput !== ''"
				:trailingButtonLabel="t('circles', 'Clear search')"
				trailingButtonIcon="close"
				:helperText="searchHelperText"
				@trailingButtonClick="searchInput = ''">
				<template #icon>
					<Magnify :size="20" />
				</template>
			</NcTextField>

			<NcSelectUsers
				v-if="actorOptions.length > 0"
				class="team-activity__actor"
				:inputId="actorInputId"
				labelOutside
				:options="actorOptions"
				:modelValue="selectedActorOption"
				:placeholder="t('circles', 'Anyone')"
				@update:modelValue="onActorChange" />

			<NcActions
				class="team-activity__range"
				forceMenu
				:menuName="dateRangeLabel"
				:aria-label="t('circles', 'Filter activities by date')">
				<template #icon>
					<CalendarRange :size="20" />
				</template>
				<NcActionButton
					v-for="preset in presets"
					:key="preset.id"
					type="radio"
					:modelValue="selectedPreset"
					:value="preset.id"
					@click="applyPreset(preset.id)">
					{{ preset.label }}
				</NcActionButton>
			</NcActions>

			<NcButton v-if="hasActiveFilters" variant="tertiary" @click="clearFilters">
				{{ t('circles', 'Clear filters') }}
			</NcButton>
		</div>

		<div v-if="selectedPreset === 'custom'" class="team-activity__custom-range">
			<div class="input-field team-activity__date">
				<div class="input-field__main-wrapper">
					<input
						:id="fromInputId"
						class="input-field__input"
						type="date"
						:value="formatDateInput(from)"
						:max="formatDateInput(to ?? today)"
						@input="onFromInput">
					<label class="input-field__label" :for="fromInputId">
						{{ t('circles', 'From') }}
					</label>
				</div>
			</div>
			<div class="input-field team-activity__date">
				<div class="input-field__main-wrapper">
					<input
						:id="toInputId"
						class="input-field__input"
						type="date"
						:value="formatDateInput(to)"
						:min="formatDateInput(from)"
						:max="formatDateInput(today)"
						@input="onToInput">
					<label class="input-field__label" :for="toInputId">
						{{ t('circles', 'To') }}
					</label>
				</div>
			</div>
		</div>

		<NcLoadingIcon v-if="loading" class="team-activity__loading" :size="44" />

		<NcEmptyContent
			v-else-if="failed"
			:name="t('circles', 'Activity unavailable')"
			:description="t('circles', 'Could not load this team’s activity.')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiHistory" />
			</template>
		</NcEmptyContent>

		<NcEmptyContent
			v-else-if="activities.length === 0"
			:name="t('circles', 'No activity yet')"
			:description="t('circles', 'Team changes will appear here.')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiHistory" />
			</template>
		</NcEmptyContent>

		<div v-else class="team-activity__groups">
			<section v-for="group in groupedActivities" :key="group.key" class="team-activity__group">
				<h2 class="team-activity__group-heading" :title="group.date.toLocaleDateString(undefined, { dateStyle: 'long' })">
					{{ getDayHeading(group.date) }}
				</h2>
				<ul class="team-activity__list" :aria-label="t('circles', 'Team activity')">
					<li v-for="activity in group.activities" :key="activity.activity_id" class="team-activity__item">
						<NcIconSvgWrapper
							v-if="isRosterActivity(activity)"
							class="team-activity__icon team-activity__roster-icon"
							:path="mdiAccountMultipleOutline"
							:size="20" />
						<NcAvatar
							v-else-if="activity.icon"
							class="team-activity__icon avatardiv--unknown"
							:disableMenu="true"
							:disableTooltip="true"
							:url="activity.icon"
							:size="20" />
						<div class="team-activity__content">
							<NcRichText
								class="team-activity__subject"
								:text="getRichText(activity.subject, activity.subject_rich)"
								:arguments="mapRichObjects(getRichObjects(activity.subject_rich))" />
							<NcRichText
								v-if="activity.message !== undefined || activity.message_rich?.[0]"
								class="team-activity__message"
								:text="getRichText(activity.message ?? '', activity.message_rich)"
								:arguments="mapRichObjects(getRichObjects(activity.message_rich))" />
						</div>
						<NcDateTime class="team-activity__timestamp" :timestamp="Date.parse(activity.datetime)" ignoreSeconds />
					</li>
				</ul>
			</section>
		</div>

		<div v-if="!loading && nextSince !== undefined" class="team-activity__more">
			<NcButton :disabled="loadingMore" @click="loadActivities(true)">
				{{ loadingMore ? t('circles', 'Loading…') : t('circles', 'Load more') }}
			</NcButton>
		</div>
	</div>
</template>

<style lang="scss" scoped>
.team-activity {
	height: 100%;
	overflow-y: auto;
	padding: 20px;

	&__loading {
		display: block;
		margin: 64px auto;
	}

	&__filters {
		--team-activity-filter-height: var(--default-clickable-area);
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 2);
		max-width: 900px;
		margin: 0 auto calc(3 * var(--default-grid-baseline));
		min-height: var(--team-activity-filter-height);

		:deep(.input-field),
		:deep(.v-select.select),
		:deep(.action-item) {
			margin: 0;
		}

		:deep(.input-field__main-wrapper),
		:deep(.button-vue),
		:deep(.v-select.select .vs__dropdown-toggle) {
			box-sizing: border-box;
			height: var(--team-activity-filter-height);
			min-height: var(--team-activity-filter-height);
		}

		:deep(.v-select.select .vs__dropdown-toggle) {
			max-height: none;
			overflow: visible;
			padding: 0;
		}
	}

	&__search {
		flex: 1 1 240px;
		min-width: 0;
	}

	&__actor {
		flex: 0 1 200px;
		min-width: 150px;
	}

	&__range {
		flex: 0 0 auto;
	}

	&__custom-range {
		display: flex;
		flex-wrap: wrap;
		gap: calc(var(--default-grid-baseline) * 2);
		max-width: 900px;
		margin: var(--default-grid-baseline) auto calc(3 * var(--default-grid-baseline));

		:deep(.input-field) {
			margin: 0;
		}
	}

	&__date {
		position: relative;
		flex: 1 1 180px;
		min-width: 0;

		:deep(.input-field__main-wrapper) {
			height: var(--default-clickable-area);
			padding: 2px;
		}

		:deep(.input-field__input) {
			box-sizing: border-box;
			width: 100%;
			height: 30px !important;
			min-height: 0;
			padding: 0 8px;
			border: 0;
			border-radius: 8px;
			background-color: var(--color-main-background);
			appearance: textfield;
			box-shadow: 0 -1px 0 var(--color-border-maxcontrast),
				0 0 0 1px color-mix(in srgb, var(--color-border-maxcontrast) 35%, transparent);
		}

		:deep(.input-field__label) {
			position: absolute;
			top: -9.75px;
			left: 2px;
			padding: 0 calc(var(--default-grid-baseline) / 2);
			background-color: var(--color-main-background);
			font-size: 13px;
			line-height: 19.5px;
		}
	}

	&__list {
		max-width: 700px;
		margin: 0 auto;
		padding: 0;
		list-style: none;
	}

	&__group {
		padding-bottom: calc(3 * var(--default-grid-baseline));
	}

	&__group-heading {
		position: sticky;
		top: 0;
		z-index: 1;
		max-width: 700px;
		margin: 0 auto;
		padding: var(--default-grid-baseline) 0 calc(2.5 * var(--default-grid-baseline));
		background: linear-gradient(to bottom, var(--color-main-background) 44%, transparent);
		font-size: 20px;
		line-height: var(--default-clickable-area);
	}

	&__item {
		display: flex;
		align-items: center;
		gap: calc(2 * var(--default-grid-baseline));
		padding: calc(2 * var(--default-grid-baseline));
	}

	&__icon {
		flex: 0 0 auto;
		opacity: 0.5;

		:deep(img) {
			border-radius: 0;
		}
	}

	&__content {
		flex: 1 1 min-content;
		min-width: 0;
		overflow: hidden;
		overflow-wrap: break-word;
		white-space: pre-wrap;
		word-break: break-word;

		:deep(a) {
			font-weight: bold;

			&:hover {
				opacity: 0.7;
				text-decoration: underline;
			}
		}
	}

	&__subject {
		padding: 0 5px;
	}

	&__message {
		color: var(--color-text-lighter);
	}

	&__timestamp {
		flex: 0 0 auto;
		margin-left: 5px;
		color: var(--color-text-lighter);
	}

	&__more {
		display: flex;
		justify-content: center;
		margin: calc(3 * var(--default-grid-baseline)) 0;
	}

	@media (max-width: 800px) {
		&__filters {
			align-items: stretch;
		}

		&__search,
		&__actor,
		&__range {
			flex-basis: 100%;
		}
	}
}
</style>
