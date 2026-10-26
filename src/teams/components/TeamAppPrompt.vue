<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script lang="ts">
import { t } from '@nextcloud/l10n'
import { defineComponent, reactive } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextField from '@nextcloud/vue/components/NcTextField'

const promptState = reactive({ open: false, title: '', value: '' })
let settle: ((value: string | null) => void) | undefined

/**
 * Resolves the active prompt with the entered value or null when cancelled.
 *
 * @param value Entered value or null when cancelled
 */
function finishPrompt(value: string | null): void {
	promptState.open = false
	settle?.(value)
	settle = undefined
}

/**
 * Opens a name prompt and resolves when submitted, cancelled, or aborted.
 *
 * @param title Dialog heading
 * @param value Initial value
 * @param signal Team-scope cancellation signal
 */
export function promptForName(title: string, value: string | undefined, signal: AbortSignal): Promise<string | null> {
	signal.throwIfAborted()
	if (settle) {
		throw new Error('Another Teams app dialog is already open')
	}
	promptState.title = title
	promptState.value = value ?? ''
	promptState.open = true
	return new Promise((resolve) => {
		const cancel = () => finishPrompt(null)
		signal.addEventListener('abort', cancel, { once: true })
		settle = (result) => {
			signal.removeEventListener('abort', cancel)
			resolve(result)
		}
	})
}

export default defineComponent({
	components: { NcButton, NcDialog, NcTextField },
	setup: () => ({ finishPrompt, promptState, t }),
})
</script>

<template>
	<NcDialog
		v-if="promptState.open"
		:name="promptState.title"
		size="small"
		@update:open="!$event && finishPrompt(null)">
		<NcTextField
			v-model="promptState.value"
			:label="promptState.title"
			@keyup.enter="promptState.value.trim() && finishPrompt(promptState.value.trim())" />
		<template #actions>
			<NcButton @click="finishPrompt(null)">
				{{ t('circles', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="!promptState.value.trim()"
				@click="finishPrompt(promptState.value.trim())">
				{{ t('circles', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>
