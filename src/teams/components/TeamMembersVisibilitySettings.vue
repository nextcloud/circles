<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type Circle from '../team-page/models/circle.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import { MemberLevels } from '../team-page/models/constants.ts'

const props = defineProps<{
	circle: Circle
}>()

const store = useStore()
const saving = ref(false)
const canEdit = computed(() => props.circle.isAdmin || props.circle.isOwner)
const permissionLevel = computed(() => String(props.circle.settings?.membersViewPermissionLevel ?? MemberLevels.MEMBER))

async function updatePermission(value: string): Promise<void> {
	if (saving.value || !canEdit.value || value === permissionLevel.value) {
		return
	}

	saving.value = true
	try {
		await store.dispatch('editCircleSetting', {
			circleId: props.circle.id,
			setting: {
				setting: 'membersViewPermissionLevel',
				value,
			},
		})
		showSuccess(t('circles', 'Member visibility updated'))
	} catch {
		showError(t('circles', 'Could not update member visibility'))
	} finally {
		saving.value = false
	}
}
</script>

<template>
	<NcRadioGroup
		:modelValue="permissionLevel"
		:label="t('circles', 'Allow viewing members for')"
		:description="t('circles', 'Applies to this team in all apps.')"
		:disabled="saving || !canEdit"
		@update:modelValue="updatePermission">
		<NcRadioGroupButton :label="t('circles', 'Admins only')" :value="String(MemberLevels.ADMIN)" />
		<NcRadioGroupButton :label="t('circles', 'Admins and moderators')" :value="String(MemberLevels.MODERATOR)" />
		<NcRadioGroupButton :label="t('circles', 'All members')" :value="String(MemberLevels.MEMBER)" />
	</NcRadioGroup>
</template>
