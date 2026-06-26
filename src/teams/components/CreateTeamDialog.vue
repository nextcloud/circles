<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { logger } from '../../logger.ts'
import { useTeamsStore } from '../store.ts'

const emit = defineEmits<{
	close: []
}>()

const router = useRouter()
const store = useTeamsStore()

const open = ref(true)
const name = ref('')
const description = ref('')
const submitting = ref(false)

const canCreate = computed(() => name.value.trim().length > 0 && !submitting.value)

/**
 * Forward the dialog close so the parent can drop this component.
 *
 * @param value - The new open state from NcDialog
 */
function onUpdateOpen(value: boolean): void {
	if (!value) {
		emit('close')
	}
}

/** Create the team, navigate into it and close the dialog. */
async function submit(): Promise<void> {
	if (!canCreate.value) {
		return
	}
	submitting.value = true
	try {
		const team = await store.createTeam(name.value, description.value)
		showSuccess(t('circles', 'Team "{name}" created', { name: name.value.trim() }))
		if (team) {
			router.push({ name: 'team', params: { teamId: team.id } })
		}
		open.value = false
	} catch (error) {
		logger.error('Failed to create team', { error })
		showError(t('circles', 'Could not create the team'))
	} finally {
		submitting.value = false
	}
}
</script>

<template>
	<NcDialog
		:open="open"
		:name="t('circles', 'Create a new team')"
		size="normal"
		@update:open="onUpdateOpen">
		<form class="create-team" @submit.prevent="submit">
			<NcTextField
				v-model="name"
				:label="t('circles', 'Team name')"
				:placeholder="t('circles', 'e.g. Design')" />
			<NcTextArea
				v-model="description"
				:label="t('circles', 'Description (optional)')"
				:placeholder="t('circles', 'What is this team about?')"
				rows="3" />
		</form>

		<template #actions>
			<NcButton variant="tertiary" @click="open = false">
				{{ t('circles', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="!canCreate"
				@click="submit">
				{{ t('circles', 'Create team') }}
			</NcButton>
		</template>
	</NcDialog>
</template>
