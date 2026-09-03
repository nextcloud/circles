<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
  -->

<script setup lang="ts">
import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { TeamFolder } from '../teams/api.ts'

import axios from '@nextcloud/axios'
import { showConfirmation, showError, showSuccess } from '@nextcloud/dialogs'
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, onMounted, ref } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { logger } from '../logger.ts'
import { deleteTeam, getAdminTeamFolders, getLinkableTeamFolders, linkTeamFolder, updateTeamFolderQuota, upgradeTeamFolder } from '../teams/api.ts'

interface QuotaOption {
	id: string
	label: string
}

const unlimitedQuota: QuotaOption = {
	id: '0',
	label: t('circles', 'Unlimited'),
}

const quotaPreset: QuotaOption[] = [
	{ id: '1 GB', label: '1 GB' },
	{ id: '5 GB', label: '5 GB' },
	{ id: '10 GB', label: '10 GB' },
]

const teamFolderDefaultQuotaBytes = Number(loadState('circles', 'teamFolderDefaultQuota', 0))

const quotaOptions = computed<QuotaOption[]>(() => {
	const options = [...quotaPreset]
	if (teamFolderDefaultQuotaBytes <= 0) {
		options.unshift(unlimitedQuota)
		return options
	}
	const label = formatFileSize(teamFolderDefaultQuotaBytes)
	const option = { id: label, label }
	if (!options.some((q) => q.id === label)) {
		options.unshift(option)
	} else {
		options.unshift(unlimitedQuota)
	}
	return options
})

const selectedQuota = ref<QuotaOption>(teamFolderDefaultQuotaBytes <= 0
	? unlimitedQuota
	: { id: formatFileSize(teamFolderDefaultQuotaBytes), label: formatFileSize(teamFolderDefaultQuotaBytes) })

type AdminTeamFolder = Awaited<ReturnType<typeof getAdminTeamFolders>>[number]
type TeamFolderSortKey = 'teamName' | 'mountPoint'

const teamFolders = ref<AdminTeamFolder[]>([])
const teamFolderQuotas = ref<Record<string, QuotaOption>>({})
const manuallyChangedQuotaTeamIds = ref<Set<string>>(new Set())
const updatingQuotaTeamIds = ref<Set<string>>(new Set())
const loadingTeamFolders = ref(true)
const sortKey = ref<TeamFolderSortKey>('teamName')
const sortAscending = ref(true)
const selectedTeam = ref<AdminTeamFolder | null>(null)
const folderName = ref('')
const assigningFolder = ref(false)
const assignmentMode = ref<'create' | 'existing'>('create')
const linkableFolders = ref<TeamFolder[]>([])
const selectedExistingFolder = ref<TeamFolder | null>(null)
const loadingLinkableFolders = ref(false)

const sortedTeamFolders = computed(() => {
	const direction = sortAscending.value ? 1 : -1
	return [...teamFolders.value].sort((a, b) => {
		const left = getSortValue(a, sortKey.value)
		const right = getSortValue(b, sortKey.value)
		if (left < right) {
			return -1 * direction
		}
		if (left > right) {
			return direction
		}
		return 0
	})
})

/**
 * Read the value of a team folder used to compare rows for sorting.
 *
 * @param teamFolder - The row to read the value from
 * @param key - The column to sort by
 */
function getSortValue(teamFolder: AdminTeamFolder, key: TeamFolderSortKey): string | number {
	switch (key) {
		case 'teamName':
			return teamFolder.teamName
		case 'mountPoint':
			return teamFolder.folder?.mountPoint ?? ''
	}
}

/**
 * Get the optional team folder mount point for display.
 *
 * @param teamFolder - The row to read the mount point from
 */
function getMountPoint(teamFolder: AdminTeamFolder): string {
	return teamFolder.folder?.mountPoint ?? ''
}

/**
 * Toggle the column used to sort the team folders table.
 *
 * @param key - The column to sort by
 */
function onSortClick(key: TeamFolderSortKey) {
	if (sortKey.value === key) {
		sortAscending.value = !sortAscending.value
	} else {
		sortKey.value = key
		sortAscending.value = true
	}
}

/** Load the team folders shown in the admin settings table. */
async function loadTeamFolders() {
	loadingTeamFolders.value = true
	try {
		teamFolders.value = await getAdminTeamFolders()
		teamFolderQuotas.value = Object.fromEntries(teamFolders.value.map((teamFolder) => [
			teamFolder.teamId,
			quotaOptionFromBytes(teamFolder.folder?.quota ?? teamFolderDefaultQuotaBytes),
		]))
		manuallyChangedQuotaTeamIds.value = new Set()
	} catch (error) {
		showError(t('circles', 'Unable to load team folders'))
		logger.error('Unable to load team folders', { error })
	} finally {
		loadingTeamFolders.value = false
	}
}

/**
 * Open the team-folder assignment dialog for an unassigned team.
 *
 * @param team - The team that will receive a new folder
 */
async function openAssignDialog(team: AdminTeamFolder) {
	selectedTeam.value = team
	folderName.value = team.teamName
	assignmentMode.value = 'create'
	selectedExistingFolder.value = null
	loadingLinkableFolders.value = true
	try {
		linkableFolders.value = await getLinkableTeamFolders(team.teamId)
	} catch (error) {
		linkableFolders.value = []
		showError(t('circles', 'Unable to load available team folders'))
		logger.error('Unable to load available team folders', { error, teamId: team.teamId })
	} finally {
		loadingLinkableFolders.value = false
	}
}

/** Close the team-folder assignment dialog. */
function closeAssignDialog() {
	if (!assigningFolder.value) {
		selectedTeam.value = null
	}
}

/** Create a new exclusive team folder with the name provided by the administrator. */
async function assignTeamFolder() {
	if (selectedTeam.value === null) {
		return
	}
	if (assignmentMode.value === 'create' && folderName.value.trim() === '') {
		return
	}
	if (assignmentMode.value === 'existing' && selectedExistingFolder.value === null) {
		return
	}

	const teamId = selectedTeam.value.teamId
	const existingFolderId = selectedExistingFolder.value?.id
	assigningFolder.value = true
	try {
		if (assignmentMode.value === 'create') {
			await upgradeTeamFolder(teamId, folderName.value.trim())
		} else if (existingFolderId !== undefined) {
			await linkTeamFolder(teamId, existingFolderId)
		}
		showSuccess(t('circles', 'Team folder added'))
		selectedTeam.value = null
		await loadTeamFolders()
	} catch (error) {
		showError(t('circles', 'Unable to add team folder'))
		logger.error('Unable to add team folder', { error, teamId })
	} finally {
		assigningFolder.value = false
	}
}

/**
 * Delete a team after an explicit destructive confirmation.
 *
 * @param team - The team to delete
 */
async function confirmDeleteTeam(team: AdminTeamFolder) {
	const confirmed = await showConfirmation({
		name: t('circles', 'Delete team'),
		text: t('circles', 'Are you sure you want to delete {team}? This cannot be undone.', { team: team.teamName }),
		labelConfirm: t('circles', 'Delete team'),
		labelReject: t('circles', 'Cancel'),
		severity: 'error',
	})
	if (!confirmed) {
		return
	}

	try {
		await deleteTeam(team.teamId)
		showSuccess(t('circles', 'Team deleted'))
		await loadTeamFolders()
	} catch (error) {
		showError(t('circles', 'Unable to delete the team'))
		logger.error('Unable to delete team', { error, teamId: team.teamId })
	}
}

/**
 * Normalize a user-entered quota string into a quota option.
 *
 * @param quota - Raw quota string entered by the user (e.g. "4 GB")
 * @return Normalized quota option
 */
function validateQuota(quota: string): QuotaOption {
	const parsed = parseFileSize(quota, true)
	if (parsed !== null && parsed >= 0) {
		const label = formatFileSize(parsed)
		return { id: label, label }
	}
	return unlimitedQuota
}

/**
 * Build the quota option matching a byte count.
 *
 * @param bytes - Quota in bytes, zero or negative for unlimited
 */
function quotaOptionFromBytes(bytes: number): QuotaOption {
	if (bytes <= 0) {
		return unlimitedQuota
	}
	const label = formatFileSize(bytes)
	return { id: label, label }
}

/**
 * Persist the storage quota of an existing team folder.
 *
 * @param teamFolder - The row whose folder quota changed
 * @param quota - The newly selected quota option
 */
async function onUpdateTeamFolderQuota(teamFolder: AdminTeamFolder, quota: QuotaOption) {
	const previous = teamFolderQuotas.value[teamFolder.teamId]
	const bytes = quota.id === unlimitedQuota.id ? 0 : parseFileSize(quota.id, true)
	if (bytes === null || bytes < 0) {
		showError(t('circles', 'Quota must be a non-negative number.'))
		return
	}

	manuallyChangedQuotaTeamIds.value.add(teamFolder.teamId)
	teamFolderQuotas.value[teamFolder.teamId] = quota
	updatingQuotaTeamIds.value.add(teamFolder.teamId)
	try {
		const folder = await updateTeamFolderQuota(teamFolder.teamId, Math.round(bytes))
		teamFolderQuotas.value[teamFolder.teamId] = quotaOptionFromBytes(folder.quota ?? bytes)
		showSuccess(t('circles', 'Changed team folder quota'))
	} catch (error) {
		teamFolderQuotas.value[teamFolder.teamId] = previous
		showError(t('circles', 'Unable to update team folder quota'))
		logger.error('Unable to update team folder quota', { error, teamId: teamFolder.teamId })
	} finally {
		updatingQuotaTeamIds.value.delete(teamFolder.teamId)
	}
}

/**
 * Apply a new default quota to every team folder the administrator has not
 * manually changed in this session.
 *
 * @param bytes - The new default quota in bytes, zero for unlimited
 */
async function applyDefaultQuotaToUnmodifiedFolders(bytes: number) {
	const targets = teamFolders.value.filter((teamFolder) => teamFolder.folder !== null && !manuallyChangedQuotaTeamIds.value.has(teamFolder.teamId))

	await Promise.all(targets.map(async (teamFolder) => {
		updatingQuotaTeamIds.value.add(teamFolder.teamId)
		try {
			const folder = await updateTeamFolderQuota(teamFolder.teamId, Math.round(bytes))
			teamFolderQuotas.value[teamFolder.teamId] = quotaOptionFromBytes(folder.quota ?? bytes)
		} catch (error) {
			showError(t('circles', 'Unable to update team folder quota for {team}', { team: teamFolder.teamName }))
			logger.error('Unable to update team folder quota', { error, teamId: teamFolder.teamId })
		} finally {
			updatingQuotaTeamIds.value.delete(teamFolder.teamId)
		}
	}))
}

/**
 * Update app configuration
 *
 * @param key - The config key
 * @param value - The config value
 */
async function updateAppConfig(key: string, value: string): Promise<boolean> {
	try {
		await confirmPassword()

		const url = generateOcsUrl('/apps/circles/settings/{key}', {
			appId: 'circles',
			key,
		})
		const { data } = await axios.post<OCSResponse>(url, {
			value,
		})
		if (data.ocs.meta.status !== 'ok') {
			if (data.ocs.meta.message) {
				showError(t('circles', 'Unable to update team folder config'))
				logger.error('Error while updating team folder config', { error: data.ocs })
				return false
			} else {
				throw new Error(`${data.ocs.meta.statuscode}`)
			}
		}
		return true
	} catch (error) {
		showError(t('circles', 'Unable to update team folder config'))
		logger.error('Error while updating team folder config', { error })
		return false
	}
}

/**
 * Save the default team folder quota.
 *
 * The selected option id is a human-readable size string (e.g. "5 GB") or
 * "0" for unlimited; it is parsed back to bytes before being stored.
 */
async function onSaveQuota() {
	const bytes = selectedQuota.value.id === unlimitedQuota.id ? 0 : parseFileSize(selectedQuota.value.id, true)
	if (bytes === null || bytes < 0) {
		showError(t('circles', 'Quota must be a non-negative number.'))
		return
	}

	if (await updateAppConfig('team_folder_default_quota', String(Math.round(bytes)))) {
		showSuccess(t('circles', 'Changed default team folder quota'))
		await applyDefaultQuotaToUnmodifiedFolders(bytes)
	}
}

onMounted(loadTeamFolders)
</script>

<template>
	<NcSettingsSection
		:name="t('circles', 'Teams')"
		:description="t('circles', 'Configure the default storage quota for team folders. Requires the Team Folders app to be installed and enabled.')">
		<div class="team-folders__input-row">
			<NcSelect
				v-model="selectedQuota"
				:clearable="false"
				:createOption="validateQuota"
				:inputLabel="t('circles', 'Default quota')"
				:options="quotaOptions"
				:placeholder="t('circles', 'Select default quota')"
				taggable
				class="team-folders__input" />
			<NcButton
				variant="primary"
				@click="onSaveQuota">
				{{ t('circles', 'Save') }}
			</NcButton>
		</div>

		<p class="team-folders__hint">
			{{ t('circles', 'Default storage quota applied to each auto-created team folder. Use 0 for unlimited storage.') }}
		</p>
	</NcSettingsSection>

	<div class="team-folders__list">
		<h3 class="team-folders__list-title">
			{{ t('circles', 'Teams') }}
		</h3>
		<p v-if="loadingTeamFolders" class="team-folders__hint">
			{{ t('circles', 'Loading team folders…') }}
		</p>
		<div v-if="!loadingTeamFolders" class="team-folders__scroll">
			<table class="team-folders__table">
				<thead>
					<tr>
						<th>
							<button type="button" class="team-folders__sort-header" @click="onSortClick('teamName')">
								{{ t('circles', 'Team') }}
								<span v-if="sortKey === 'teamName'">{{ sortAscending ? '▲' : '▼' }}</span>
							</button>
						</th>
						<th class="team-folders__mountpoint">
							<button type="button" class="team-folders__sort-header" @click="onSortClick('mountPoint')">
								{{ t('circles', 'Team folder') }}
								<span v-if="sortKey === 'mountPoint'">{{ sortAscending ? '▲' : '▼' }}</span>
							</button>
						</th>
						<th class="team-folders__quota">
							{{ t('circles', 'Storage quota') }}
						</th>
						<th>
							<span class="hidden-visually">{{ t('circles', 'Actions') }}</span>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-if="sortedTeamFolders.length === 0" class="team-folders__empty">
						<td colspan="4">
							{{ t('circles', 'No team folders found.') }}
						</td>
					</tr>
					<tr v-for="teamFolder in sortedTeamFolders" v-else :key="teamFolder.teamId">
						<td>
							{{ teamFolder.teamName }}
						</td>
						<td class="team-folders__mountpoint">
							{{ getMountPoint(teamFolder) }}
						</td>
						<td class="team-folders__quota">
							<NcSelect
								:modelValue="teamFolderQuotas[teamFolder.teamId]"
								:options="quotaOptions"
								:createOption="validateQuota"
								:disabled="teamFolder.folder === null || updatingQuotaTeamIds.has(teamFolder.teamId)"
								:aria-label="t('circles', 'Storage quota')"
								:clearable="false"
								taggable
								class="team-folders__quota-select"
								@update:modelValue="onUpdateTeamFolderQuota(teamFolder, $event)" />
						</td>
						<td class="team-folders__remove">
							<NcActions forceMenu :aria-label="t('circles', 'Team actions')">
								<NcActionButton
									v-if="teamFolder.folder === null"
									closeAfterClick
									@click="openAssignDialog(teamFolder)">
									{{ t('circles', 'Add') }}
								</NcActionButton>
								<NcActionButton closeAfterClick @click="confirmDeleteTeam(teamFolder)">
									{{ t('circles', 'Delete team') }}
								</NcActionButton>
							</NcActions>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<NcDialog
			v-if="selectedTeam"
			:name="t('circles', 'Add a team folder')"
			:open="selectedTeam !== null"
			@closing="closeAssignDialog">
			<p class="team-folders__warning">
				{{ t('circles', 'This team folder can only be assigned to this team. It cannot be assigned to another team, and this action cannot be undone.') }}
			</p>
			<div class="team-folders__dialog-options">
				<NcCheckboxRadioSwitch
					v-model="assignmentMode"
					value="create"
					type="radio"
					:disabled="assigningFolder">
					{{ t('circles', 'Create a new team folder') }}
				</NcCheckboxRadioSwitch>
				<NcTextField
					v-if="assignmentMode === 'create'"
					v-model="folderName"
					:label="t('circles', 'Team folder name')"
					:disabled="assigningFolder"
					required />
				<NcCheckboxRadioSwitch
					v-model="assignmentMode"
					value="existing"
					type="radio"
					:disabled="assigningFolder">
					{{ t('circles', 'Use an existing team folder') }}
				</NcCheckboxRadioSwitch>
				<NcSelect
					v-if="assignmentMode === 'existing'"
					v-model="selectedExistingFolder"
					:options="linkableFolders"
					:loading="loadingLinkableFolders"
					:disabled="assigningFolder || loadingLinkableFolders"
					:placeholder="t('circles', 'Select a team folder')"
					label="mountPoint"
					:clearable="false" />
			</div>
			<template #actions>
				<NcButton :disabled="assigningFolder" @click="closeAssignDialog">
					{{ t('circles', 'Cancel') }}
				</NcButton>
				<NcButton
					variant="primary"
					:disabled="assigningFolder || (assignmentMode === 'create' ? folderName.trim() === '' : selectedExistingFolder === null)"
					@click="assignTeamFolder">
					{{ t('circles', 'Add') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<style scoped>
.team-folders__input-row {
	display: flex;
	gap: 8px;
	align-items: flex-end;
	max-width: 500px;
}

.team-folders__input {
	flex: 1;
}

.team-folders__hint {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
	margin: 12px 0 0;
}

:deep(.dialog) {
	width: min(600px, calc(100vw - 32px));
}

.team-folders__dialog-options {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.team-folders__warning {
	margin: 0 0 16px;
	padding: 12px;
	border-inline-start: 4px solid var(--color-warning);
	background-color: var(--color-warning-hover);
	color: var(--color-main-text);
}

.team-folders__list {
	margin: 24px calc(var(--default-grid-baseline) * 7);
}

.team-folders__list-title {
	border-bottom: 1px solid var(--color-border);
	margin: 0;
	padding-bottom: 8px;
	font-size: 16px;
	font-weight: 600;
}

.team-folders__scroll {
	overflow-x: auto;
}

.team-folders__table {
	border-collapse: collapse;
	width: 100%;
}

.team-folders__table tr {
	height: 55px;
}

.team-folders__table th,
.team-folders__table td {
	padding: 10px;
	position: relative;
	text-align: left;
}

.team-folders__table thead th {
	border-bottom: 2px solid var(--color-border);
	color: var(--color-text-lighter);
}

.team-folders__table thead th:first-child {
	color: var(--color-main-text);
	font-weight: bold;
}

.team-folders__sort-header {
	display: flex;
	align-items: center;
	gap: 6px;
	width: 100%;
	padding: 0;
	margin: 0;
	border: none;
	background: none;
	font: inherit;
	color: inherit;
	text-align: inherit;
	cursor: pointer;
}

.team-folders__sort-header:focus-visible {
	outline: 2px solid var(--color-main-text);
	outline-offset: 2px;
}

.team-folders__empty td {
	padding: 32px 16px;
	color: var(--color-text-maxcontrast);
	text-align: center;
}

.team-folders__table tbody tr:not(:last-child) {
	border-bottom: 1px solid var(--color-border);
}

.team-folders__table tbody tr:hover td {
	background-color: var(--color-background-dark);
}

.team-folders__table tbody tr:hover td:first-child {
	border-radius: 6px 0 0 6px;
}

.team-folders__table tbody tr:hover td:last-child {
	border-radius: 0 6px 6px 0;
}

.team-folders__table tbody td:first-child {
	color: var(--color-main-text);
	font-weight: bold;
}

.team-folders__mountpoint {
	width: 35%;
}

.team-folders__quota {
	width: 180px;
}

.team-folders__quota-select {
	width: 100%;
	padding: 6px 8px;
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: inherit;
}

.team-folders__remove {
	width: 32px;
}

@media (max-width: 700px) {
	.team-folders__table {
		min-width: 640px;
	}
}
</style>
