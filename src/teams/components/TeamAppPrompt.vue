<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { finishPrompt, promptState } from '../integrations/prompt.ts'
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
