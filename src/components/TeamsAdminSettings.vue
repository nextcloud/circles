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
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { logger } from '../logger.ts'

interface GroupOption {
	gid: string
	displayName: string
}

const availableGroups = loadState<GroupOption[]>('circles', 'availableGroups', [])
const allowedGroupIds = ref<string[]>(loadState<string[]>('circles', 'teamCreationAllowedGroups', []))

const selectedAllowedGroups = ref(
	availableGroups.filter((group) => allowedGroupIds.value.includes(group.gid)),
)

/**
 * Persist app configuration via OCS settings endpoint.
 *
 * @param key - Config key
 * @param value - Config value as string
 */
async function updateAppConfig(key: string, value: string) {
	await confirmPassword()

	const url = generateOcsUrl('/apps/circles/settings/{key}', {
		appId: 'circles',
		key,
	})

	try {
		const { data } = await axios.post<OCSResponse>(url, { value })
		if (data.ocs.meta.status !== 'ok') {
			showError(t('circles', 'Unable to update teams settings'))
			logger.error('Error while updating teams settings', { error: data.ocs })
			return false
		}
		return true
	} catch (error) {
		showError(t('circles', 'Unable to update teams settings'))
		logger.error('Error while updating teams settings', { error })
		return false
	}
}

/**
 * Save allowed groups for team creation.
 */
async function onAllowedGroupsChange() {
	allowedGroupIds.value = selectedAllowedGroups.value.map((group) => group.gid)
	const ok = await updateAppConfig('team_creation_allowed_groups', JSON.stringify(allowedGroupIds.value))
	if (ok) {
		showSuccess(t('circles', 'Team creation permissions updated'))
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('circles', 'Permissions')"
		:description="t('circles', 'Control who can create new teams in the Teams app.')">
		<div class="teams-admin__field teams-admin__field--wide">
			<label class="teams-admin__label">{{ t('circles', 'Groups allowed to create teams') }}</label>
			<NcSelect
				v-model="selectedAllowedGroups"
				:options="availableGroups"
				label="displayName"
				track-by="gid"
				multiple
				:placeholder="t('circles', 'All users')"
				@update:modelValue="onAllowedGroupsChange" />
			<p class="teams-admin__hint">
				{{ t('circles', 'Leave empty to allow every user to create teams.') }}
			</p>
		</div>
	</NcSettingsSection>
</template>

<style scoped>
.teams-admin__field {
	display: flex;
	flex-direction: column;
	gap: 8px;
	max-width: 320px;
}

.teams-admin__field--wide {
	max-width: 480px;
}

.teams-admin__label {
	font-weight: bold;
}

.teams-admin__hint {
	margin: 0;
	color: var(--color-text-maxcontrast);
	font-size: 0.9em;
}
</style>
