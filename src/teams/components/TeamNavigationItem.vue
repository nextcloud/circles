<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Team } from '../types.ts'

import { mdiCogOutline, mdiContentCopy, mdiExitToApp, mdiTrashCanOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import TeamAvatar from './TeamAvatar.vue'
import { useTeamActions } from '../composables/useTeamActions.ts'

const props = defineProps<{
	team: Team
}>()

const to = computed(() => ({ name: 'team', params: { teamId: props.team.id } }))

const { canManage, canLeave, canDelete, onManage, onCopyLink, onLeave, onDelete } = useTeamActions(() => props.team)
</script>

<template>
	<NcAppNavigationItem :name="team.displayName" :to="to">
		<template #icon>
			<TeamAvatar :displayName="team.displayName" :size="32" />
		</template>
		<template #actions>
			<NcActionButton v-if="canManage" closeAfterClick @click="onManage">
				<template #icon>
					<NcIconSvgWrapper :path="mdiCogOutline" :size="20" />
				</template>
				{{ t('circles', 'Manage team') }}
			</NcActionButton>

			<NcActionButton closeAfterClick @click="onCopyLink">
				<template #icon>
					<NcIconSvgWrapper :path="mdiContentCopy" :size="20" />
				</template>
				{{ t('circles', 'Copy link') }}
			</NcActionButton>

			<NcActionButton v-if="canLeave" closeAfterClick @click="onLeave">
				<template #icon>
					<NcIconSvgWrapper :path="mdiExitToApp" :size="20" />
				</template>
				{{ t('circles', 'Leave team') }}
			</NcActionButton>

			<NcActionButton v-if="canDelete" closeAfterClick @click="onDelete">
				<template #icon>
					<NcIconSvgWrapper :path="mdiTrashCanOutline" :size="20" />
				</template>
				{{ t('circles', 'Delete team') }}
			</NcActionButton>
		</template>
	</NcAppNavigationItem>
</template>
