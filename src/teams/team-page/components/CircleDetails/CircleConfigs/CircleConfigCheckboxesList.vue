<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcFormBox>
		<NcFormBoxSwitch
			v-for="(label, config) in configs"
			:key="'circle-config' + config"
			:model-value="isChecked(Number(config))"
			:label="label"
			:disabled="loading !== false"
			@update:model-value="onChange(Number(config), $event)" />
	</NcFormBox>
</template>

<script setup lang="ts">
import { showConfirmation, showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import { ref } from 'vue'
import Circle from '../../../models/circle.ts'
import { CircleConfigs } from '../../../models/constants.ts'
import { CircleEdit, editCircle } from '../../../services/circles.ts'
import { logger } from '../../../../../logger.ts'

const props = defineProps<{
	circle: Circle
	configs: Record<string, string>
}>()

const loading = ref<number | false>(false)

function isChecked(config: number): boolean {
	return (props.circle.config & config) !== 0
}

async function onChange(config: number, checked: boolean) {
	logger.debug(`Circle config ${config} is set to ${checked}`)

	if (checked && config === CircleConfigs.FEDERATED) {
		const confirmed = await confirmEnableFederationForCircle()
		if (!confirmed) {
			return
		}
	}

	loading.value = config
	const prevConfig = props.circle.config
	const nextConfig = checked ? prevConfig | config : prevConfig & ~config

	try {
		const circleData = await editCircle(props.circle.id, CircleEdit.Config, nextConfig)
		// eslint-disable-next-line vue/no-mutating-props
		props.circle.config = circleData.config
	} catch (error) {
		logger.error('Unable to edit circle config', { prevConfig, config: nextConfig, error })
		showError(t('circles', 'An error happened during the config change'))
	} finally {
		loading.value = false
	}
}

async function confirmEnableFederationForCircle(): Promise<boolean> {
	const confirmed = await showConfirmation({
		name: t('circles', 'Confirm enabling federation'),
		text: t('circles', 'Enabling this will prevent {circle} from being a member of other teams.\nAre you sure?', {
			circle: props.circle.displayName,
		}),
		labelConfirm: t('circles', 'Enable federation'),
		labelReject: t('circles', 'Cancel'),
		severity: 'warning',
	})
	if (!confirmed) {
		logger.debug('Enable federation cancelled')
		return false
	}
	return true
}
</script>
