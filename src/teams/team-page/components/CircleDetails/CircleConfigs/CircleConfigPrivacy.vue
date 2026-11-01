<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type Circle from '../../../models/circle.ts'

import { t } from '@nextcloud/l10n'
import TeamMembersVisibilitySettings from '../../../../components/TeamMembersVisibilitySettings.vue'
import CircleConfigCheckboxesList from './CircleConfigCheckboxesList.vue'
import { CircleConfigs } from '../../../models/constants.ts'

defineProps<{
	circle: Circle
}>()

const configs = {
	[CircleConfigs.VISIBLE]: t('circles', 'Visible to everyone'),
}
</script>

<template>
	<CircleConfigCheckboxesList :circle="circle" :configs="configs" />
	<TeamMembersVisibilitySettings
		v-if="circle.isAdmin || circle.isOwner"
		:circle="circle" />
</template>
