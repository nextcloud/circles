<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcPopover
		:shown="isPopoverOpen"
		popup-role="dialog"
		@update:shown="handlePopoverToggle">
		<template #trigger>
			<NcButton
				variant="secondary"
				:disabled="loading"
				:aria-describedby="`tooltip-${resourceType.id}`"
				@click="openPopover">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="20" />
					<slot v-else name="icon" />
				</template>
				{{ resourceType.label }}
			</NcButton>
		</template>
		<div v-if="!resourceType.noInput" :class="$style.resourceCreationPopover">
			<div :class="$style.popoverContent">
				<NcTextField
					:model-value="inputValue"
					:placeholder="resourceType.placeholder"
					:label="resourceType.inputLabel"
					@update:value="updateInput"
					@input="updateInput" />
				<div :class="$style.popoverActions">
					<NcButton
						variant="secondary"
						:aria-label="t('circles', 'Close')"
						@click="closePopover">
						<template #icon>
							<CloseOutlineIcon :size="20" />
						</template>
					</NcButton>
					<NcButton
						variant="primary"
						:aria-label="t('circles', 'Save')"
						:disabled="!canCreate"
						@click="createResource">
						<template #icon>
							<CheckOutlineIcon :size="20" />
						</template>
					</NcButton>
				</div>
			</div>
			<div v-if="resourceType.helperText" class="popover-helper-text">
				<NcNoteCard type="info">
					{{ resourceType.helperText }}
				</NcNoteCard>
			</div>
		</div>
	</NcPopover>
</template>

<script>
// @ts-nocheck
import { NcButton, NcLoadingIcon, NcNoteCard, NcPopover, NcTextField } from '@nextcloud/vue'
import CheckOutlineIcon from 'vue-material-design-icons/Check.vue'
import CloseOutlineIcon from 'vue-material-design-icons/Close.vue'

export default {
	name: 'TeamResourceButton',

	components: {
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		NcPopover,
		NcTextField,
		CloseOutlineIcon,
		CheckOutlineIcon,
	},

	props: {
		resourceType: {
			type: Object,
			required: true,
		},

		value: {
			type: String,
			default: '',
		},

		isOpen: {
			type: Boolean,
			default: false,
		},

		loading: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['update:value', 'update:isOpen', 'create'],

	computed: {
		inputValue() {
			return this.value
		},

		isPopoverOpen: {
			get() {
				return this.isOpen
			},

			set(value) {
				this.$emit('update:isOpen', value)
			},
		},

		canCreate() {
			if (this.resourceType.noInput) {
				return this.resourceType.enabled !== false
			}
			const value = this.inputValue
			const hasValue = typeof value === 'string' && value.trim().length > 0
			return hasValue && this.resourceType.enabled !== false
		},
	},

	methods: {
		openPopover() {
			if (this.resourceType.noInput) {
				this.createResource()
			} else {
				this.isPopoverOpen = true
			}
		},

		closePopover() {
			this.isPopoverOpen = false
		},

		handlePopoverToggle(shown) {
			this.isPopoverOpen = shown
		},

		updateInput(value) {
			const actualValue = typeof value === 'string' ? value : value?.target?.value || value?.value || ''
			this.$emit('update:value', actualValue)
		},

		createResource() {
			if (this.canCreate) {
				if (this.resourceType.noInput) {
					this.$emit('create', {
						resourceType: this.resourceType,
						name: '',
					})
				} else {
					const value = this.inputValue
					const name = typeof value === 'string' ? value.trim() : ''
					if (name) {
						this.$emit('create', {
							resourceType: this.resourceType,
							name,
						})
					}
				}
			}
		},
	},
}
</script>

<style module lang="scss">
.resource-creation-popover {
	padding: calc(var(--default-grid-baseline) * 4);
	min-width: 320px;

	.popover-content {
		display: flex;
		align-items: flex-end;
		gap: calc(var(--default-grid-baseline) * 2);

		.popover-actions {
			display: flex;
			gap: var(--default-grid-baseline);
			align-items: center;
		}
	}

	.popover-helper-text {
		margin-top: calc(var(--default-grid-baseline) * 4);
		margin-bottom: 0;
		width: 0;
		min-width: 100%;
	}
}
</style>

<!-- Child-component internals can't be reached from a module block, so their
     overrides stay in a scoped block where :deep() is transformed. -->
<style scoped lang="scss">
// NcTextField wrapper, rendered inside .popover-content
:deep(.input-field__main-wrapper) {
	flex: 1;
}

// NcNoteCard, rendered inside .popover-helper-text
:deep(.notecard) {
	text-align: start;
	margin: 0;
}
</style>
