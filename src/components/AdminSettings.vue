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
import { watchDebounced } from '@vueuse/core'
import { ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { logger } from '../logger.ts'

const federatedTeamsEnabled = ref(Boolean(loadState('circles', 'federatedTeamsEnabled', false)))
const federatedTeamsFrontal = ref(loadState<string>('circles', 'federatedTeamsFrontal', ''))

/**
 * Parse and validate federated teams frontal URL
 *
 * @param url - The URL to parse
 */
function parseFederatedTeamsFrontal(url: string) {
	try {
		const parsed = new URL(url)
		const scheme = parsed.protocol.replace(':', '')
		let cloudId = parsed.hostname
		const port = parsed.port
		let path = parsed.pathname

		if (!scheme || !cloudId) {
			return { scheme: null, cloudId: null, path: '' }
		}
		if (!path || path === '/') {
			path = ''
		} else {
			path = path.replace(/^\//, '').replace(/\/$/, '')
		}
		if (port) {
			cloudId += ':' + port
		}

		return { scheme, cloudId, path }
	} catch {
		return { scheme: null, cloudId: null, path: '' }
	}
}

/**
 * Update app configuration
 *
 * @param key - The config key
 * @param value - The config value
 */
async function updateAppConfig(key: string, value: string) {
	await confirmPassword()

	const url = generateOcsUrl('/apps/circles/settings/{key}', {
		appId: 'circles',
		key,
	})

	try {
		const { data } = await axios.post<OCSResponse>(url, {
			value,
		})
		if (data.ocs.meta.status !== 'ok') {
			if (data.ocs.meta.message) {
				showError(t('circles', 'Unable to update federated teams config'))
				logger.error('Error while updating federated teams config', { error: data.ocs })
			} else {
				throw new Error(`${data.ocs.meta.statuscode}`)
			}
		}
	} catch (error) {
		showError(t('circles', 'Unable to update federated teams config'))
		logger.error('Error while updating federated teams config', { error })
	}
}

/**
 * Toggle federated teams enabled state
 */
function onToggleFederatedTeams() {
	const value = federatedTeamsEnabled.value ? 'yes' : 'no'
	updateAppConfig('federated_teams_enabled', value)
}

watchDebounced(federatedTeamsFrontal, async (value) => {
	// Frontend validation to avoid unnecessary requests (actual validation happens in backend)
	const { scheme, cloudId } = parseFederatedTeamsFrontal(value)
	if (scheme === null || cloudId === null) {
		showError(t('circles', 'Invalid URL format. Please provide a valid URL.'))
		return
	}

	await updateAppConfig('federated_teams_frontal', value)
	showSuccess(t('circles', 'Changed frontal cloud URL'))
}, { debounce: 500 })
</script>

<template>
	<NcSettingsSection
		:name="t('circles', 'Federated Teams')"
		:description="t('circles', 'Federation allows you to share teams with other trusted servers and make them discoverable across instances.')">
		<NcCheckboxRadioSwitch
			v-model="federatedTeamsEnabled"
			type="switch"
			@update:model-value="onToggleFederatedTeams">
			{{ t('circles', 'Enable federated teams') }}
		</NcCheckboxRadioSwitch>

		<div
			v-show="federatedTeamsEnabled"
			class="federated-teams__sub-section">
			<NcTextField
				v-model="federatedTeamsFrontal"
				:label="t('circles', 'Frontal URL')"
				:placeholder="t('circles', 'https://…')"
				type="url"
				class="federated-teams__input" />

			<p class="federated-teams__hint">
				{{ t('circles', 'The public URL used by other instances to discover and connect to your teams.') }}
			</p>
		</div>
	</NcSettingsSection>
</template>

<style scoped>
.federated-teams__sub-section {
	margin-top: 12px;
	margin-left: 44px;
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.federated-teams__input {
	max-width: 500px;
}

.federated-teams__hint {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
	margin: 0;
}
</style>
