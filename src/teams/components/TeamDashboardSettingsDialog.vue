<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { TeamDashboardWidget } from '../types.ts'

import { mdiCheck, mdiClose, mdiRestore } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	catalog: TeamDashboardWidget[]
	widgetIds: string[] | null
	saving: boolean
	saveLayout: (widgetIds: string[] | null) => Promise<boolean>
}>()
const emit = defineEmits<{ close: [] }>()
const draft = ref([...(props.widgetIds ?? props.catalog.map((widget) => widget.id))])
const hasError = ref(false)
const choices = computed(() => [
	...props.catalog.map((widget) => ({ id: widget.id, title: widget.title, icon: widget.icon_url, missing: false })),
	...(props.widgetIds ?? []).filter((id) => !props.catalog.some((widget) => widget.id === id))
		.map((id) => ({ id, title: id, icon: '', missing: true })),
])

/**
 *
 * @param id - Widget identifier to toggle
 * @param checked - Whether the draft includes the widget
 */
function toggle(id: string, checked: boolean) {
	draft.value = checked ? [...draft.value, id] : draft.value.filter((item) => item !== id)
}

/**
 *
 * @param selection - Draft selection or null to restore automatic defaults
 */
async function save(selection: string[] | null) {
	hasError.value = false
	try {
		if (await props.saveLayout(selection)) {
			emit('close')
		} else {
			hasError.value = true
		}
	} catch {
		hasError.value = true
	}
}
</script>

<template>
	<NcDialog
		:name="t('circles', 'Customize dashboards')"
		:closeOnClickOutside="!saving"
		:noClose="saving"
		@update:open="!$event && emit('close')">
		<div class="dashboard-settings">
			<NcCheckboxRadioSwitch
				v-for="choice in choices"
				:key="choice.id"
				:modelValue="draft.includes(choice.id)"
				:disabled="saving"
				@update:modelValue="toggle(choice.id, Boolean($event))">
				<img
					v-if="choice.icon"
					class="dashboard-settings__icon"
					:src="choice.icon"
					alt="">
				{{ choice.title }}
				<span v-if="choice.missing"> ({{ t('circles', 'Unavailable') }})</span>
			</NcCheckboxRadioSwitch>
			<p v-if="choices.length === 0">
				{{ t('circles', 'No dashboards available') }}
			</p>
			<p v-if="hasError" role="alert">
				{{ t('circles', 'Could not save dashboard selection') }}
			</p>
		</div>
		<template #actions>
			<div class="dashboard-settings__actions">
				<NcButton :disabled="saving" @click="emit('close')">
					<template #icon>
						<NcIconSvgWrapper :path="mdiClose" />
					</template>
					{{ t('circles', 'Cancel') }}
				</NcButton>
				<NcButton variant="primary" :disabled="saving" @click="save(draft)">
					<template #icon>
						<NcIconSvgWrapper :path="mdiCheck" />
					</template>
					{{ t('circles', 'Save') }}
				</NcButton>
			</div>
		</template>
	</NcDialog>
</template>

<style scoped>
.dashboard-settings {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding: 8px;
	overflow-wrap: anywhere;
}

.dashboard-settings__icon {
	width: 20px;
	height: 20px;
	vertical-align: middle;
	margin-inline-end: 8px;
}

.dashboard-settings__actions {
	display: flex;
	flex-wrap: wrap;
	justify-content: flex-end;
	gap: 8px;
	width: 100%;
}

@media (max-width: 480px) {
	.dashboard-settings__actions {
		flex-direction: column;
		align-items: stretch;
	}
}
</style>
