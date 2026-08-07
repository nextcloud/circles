<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
  -->

<script setup lang="ts">
import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { logger } from '../logger.ts'

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

const teamFolderAutoCreate = ref(Boolean(loadState('circles', 'teamFolderAutoCreate', true)))

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
				showError(t('circles', 'Unable to update team space config'))
				logger.error('Error while updating team folder config', { error: data.ocs })
				return false
			} else {
				throw new Error(`${data.ocs.meta.statuscode}`)
			}
		}
		return true
	} catch (error) {
		showError(t('circles', 'Unable to update team space config'))
		logger.error('Error while updating team folder config', { error })
		return false
	}
}

/**
 * Toggle automatic team folder creation
 */
function onToggleTeamFolderAutoCreate() {
	const value = teamFolderAutoCreate.value ? 'yes' : 'no'
	updateAppConfig('team_folder_auto_create', value)
}

/**
 * Save the default team folder quota.
 *
 * The selected option id is a human-readable size string (e.g. "5 GB") or
 * "0" for unlimited; it is parsed back to bytes before being stored.
 */
async function onSaveQuota() {
	if (selectedQuota.value.id === unlimitedQuota.id) {
		if (await updateAppConfig('team_folder_default_quota', '0')) {
			showSuccess(t('circles', 'Changed default team space quota'))
		}
		return
	}

	const bytes = parseFileSize(selectedQuota.value.id, true)
	if (bytes === null || bytes < 0) {
		showError(t('circles', 'Quota must be a non-negative number.'))
		return
	}

	if (await updateAppConfig('team_folder_default_quota', String(Math.round(bytes)))) {
		showSuccess(t('circles', 'Changed default team space quota'))
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('circles', 'Team spaces')"
		:description="t('circles', 'Automatically create a shared team space when a new team is created. Requires the Team Folders app to be installed and enabled.')">
		<NcCheckboxRadioSwitch
			v-model="teamFolderAutoCreate"
			type="switch"
			@update:modelValue="onToggleTeamFolderAutoCreate">
			{{ t('circles', 'Automatically create a team space') }}
		</NcCheckboxRadioSwitch>

		<div
			v-show="teamFolderAutoCreate"
			class="team-folders__sub-section">
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
				{{ t('circles', 'Default storage quota applied to each auto-created team space. Use 0 for unlimited storage.') }}
			</p>
		</div>
	</NcSettingsSection>
</template>

<style scoped>
.team-folders__sub-section {
	margin-top: 12px;
	margin-left: 44px;
	display: flex;
	flex-direction: column;
	gap: 8px;
}

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
	margin: 0;
}
</style>
