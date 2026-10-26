<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiFileDocumentOutline, mdiMagnify } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { finishResourcePicker, NAVIGATION_ENTRY_LIMIT, resourcePickerState } from '../integrations/resourcePicker.ts'

const query = ref('')
const resources = computed(() => {
	const search = query.value.trim().toLocaleLowerCase()
	return resourcePickerState.resources.filter((resource) => `${resource.label} ${resource.description ?? ''}`.toLocaleLowerCase().includes(search))
})
const selectedCount = computed(() => resourcePickerState.selectedIds.length)
const atLimit = computed(() => selectedCount.value >= NAVIGATION_ENTRY_LIMIT)

watch(() => resourcePickerState.open, (open) => {
	if (open) {
		query.value = ''
	}
})

onBeforeUnmount(() => finishResourcePicker(null))
</script>

<template>
	<NcDialog
		v-if="resourcePickerState.open"
		:name="resourcePickerState.title"
		size="normal"
		@update:open="!$event && finishResourcePicker(null)">
		<div class="resource-picker">
			<NcTextField v-model="query" :label="t('circles', 'Search resources')">
				<template #icon>
					<NcIconSvgWrapper :path="mdiMagnify" :size="20" />
				</template>
			</NcTextField>
			<div class="resource-picker__summary" role="status" aria-live="polite">
				{{ t('circles', '{count} selected', { count: selectedCount }) }}
			</div>
			<ul v-if="resources.length" class="resource-picker__list">
				<li v-for="resource in resources" :key="resource.id">
					<NcCheckboxRadioSwitch
						v-model="resourcePickerState.selectedIds"
						:value="resource.id"
						:disabled="atLimit && !resourcePickerState.selectedIds.includes(resource.id)"
						type="checkbox">
						<span class="resource-picker__resource">
							<NcIconSvgWrapper v-if="resource.iconSvg" :svg="resource.iconSvg" :size="20" />
							<span v-else-if="resource.iconEmoji" class="resource-picker__icon">{{ resource.iconEmoji }}</span>
							<NcIconSvgWrapper v-else :path="resource.icon ?? mdiFileDocumentOutline" :size="20" />
							<span class="resource-picker__text">
								<span>{{ resource.label }}</span>
								<span v-if="resource.description" class="resource-picker__description">{{ resource.description }}</span>
							</span>
						</span>
					</NcCheckboxRadioSwitch>
				</li>
			</ul>
			<NcEmptyContent v-else :name="t('circles', 'No resources found')" />
			<p v-if="atLimit" class="resource-picker__limit" role="status">
				{{ t('circles', 'You can add up to {count} navigation entries.', { count: NAVIGATION_ENTRY_LIMIT }) }}
			</p>
		</div>
		<template #actions>
			<NcButton @click="finishResourcePicker(null)">
				{{ t('circles', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="selectedCount > NAVIGATION_ENTRY_LIMIT"
				@click="finishResourcePicker(resourcePickerState.selectedIds)">
				{{ t('circles', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style scoped lang="scss">
.resource-picker {
	display: flex;
	flex-direction: column;
	gap: 12px;
	min-width: 0;

	&__summary,
	&__description {
		color: var(--color-text-maxcontrast);
		font-size: 13px;
	}

	&__list {
		list-style: none;
		margin: 0;
		padding: 0;
		min-height: 120px;
		max-height: 45vh;
		overflow-y: auto;
	}

	&__resource {
		display: flex;
		align-items: center;
		gap: 12px;
		padding-block: 6px;
		min-width: 0;
	}

	&__icon {
		width: 20px;
		flex-shrink: 0;
	}

	&__text {
		display: flex;
		flex-direction: column;
		min-width: 0;
		overflow-wrap: anywhere;
	}

	&__limit {
		margin: 0;
	}
}
</style>
