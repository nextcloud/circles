<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<ul>
		<li class="circle-config">
			<ul class="circle-config__list">
				<NcCheckboxRadioSwitch
					:model-value="enforcePasswordProtection"
					:loading="loading.includes(ENFORCE_PASSWORD_PROTECTION)"
					:disabled="loading.length > 0"
					wrapper-element="li"
					@update:model-value="changePasswordProtection">
					{{ t('circles', 'Enforce password protection on files shared to this team') }}
				</NcCheckboxRadioSwitch>

				<NcCheckboxRadioSwitch
					v-if="enforcePasswordProtection"
					:model-value="useUniquePassword || showUniquePasswordInput"
					:loading="loading.includes(USE_UNIQUE_PASSWORD)"
					:disabled="loading.length > 0"
					wrapper-element="li"
					@update:model-value="changeUseUniquePassword">
					{{ t('circles', 'Use a unique password for all shares to this team') }}
				</NcCheckboxRadioSwitch>

				<li class="unique-password">
					<template v-if="showUniquePasswordInput">
						<input
							v-model="uniquePassword"
							:disabled="loading.length > 0"
							:placeholder="t('circles', 'Unique password …')"
							type="text"
							@keyup.enter="saveUniquePassword">
						<NcButton
							variant="tertiary-no-background"
							:disabled="loading.length > 0 || uniquePassword.length === 0"
							@click="saveUniquePassword">
							{{ t('circles', 'Save') }}
						</NcButton>
					</template>
					<NcButton
						v-else-if="useUniquePassword"
						class="change-unique-password"
						@click="onClickChangePassword">
						{{ t('circles', 'Change unique password') }}
					</NcButton>

					<div v-if="uniquePasswordError" class="unique-password-error">
						{{ t('circles', 'Failed to save password. Please try again later.') }}
					</div>
				</li>
			</ul>
		</li>
	</ul>
</template>

<script setup lang="ts">
import type Circle from '../../models/circle.ts'

import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { computed, ref } from 'vue'
import { useStore } from 'vuex'

// Circle setting keys
const ENFORCE_PASSWORD_PROTECTION = 'enforce_password'
const USE_UNIQUE_PASSWORD = 'password_single_enabled'
const UNIQUE_PASSWORD = 'password_single'

const props = defineProps<{
	circle: Circle
}>()

const store = useStore()

const loading = ref<string[]>([])
const uniquePassword = ref('')
const uniquePasswordError = ref(false)
const showUniquePasswordInput = ref(false)

const circleId = computed<string>(() => props.circle._data.id)

const enforcePasswordProtection = computed<boolean>(() => {
	const value = props.circle._data.settings[ENFORCE_PASSWORD_PROTECTION]
	return value === '1' || value === 'true'
})

const useUniquePassword = computed<boolean>(() => {
	const value = props.circle._data.settings[USE_UNIQUE_PASSWORD]
	return value === '1' || value === 'true'
})

/**
 * Change handler for enforcePasswordProtection checkbox.
 */
async function changePasswordProtection() {
	loading.value.push(ENFORCE_PASSWORD_PROTECTION)
	try {
		const newValue = !enforcePasswordProtection.value

		// Also disable unique password setting
		if (!newValue && useUniquePassword.value) {
			await saveUseUniquePassword(false)
		}

		// Also hide password input
		if (!newValue && showUniquePasswordInput.value) {
			showUniquePasswordInput.value = false
		}

		await store.dispatch('editCircleSetting', {
			circleId: circleId.value,
			setting: {
				setting: ENFORCE_PASSWORD_PROTECTION,
				value: newValue.toString(),
			},
		})
	} finally {
		loading.value = loading.value.filter((item) => item !== ENFORCE_PASSWORD_PROTECTION)
	}
}

/**
 * Change handler for useUniquePassword checkbox.
 */
async function changeUseUniquePassword() {
	// Only update backend if the user disables the setting.
	// It will be enabled once a unique password has been set.
	if (!useUniquePassword.value) {
		showUniquePasswordInput.value = !showUniquePasswordInput.value
		return
	}

	await saveUseUniquePassword(!useUniquePassword.value)
}

/**
 * Update backend with given value for useUniquePassword.
 *
 * @param value New value
 */
async function saveUseUniquePassword(value: boolean) {
	loading.value.push(USE_UNIQUE_PASSWORD)
	try {
		await store.dispatch('editCircleSetting', {
			circleId: circleId.value,
			setting: {
				setting: USE_UNIQUE_PASSWORD,
				value: value.toString(),
			},
		})

		// Reset unique password input state if disabled
		if (!value) {
			uniquePassword.value = ''
			showUniquePasswordInput.value = false
		}
	} finally {
		loading.value = loading.value.filter((item) => item !== USE_UNIQUE_PASSWORD)
	}
}

/**
 * Persist uniquePassword to backend.
 */
async function saveUniquePassword() {
	if (uniquePassword.value.length === 0) {
		return
	}

	loading.value.push(UNIQUE_PASSWORD)
	uniquePasswordError.value = false
	try {
		if (!useUniquePassword.value) {
			await saveUseUniquePassword(true)
		}

		await store.dispatch('editCircleSetting', {
			circleId: circleId.value,
			setting: {
				setting: UNIQUE_PASSWORD,
				value: uniquePassword.value,
			},
		})

		// Show change button after saving the password
		showUniquePasswordInput.value = false
		uniquePassword.value = ''
	} catch {
		uniquePasswordError.value = true
	} finally {
		loading.value = loading.value.filter((item) => item !== UNIQUE_PASSWORD)
	}
}

/**
 * Click handler for the button to show the uniquePassword input.
 */
function onClickChangePassword() {
	showUniquePasswordInput.value = true
}
</script>

<style lang="scss" scoped>
ul {
	margin-top: -12px; // Merge with privacy settings list
}

.unique-password {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	width: 100%;

	input {
		flex: 1 auto;
		max-width: 200px;
	}

	.change-unique-password {
		margin-top: 5px;
	}

	// Force wrap error into a new line
	.unique-password-error {
		flex: 1 100%;
	}
}
</style>
