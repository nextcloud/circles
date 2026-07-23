<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { logger } from '../logger.ts'

const BYTES_PER_MB = 1024 * 1024

/**
 * Convert a byte quota to a human-readable MB string.
 *
 * @param bytes - The quota in bytes
 */
function bytesToMb(bytes: number): string {
	if (bytes === 0) {
		return '0'
	}
	const mb = bytes / BYTES_PER_MB
	return String(parseFloat(mb.toFixed(2)))
}

const teamFolderAutoCreate = ref(Boolean(loadState('circles', 'teamFolderAutoCreate', true)))
const teamFolderDefaultQuota = ref(bytesToMb(Number(loadState('circles', 'teamFolderDefaultQuota', 0))))

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
 * The user enters the value in MB; it is stored as bytes on the server.
 */
async function onSaveQuota() {
	const quotaMb = Number(teamFolderDefaultQuota.value)
	if (Number.isNaN(quotaMb) || quotaMb < 0) {
		showError(t('circles', 'Quota must be a non-negative number.'))
		return
	}

	const quotaBytes = Math.round(quotaMb * BYTES_PER_MB)
	if (await updateAppConfig('team_folder_default_quota', quotaBytes.toString())) {
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
				<NcTextField
					v-model="teamFolderDefaultQuota"
					:label="t('circles', 'Default quota (in MB)')"
					:placeholder="t('circles', '0 for unlimited')"
					type="number"
					min="0"
					step="1"
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
