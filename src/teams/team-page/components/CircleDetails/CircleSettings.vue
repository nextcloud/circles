<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="circle-settings">
		<ul>
			<li v-for="(configs, title) in PUBLIC_CIRCLE_CONFIG" :key="title" class="circle-config">
				<ContentHeading class="circle-config__title">
					{{ title }}
				</ContentHeading>

				<ul class="circle-config__list">
					<NcCheckboxRadioSwitch
						v-for="(label, config) in configs"
						:key="'circle-config' + config"
						:model-value="isChecked(config)"
						:loading="loading === config"
						:disabled="loading !== false"
						wrapper-element="li"
						@update:model-value="onChange(Number(config), $event)">
						{{ label }}
					</NcCheckboxRadioSwitch>
				</ul>
			</li>
		</ul>

		<CirclePasswordSettings :circle="circle" />

		<!-- leave circle -->
		<NcButton
			v-if="circle.canLeave"
			variant="warning"
			@click="$emit('leave')">
			<template #icon>
				<IconLogout :size="16" />
			</template>
			{{ t('circles', 'Leave team') }}
		</NcButton>

		<!-- delete circle -->
		<NcButton
			v-if="circle.canDelete"
			variant="error"
			href="#"
			@click.prevent.stop="$emit('delete')">
			<template #icon>
				<IconDelete :size="20" />
			</template>
			{{ t('circles', 'Delete team') }}
		</NcButton>
	</div>
</template>

<script setup lang="ts">
import type Circle from '../../models/circle.ts'

import { showConfirmation, showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { ref } from 'vue'
import IconLogout from 'vue-material-design-icons/Logout.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import CirclePasswordSettings from './CirclePasswordSettings.vue'
import ContentHeading from './ContentHeading.vue'
import { CircleConfigs, PUBLIC_CIRCLE_CONFIG } from '../../models/constants.ts'
import { CircleEdit, editCircle } from '../../services/circles.ts'
import logger from '../../services/logger.js'

const props = defineProps<{
	circle: Circle
}>()

const emit = defineEmits<{
	(e: 'leave'): void
	(e: 'delete'): void
	(e: 'close-settings-popover'): void
}>()

const loading = ref<number | false>(false)

/**
 * Whether the given config bit is enabled on the circle.
 *
 * @param config - The circle config bit
 */
function isChecked(config: number): boolean {
	return (props.circle.config & config) !== 0
}

/**
 * On toggle, add or remove the config bitwise
 *
 * @param config - The circle config to manage
 * @param checked - Checked or not
 */
async function onChange(config: number, checked: boolean) {
	logger.debug(`Circle config ${config} is set to ${checked}`)

	if (checked && config === CircleConfigs.FEDERATED) {
		emit('close-settings-popover')
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

/**
 * Ask the user to confirm enabling federation for this circle.
 */
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

<style lang="scss" scoped>
.circle-settings {
	padding: 16px;
	display: flex;
	flex-direction: column;
	gap: 16px;
	max-width: 320px;
}

.circle-config {
	&__title {
		user-select: none;
		margin-top: 22px;
	}
}
</style>
