<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
  -->

<script setup lang="ts">
import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { StoragePool, TeamFolder } from '../teams/api.ts'

import axios from '@nextcloud/axios'
import { showConfirmation, showError, showSuccess } from '@nextcloud/dialogs'
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, nextTick, onMounted, ref } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import IconPencil from 'vue-material-design-icons/Pencil.vue'
import IconDeleteOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import { logger } from '../logger.ts'
import { assignTeamStoragePool, deleteStoragePool, deleteTeam, getAdminTeamFolders, getLinkableTeamFolders, getStoragePools, linkTeamFolder, setStoragePoolLimit, updateTeamFolderDefaultQuota, updateTeamFolderQuota, upgradeTeamFolder } from '../teams/api.ts'

interface QuotaOption {
	id: string
	label: string
}

interface TeamOption {
	id: string
	label: string
}

type QuotaTargetType = 'team' | 'group'

interface QuotaRow extends TeamOption {
	type: QuotaTargetType
	quota: QuotaOption
}

interface QuotaTarget extends QuotaRow {
	selectionLabel: string
}

interface DisplayQuotaRow extends Omit<QuotaRow, 'quota'> {
	quota: QuotaOption | null
	poolOnly: boolean
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
const teamFolderProvisioningEnabled = Boolean(loadState('circles', 'teamFolderProvisioningEnabled', true))
const selectedQuota = ref<QuotaOption>(teamFolderDefaultQuotaBytes <= 0
	? unlimitedQuota
	: { id: formatFileSize(teamFolderDefaultQuotaBytes), label: formatFileSize(teamFolderDefaultQuotaBytes) })
const rows = ref<QuotaRow[]>([])
const loadedQuotas = ref<Record<string, number>>({})
const groupRows = ref<QuotaRow[]>([])
const loadedGroupQuotas = ref<Record<string, number>>({})
const removedQuotaTargetIds = ref<Set<string>>(new Set())
const availableGroups = ref<TeamOption[]>([])
const selectedQuotaTarget = ref<QuotaTarget | null>(null)
const loadingGroups = ref(true)
const savingQuotas = ref(false)
const poolsSupported = ref(false)
const storagePools = ref<StoragePool[]>([])
const poolInputs = ref<Record<string, string>>({})
const loadedPoolInputs = ref<Record<string, string>>({})
const movingTeamIds = ref<Set<string>>(new Set())
const poolsById = computed(() => new Map(storagePools.value.map((pool) => [pool.id, pool])))
const settingsTabs = ['teamFolders', 'defaultQuotas'] as const
type SettingsTab = typeof settingsTabs[number]
const activeTab = ref<SettingsTab>('teamFolders')

const quotaOptions = computed<QuotaOption[]>(() => {
	const options = [unlimitedQuota, ...quotaPreset]
	for (const quota of [selectedQuota.value, ...rows.value.map((row) => row.quota), ...groupRows.value.map((row) => row.quota)]) {
		if (!options.some((option) => option.id === quota.id)) {
			options.push(quota)
		}
	}
	return options
})

type AdminTeamFolder = Awaited<ReturnType<typeof getAdminTeamFolders>>[number]
type TeamFolderSortKey = 'teamName' | 'mountPoint'

const teamFolders = ref<AdminTeamFolder[]>([])
const poolOptions = computed(() => storagePools.value.map((pool) => ({
	id: pool.id,
	label: pool.teamId
		? `${teamFolders.value.find((team) => team.teamId === pool.teamId)?.teamName ?? pool.teamId} (${t('circles', 'Team')})`
		: pool.groupId === null ? t('circles', 'Everyone else') : availableGroups.value.find((group) => group.id === pool.groupId)?.label ?? pool.groupId,
})))
const teamFolderQuotas = ref<Record<string, QuotaOption>>({})
const manuallyChangedQuotaTeamIds = ref<Set<string>>(new Set())
const updatingQuotaTeamIds = ref<Set<string>>(new Set())
const loadingTeamFolders = ref(true)
const sortKey = ref<TeamFolderSortKey>('teamName')
const sortAscending = ref(true)
const teamSearch = ref('')
const selectedTeam = ref<AdminTeamFolder | null>(null)
const folderName = ref('')
const assigningFolder = ref(false)
const assignmentMode = ref<'create' | 'existing'>('create')
const linkableFolders = ref<TeamFolder[]>([])
const selectedExistingFolder = ref<TeamFolder | null>(null)
const loadingLinkableFolders = ref(false)

const availableQuotaTargets = computed<QuotaTarget[]>(() => {
	const mappedTargets = new Set([...rows.value, ...groupRows.value].map((row) => `${row.type}:${row.id}`))
	const teams = teamFolders.value
		.map((team) => ({ id: team.teamId, label: team.teamName, selectionLabel: `${team.teamName} (${t('circles', 'Team')})`, type: 'team' as const, quota: selectedQuota.value }))
		.filter((team) => !mappedTargets.has(`${team.type}:${team.id}`))
	const groups = availableGroups.value
		.map((group) => ({ ...group, selectionLabel: `${group.label} (${t('circles', 'Group')})`, type: 'group' as const, quota: selectedQuota.value }))
		.filter((group) => !mappedTargets.has(`${group.type}:${group.id}`))
	return [...teams, ...groups].sort((left, right) => left.label.localeCompare(right.label))
})

const quotaRows = computed<DisplayQuotaRow[]>(() => {
	const configuredRows = [...rows.value, ...groupRows.value].map((row) => ({ ...row, poolOnly: false }))
	const mappedTargets = new Set(configuredRows.map((row) => `${row.type}:${row.id}`))
	const poolRows = storagePools.value
		.filter((pool) => (pool.teamId || pool.groupId !== null) && !mappedTargets.has(pool.id) && !removedQuotaTargetIds.value.has(pool.id))
		.map((pool): DisplayQuotaRow => pool.teamId
			? {
					id: pool.teamId,
					label: teamFolders.value.find((team) => team.teamId === pool.teamId)?.teamName ?? pool.teamId,
					type: 'team',
					quota: null,
					poolOnly: true,
				}
			: {
					id: pool.groupId!,
					label: availableGroups.value.find((group) => group.id === pool.groupId)?.label ?? pool.groupId!,
					type: 'group',
					quota: null,
					poolOnly: true,
				})
	return [...configuredRows, ...poolRows].sort((left, right) => left.label.localeCompare(right.label))
})

/**
 * Update a default override without creating overrides for pool-only rows.
 *
 * @param row - Displayed team or group
 * @param quota - Selected default quota
 */
function onUpdateDefaultQuota(row: DisplayQuotaRow, quota: QuotaOption | null) {
	if (quota === null) {
		return
	}
	const targets = row.type === 'team' ? rows.value : groupRows.value
	const target = targets.find((target) => target.id === row.id)
	if (target) {
		target.quota = quota
	} else {
		targets.push({ id: row.id, label: row.label, type: row.type, quota })
	}
}

/**
 * Refresh allocations while optionally preserving pending limit edits.
 *
 * @param resetInputs - Replace editable limits after a successful save
 */
async function loadStoragePools(resetInputs: boolean = true) {
	try {
		const state = await getStoragePools()
		poolsSupported.value = state.supported
		storagePools.value = state.pools
		if (resetInputs) {
			poolInputs.value = Object.fromEntries(state.pools.map((pool) => [pool.id, pool.limit === null ? '' : formatFileSize(pool.limit)]))
			loadedPoolInputs.value = { ...poolInputs.value }
		}
	} catch (error) {
		showError(t('circles', 'Unable to load storage pools'))
		logger.error('Unable to load storage pools', { error })
	}
}

/**
 * Format the server-calculated allocation, including unlimited folders.
 *
 * @param poolId - Financing pool identifier
 */
function allocatedStorage(poolId: string): string {
	const pool = poolsById.value.get(poolId)
	return pool?.hasUnlimitedFolders ? t('circles', 'Unlimited') : formatFileSize(pool?.allocated ?? 0)
}

/**
 * Format cached file usage without treating an unknown size as zero.
 *
 * @param bytes - Cached file size
 */
function formatUsedStorage(bytes: number | null | undefined): string {
	return bytes === null || bytes === undefined ? t('circles', 'Unknown') : formatFileSize(bytes)
}

/**
 * Format the total budget of a team's financing pool.
 *
 * @param poolId - Assigned financing pool
 */
function totalStorage(poolId: string | null | undefined): string {
	const pool = poolsById.value.get(poolId ?? '')
	return !pool ? t('circles', 'Not configured') : pool.limit === null ? t('circles', 'Unlimited') : formatFileSize(pool.limit)
}

/**
 * Open and focus the assigned pool's limit, independently of folder ownership.
 *
 * @param poolId - Assigned financing pool
 */
async function editStoragePool(poolId: string) {
	activeTab.value = 'defaultQuotas'
	await nextTick()
	document.getElementById(`team-storage-pool-${poolId}`)?.focus()
}

/**
 * Choose a localized error for insufficient pool capacity.
 *
 * @param error - Rejected API response
 * @param fallback - Message for other failures
 */
function poolError(error: unknown, fallback: string): string {
	const code = (error as { response?: { data?: { ocs?: { data?: { code?: string } } } } })?.response?.data?.ocs?.data?.code
	return code === 'storage_pool_exceeded' ? t('circles', 'The storage pool does not have enough available quota.') : fallback
}

/**
 * Move financing without changing the folder or team membership.
 *
 * @param team - Team to move
 * @param option - Destination pool
 */
async function moveTeamPool(team: AdminTeamFolder, option: TeamOption | null) {
	if (option === null || option.id === team.storagePoolId) {
		return
	}
	const pool = poolsById.value.get(option.id)
	if (!pool) {
		return
	}
	const previouslyUnassigned = !team.storagePoolId
	movingTeamIds.value.add(team.teamId)
	try {
		await confirmPassword()
		const result = await assignTeamStoragePool(team.teamId, pool.groupId, pool.teamId ?? null)
		team.storagePoolId = result.storagePoolId
		showSuccess(t('circles', 'Changed team storage pool'))
		await loadStoragePools(false)
	} catch (error) {
		showError(poolError(error, t('circles', 'Unable to change the team storage pool')))
		logger.error('Unable to move team storage pool', { error, teamId: team.teamId })
		if (previouslyUnassigned) {
			await loadTeamFolders()
		}
	} finally {
		movingTeamIds.value.delete(team.teamId)
	}
}

const sortedTeamFolders = computed(() => {
	const direction = sortAscending.value ? 1 : -1
	const query = teamSearch.value.trim().toLocaleLowerCase()
	return teamFolders.value
		.filter((teamFolder) => teamFolder.teamName.toLocaleLowerCase().includes(query))
		.sort((a, b) => {
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
		loadedQuotas.value = Object.fromEntries(teamFolders.value
			.filter((team) => team.defaultQuota !== null)
			.map((team) => [team.teamId, team.defaultQuota as number]))
		rows.value = teamFolders.value
			.filter((team) => team.defaultQuota !== null)
			.map((team) => ({ id: team.teamId, label: team.teamName, type: 'team' as const, quota: quotaOptionFromBytes(team.defaultQuota as number) }))
			.sort((left, right) => left.label.localeCompare(right.label))
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

/** Load groups and their configured quota mappings. */
async function loadGroupQuotas() {
	loadingGroups.value = true
	try {
		const [groupsResponse, settingsResponse] = await Promise.all([
			axios.get<OCSResponse>(generateOcsUrl('/apps/circles/settings/groups')),
			axios.get<OCSResponse>(generateOcsUrl('/apps/circles/settings')),
		])
		availableGroups.value = groupsResponse.data.ocs.data as TeamOption[]
		const quotas = (settingsResponse.data.ocs.data.team_folder_group_quotas ?? {}) as Record<string, number>
		loadedGroupQuotas.value = quotas
		const groupsById = new Map(availableGroups.value.map((group) => [group.id, group]))
		groupRows.value = Object.entries(quotas)
			.map(([id, quota]) => ({ id, label: groupsById.get(id)?.label ?? id, type: 'group' as const, quota: quotaOptionFromBytes(quota) }))
			.sort((left, right) => left.label.localeCompare(right.label))
	} catch (error) {
		showError(t('circles', 'Unable to load group quotas'))
		logger.error('Unable to load group quotas', { error })
	} finally {
		loadingGroups.value = false
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
		await Promise.all([loadTeamFolders(), loadStoragePools(false)])
	} catch (error) {
		showError(poolError(error, t('circles', 'Unable to add team folder')))
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
		await Promise.all([loadTeamFolders(), loadStoragePools(false)])
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
		teamFolder.folder = folder
		showSuccess(t('circles', 'Changed team folder quota'))
		await loadStoragePools(false)
	} catch (error) {
		teamFolderQuotas.value[teamFolder.teamId] = previous
		showError(poolError(error, t('circles', 'Unable to update team folder quota')))
		logger.error('Unable to update team folder quota', { error, teamId: teamFolder.teamId })
	} finally {
		updatingQuotaTeamIds.value.delete(teamFolder.teamId)
	}
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
	const poolChanges: Array<{ groupId: string | null, sourceTeamId: string | null, limit: number | null }> = []
	const poolIds = poolsSupported.value ? ['default', ...quotaRows.value.map((row) => `${row.type}:${row.id}`)] : []
	for (const poolId of poolIds) {
		if (removedQuotaTargetIds.value.has(poolId)) {
			continue
		}
		const input = (poolInputs.value[poolId] ?? '').trim()
		if (input === loadedPoolInputs.value[poolId]) {
			continue
		}
		const unlimited = input === '' || input === t('circles', 'Unlimited')
		const limit = unlimited ? null : parseFileSize(input, true)
		if (!unlimited && (limit === null || limit < 0 || !Number.isSafeInteger(limit))) {
			showError(t('circles', 'Storage pool limit must be a non-negative size or Unlimited.'))
			return
		}
		poolChanges.push({
			groupId: poolId.startsWith('group:') ? poolId.slice('group:'.length) : null,
			sourceTeamId: poolId.startsWith('team:') ? poolId.slice('team:'.length) : null,
			limit,
		})
	}
	const bytes = selectedQuota.value.id === unlimitedQuota.id ? 0 : parseFileSize(selectedQuota.value.id, true)
	if (bytes === null || bytes < 0) {
		showError(t('circles', 'Quota must be a non-negative number.'))
		return
	}

	const quotas: Record<string, number> = {}
	for (const row of rows.value) {
		const rowBytes = row.quota.id === unlimitedQuota.id ? 0 : parseFileSize(row.quota.id, true)
		if (rowBytes === null || rowBytes < 0) {
			showError(t('circles', 'Quota must be a non-negative number.'))
			return
		}
		quotas[row.id] = Math.round(rowBytes)
	}
	const groupQuotas: Record<string, number> = {}
	for (const row of groupRows.value) {
		const rowBytes = row.quota.id === unlimitedQuota.id ? 0 : parseFileSize(row.quota.id, true)
		if (rowBytes === null || rowBytes < 0) {
			showError(t('circles', 'Quota must be a non-negative number.'))
			return
		}
		groupQuotas[row.id] = Math.round(rowBytes)
	}

	savingQuotas.value = true
	try {
		const poolsToDelete = storagePools.value.filter((pool) => removedQuotaTargetIds.value.has(pool.id))
		if (poolChanges.length > 0 || poolsToDelete.length > 0) {
			await confirmPassword()
			for (const change of poolChanges) {
				await setStoragePoolLimit(change.groupId, change.limit, change.sourceTeamId)
			}
			for (const pool of poolsToDelete) {
				await deleteStoragePool(pool.groupId, pool.teamId ?? null)
			}
		}
		if (!await updateAppConfig('team_folder_default_quota', String(Math.round(bytes)))) {
			return
		}
		if (!await updateAppConfig('team_folder_group_quotas', JSON.stringify(groupQuotas))) {
			return
		}

		const changedTeamIds = new Set([...Object.keys(loadedQuotas.value), ...Object.keys(quotas)])
		await Promise.all([...changedTeamIds].map(async (teamId) => {
			const quota = quotas[teamId] ?? null
			if ((loadedQuotas.value[teamId] ?? null) !== quota) {
				await updateTeamFolderDefaultQuota(teamId, quota)
			}
		}))

		showSuccess(t('circles', 'Changed default team folder quotas'))
		await Promise.all([loadTeamFolders(), loadGroupQuotas(), loadStoragePools()])
		removedQuotaTargetIds.value.clear()
	} catch (error) {
		showError(poolError(error, t('circles', 'Unable to update team folder config')))
		logger.error('Unable to update team quota settings', { error })
	} finally {
		savingQuotas.value = false
	}
}

/** Add the selected team or group with the global default quota. */
function addQuotaTarget() {
	if (selectedQuotaTarget.value === null) {
		return
	}
	const target = { ...selectedQuotaTarget.value, quota: selectedQuota.value }
	removedQuotaTargetIds.value.delete(`${target.type}:${target.id}`)
	if (target.type === 'team') {
		rows.value.push(target)
	} else {
		groupRows.value.push(target)
	}
	selectedQuotaTarget.value = null
}

/**
 * Remove a quota mapping.
 *
 * @param target - Team or group mapping to remove
 */
async function removeQuotaTarget(target: DisplayQuotaRow) {
	const poolId = `${target.type}:${target.id}`
	if (poolsById.value.has(poolId)) {
		const confirmed = await showConfirmation({
			name: t('circles', 'Delete quota override and storage pool'),
			text: t('circles', 'Delete the quota override and storage pool for {name}? When saved, all teams in this pool will move to Everyone else. Files and folder quotas will remain unchanged.', { name: target.label }),
			labelConfirm: t('circles', 'Delete'),
			labelReject: t('circles', 'Cancel'),
			severity: 'warning',
		})
		if (!confirmed) {
			return
		}
	}
	removedQuotaTargetIds.value.add(poolId)
	if (target.type === 'team') {
		rows.value = rows.value.filter((row) => row.id !== target.id)
	} else {
		groupRows.value = groupRows.value.filter((row) => row.id !== target.id)
	}
}

/**
 * Handle keyboard navigation between the settings tabs.
 *
 * @param event - Keyboard event from the active tab
 */
function onTabKeydown(event: KeyboardEvent) {
	const currentIndex = settingsTabs.indexOf(activeTab.value)
	let nextIndex: number

	switch (event.key) {
		case 'ArrowLeft':
			nextIndex = (currentIndex - 1 + settingsTabs.length) % settingsTabs.length
			break
		case 'ArrowRight':
			nextIndex = (currentIndex + 1) % settingsTabs.length
			break
		case 'Home':
			nextIndex = 0
			break
		case 'End':
			nextIndex = settingsTabs.length - 1
			break
		default:
			return
	}

	event.preventDefault()
	activeTab.value = settingsTabs[nextIndex]
	const tabButtons = (event.currentTarget as HTMLElement).parentElement?.querySelectorAll<HTMLButtonElement>('[role="tab"]')
	tabButtons?.[nextIndex]?.focus()
}

onMounted(() => Promise.all([loadTeamFolders(), loadGroupQuotas(), loadStoragePools()]))
</script>

<template>
	<section class="team-folders__settings">
		<h2 class="team-folders__title">
			{{ t('circles', 'Teams') }}
		</h2>
		<p class="team-folders__description">
			{{ t('circles', 'Configure default storage quotas and manage team folders.') }}
		</p>
		<div class="team-folders__tabbar">
			<div class="team-folders__tabs" role="tablist" :aria-label="t('circles', 'Team folder settings')">
				<button
					id="team-folder-folders-tab"
					type="button"
					role="tab"
					:aria-selected="activeTab === 'teamFolders'"
					aria-controls="team-folder-folders-panel"
					:tabindex="activeTab === 'teamFolders' ? 0 : -1"
					:class="{ 'team-folders__tab--active': activeTab === 'teamFolders' }"
					class="team-folders__tab"
					@click="activeTab = 'teamFolders'"
					@keydown="onTabKeydown">
					{{ t('circles', 'Team folders') }}
				</button>
				<button
					id="team-folder-default-quotas-tab"
					type="button"
					role="tab"
					:aria-selected="activeTab === 'defaultQuotas'"
					aria-controls="team-folder-default-quotas-panel"
					:tabindex="activeTab === 'defaultQuotas' ? 0 : -1"
					:class="{ 'team-folders__tab--active': activeTab === 'defaultQuotas' }"
					class="team-folders__tab"
					@click="activeTab = 'defaultQuotas'"
					@keydown="onTabKeydown">
					{{ t('circles', 'Default quotas') }}
				</button>
			</div>
			<NcTextField
				v-if="activeTab === 'teamFolders'"
				v-model="teamSearch"
				:label="t('circles', 'Search team by name')"
				type="search"
				class="team-folders__search" />
		</div>

		<NcSettingsSection
			v-show="activeTab === 'defaultQuotas'"
			id="team-folder-default-quotas-panel"
			role="tabpanel"
			aria-labelledby="team-folder-default-quotas-tab"
			:name="t('circles', 'Default quotas')"
			:description="t('circles', 'Configure the default storage quota for team folders. Requires the Team Folders app to be installed and enabled.')">
			<p v-if="!teamFolderProvisioningEnabled" class="team-folders__warning">
				{{ t('circles', 'Automatic team folder creation is disabled. These quota mappings will not apply until it is enabled again through the occ command.') }}
			</p>
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
				<NcTextField
					v-if="poolsSupported"
					id="team-storage-pool-default"
					v-model="poolInputs.default"
					:label="t('circles', 'Total storage for everyone else')"
					:placeholder="t('circles', 'Unlimited')"
					:disabled="savingQuotas"
					class="team-folders__input" />
				<NcButton
					variant="primary"
					:disabled="savingQuotas"
					@click="onSaveQuota">
					{{ savingQuotas ? t('circles', 'Saving…') : t('circles', 'Save') }}
				</NcButton>
			</div>

			<p class="team-folders__hint">
				{{ t('circles', 'Default storage quota applied to each auto-created team folder. Use 0 for unlimited storage.') }}
			</p>
			<p v-if="poolsSupported" class="team-folders__hint">
				{{ t('circles', 'Allocated storage for everyone else: {allocated}', { allocated: allocatedStorage('default') }) }}
			</p>
			<p v-if="poolsSupported" class="team-folders__hint">
				{{ t('circles', 'Used storage for everyone else: {used}', { used: formatUsedStorage(poolsById.get('default')?.used) }) }}
			</p>

			<h3>{{ t('circles', 'Quota overrides') }}</h3>
			<p class="team-folders__hint team-folders__hint--quota-overrides">
				{{ t('circles', 'Choose the default quota assigned to a Team folder created by users based on group or team membership. The default quota for a new Team folder is always the highest from the default quotas assigned to any of the groups or teams a user is member of.') }}
			</p>
			<div class="team-folders__add-row">
				<NcSelect
					v-model="selectedQuotaTarget"
					:loading="loadingTeamFolders || loadingGroups"
					:disabled="savingQuotas"
					:options="availableQuotaTargets"
					:placeholder="t('circles', 'Select a team or group')"
					label="selectionLabel"
					class="team-folders__team-select" />
				<NcButton :disabled="selectedQuotaTarget === null || savingQuotas" @click="addQuotaTarget">
					{{ t('circles', 'Add') }}
				</NcButton>
			</div>

			<div
				class="team-folders__quota-mapping"
				role="table"
				:aria-label="t('circles', 'Default team folder quotas')">
				<div class="team-folders__header" :class="{ 'team-folders__grid--pools': poolsSupported }" role="row">
					<span role="columnheader">{{ t('circles', 'Name') }}</span>
					<span role="columnheader">{{ t('circles', 'Type') }}</span>
					<span role="columnheader">{{ t('circles', 'Default quota') }}</span>
					<span v-if="poolsSupported" role="columnheader">{{ t('circles', 'Total storage') }}</span>
					<span v-if="poolsSupported" role="columnheader">{{ t('circles', 'Allocated storage') }}</span>
					<span v-if="poolsSupported" role="columnheader">{{ t('circles', 'Used storage') }}</span>
					<span role="columnheader">{{ t('circles', 'Options') }}</span>
				</div>
				<div
					v-for="row in quotaRows"
					:key="`${row.type}:${row.id}`"
					class="team-folders__row"
					:class="{ 'team-folders__grid--pools': poolsSupported }"
					role="row">
					<div class="team-folders__team" role="cell">
						<strong>{{ row.label }}</strong>
					</div>
					<span role="cell">{{ row.type === 'team' ? t('circles', 'Team') : t('circles', 'Group') }}</span>
					<NcSelect
						:modelValue="row.quota"
						:aria-label="t('circles', 'Default quota for {team}', { team: row.label })"
						:clearable="false"
						:createOption="validateQuota"
						:options="quotaOptions"
						taggable
						role="cell"
						@update:modelValue="onUpdateDefaultQuota(row, $event)" />
					<div v-if="poolsSupported" role="cell">
						<NcTextField
							:id="`team-storage-pool-${row.type}:${row.id}`"
							v-model="poolInputs[`${row.type}:${row.id}`]"
							:label="t('circles', 'Total storage for {group}', { group: row.label })"
							:placeholder="t('circles', 'Unlimited')"
							:disabled="savingQuotas" />
					</div>
					<span v-if="poolsSupported" role="cell" :data-label="t('circles', 'Allocated storage')">
						{{ allocatedStorage(`${row.type}:${row.id}`) }}
					</span>
					<span v-if="poolsSupported" role="cell" :data-label="t('circles', 'Used storage')">
						{{ formatUsedStorage(poolsById.get(`${row.type}:${row.id}`)?.used) }}
					</span>
					<div class="team-folders__options" role="cell">
						<NcActions :aria-label="t('circles', 'Quota mapping actions')">
							<NcActionButton :disabled="savingQuotas" closeAfterClick @click="removeQuotaTarget(row)">
								<template #icon>
									<IconDeleteOutline :size="20" />
								</template>
								{{ t('circles', 'Delete') }}
							</NcActionButton>
						</NcActions>
					</div>
				</div>
			</div>
			<div class="team-folders__quota-save">
				<NcButton
					variant="primary"
					:disabled="savingQuotas"
					@click="onSaveQuota">
					{{ savingQuotas ? t('circles', 'Saving…') : t('circles', 'Save') }}
				</NcButton>
			</div>
		</NcSettingsSection>

		<div
			v-show="activeTab === 'teamFolders'"
			id="team-folder-folders-panel"
			role="tabpanel"
			aria-labelledby="team-folder-folders-tab"
			class="team-folders__list">
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
							<th v-if="poolsSupported">
								{{ t('circles', 'Storage pool') }}
							</th>
							<th v-if="poolsSupported">
								{{ t('circles', 'Total storage') }}
							</th>
							<th v-if="poolsSupported">
								{{ t('circles', 'Used storage') }}
							</th>
							<th>
								<span class="hidden-visually">{{ t('circles', 'Actions') }}</span>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr v-if="sortedTeamFolders.length === 0" class="team-folders__empty">
							<td :colspan="poolsSupported ? 7 : 4">
								{{ teamSearch.trim() ? t('circles', 'No teams match your search.') : t('circles', 'No teams found.') }}
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
							<td v-if="poolsSupported" class="team-folders__pool">
								<NcSelect
									:modelValue="poolOptions.find((pool) => pool.id === teamFolder.storagePoolId) ?? null"
									:options="poolOptions"
									:disabled="movingTeamIds.has(teamFolder.teamId) || savingQuotas"
									:placeholder="t('circles', 'Unassigned')"
									:aria-label="t('circles', 'Storage pool')"
									:clearable="false"
									@update:modelValue="moveTeamPool(teamFolder, $event)" />
							</td>
							<td v-if="poolsSupported">
								<div class="team-folders__pool-total">
									{{ totalStorage(teamFolder.storagePoolId) }}
									<NcButton
										v-if="teamFolder.storagePoolId && poolsById.has(teamFolder.storagePoolId)"
										variant="tertiary"
										:aria-label="t('circles', 'Edit total storage for {team}', { team: teamFolder.teamName })"
										:title="t('circles', 'Edit total storage for {team}', { team: teamFolder.teamName })"
										@click="editStoragePool(teamFolder.storagePoolId)">
										<template #icon>
											<IconPencil :size="20" />
										</template>
									</NcButton>
								</div>
							</td>
							<td v-if="poolsSupported">
								{{ teamFolder.folder === null ? formatFileSize(0) : formatUsedStorage(teamFolder.usedStorage) }}
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
	</section>
</template>

<style scoped>
.team-folders__settings {
	box-sizing: border-box;
	width: 100%;
	padding: calc(var(--default-grid-baseline) * 7);
}

.team-folders__title {
	margin-top: 0;
}

.team-folders__description {
	margin-top: -0.2em;
	margin-bottom: 1em;
	color: var(--color-text-maxcontrast);
}

.team-folders__tabbar {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-end;
	justify-content: space-between;
	gap: 8px 16px;
	margin-bottom: 24px;
	border-bottom: 1px solid var(--color-border);
}

.team-folders__tabs {
	display: flex;
	gap: 24px;
}

.team-folders__tab {
	position: relative;
	padding: 10px 4px 9px;
	border: 0;
	border-radius: 0;
	background: transparent;
	color: var(--color-text-maxcontrast);
	font: inherit;
	font-weight: 600;
	cursor: pointer;
}

.team-folders__tab:hover,
.team-folders__tab:focus-visible {
	color: var(--color-main-text);
}

.team-folders__tab:focus-visible {
	outline: 2px solid var(--color-main-text);
	outline-offset: 2px;
}

.team-folders__tab--active {
	color: var(--color-main-text);
}

.team-folders__tab--active::after {
	position: absolute;
	right: 0;
	bottom: -1px;
	left: 0;
	height: 3px;
	background-color: var(--color-primary-element);
	content: '';
}

.team-folders__input-row {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	align-items: flex-end;
	max-width: 500px;
}

.team-folders__input {
	flex: 1 1 180px;
	min-width: 0;
	max-width: 100%;
}

.team-folders__hint {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
	margin: 12px 0 0;
}

.team-folders__hint--quota-overrides {
	margin-bottom: 20px;
}

.team-folders__add-row {
	display: flex;
	gap: 8px;
	align-items: center;
	max-width: 500px;
	margin-bottom: 20px;
}

.team-folders__team-select {
	flex: 1;
	min-width: 0;
}

.team-folders__quota-mapping {
	max-width: 1120px;
	margin-bottom: 16px;
}

.team-folders__quota-save {
	display: flex;
	justify-content: flex-end;
	max-width: 1120px;
}

.team-folders__header,
.team-folders__row {
	display: grid;
	grid-template-columns: minmax(160px, 1fr) 100px minmax(180px, 240px) 44px;
	gap: 12px;
	align-items: center;
	min-height: 52px;
	padding: 6px 8px;
	border-bottom: 1px solid var(--color-border);
}

.team-folders__header {
	min-height: 36px;
	color: var(--color-text-maxcontrast);
	font-weight: bold;
}

.team-folders__grid--pools {
	grid-template-columns: minmax(120px, 1fr) 80px minmax(140px, 180px) minmax(160px, 200px) minmax(100px, 140px) minmax(100px, 140px) 44px;
}

.team-folders__grid--pools > :deep(.v-select.select) {
	min-width: 0;
	max-width: 100%;
}

.team-folders__pool {
	min-width: 180px;
}

.team-folders__pool-total {
	display: flex;
	align-items: center;
	gap: 4px;
}

.team-folders__team {
	display: flex;
	min-width: 0;
	flex-direction: column;
}

.team-folders__team strong {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.team-folders__options {
	display: flex;
	justify-content: flex-end;
}

@media (max-width: 1200px) {
	.team-folders__header.team-folders__grid--pools {
		display: none;
	}

	.team-folders__grid--pools {
		grid-template-columns: minmax(0, 1fr) 44px;
	}

	.team-folders__grid--pools > :nth-child(2) {
		grid-column: 1 / -1;
		grid-row: 2;
	}

	.team-folders__grid--pools > :nth-child(3),
	.team-folders__grid--pools > :nth-child(4),
	.team-folders__grid--pools > :nth-child(5),
	.team-folders__grid--pools > :nth-child(6) {
		grid-column: 1 / -1;
	}

	.team-folders__grid--pools > .team-folders__options {
		grid-column: 2;
		grid-row: 1;
	}

	.team-folders__grid--pools > [data-label]::before {
		display: block;
		color: var(--color-text-maxcontrast);
		font-size: 14px;
		content: attr(data-label);
	}
}

@media (max-width: 600px) {
	.team-folders__header {
		display: none;
	}

	.team-folders__row {
		grid-template-columns: minmax(0, 1fr) 44px;
	}

	.team-folders__row > :nth-child(2) {
		grid-column: 1 / -1;
		grid-row: 2;
	}

	.team-folders__options {
		grid-column: 2;
		grid-row: 1;
	}

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

.team-folders__search {
	flex: 0 1 320px;
	margin-bottom: 4px;
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

@media (max-width: 600px) {
	.team-folders__search {
		flex-basis: 100%;
	}
}
</style>
