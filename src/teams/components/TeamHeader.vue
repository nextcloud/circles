<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type Circle from '../team-page/models/circle.ts'

import { mdiCogOutline, mdiContentCopy, mdiExitToApp, mdiTrashCanOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'
import TeamAvatar from './TeamAvatar.vue'
import { useTeamActions } from '../composables/useTeamActions.ts'
import { useTeamsStore } from '../store.ts'

const props = defineProps<{
	circle: Circle
}>()

const store = useTeamsStore()
const team = computed(() => store.getTeam(props.circle.id))

const { canManage, canLeave, canDelete, onManage, onCopyLink, onLeave, onDelete } = useTeamActions(() => team.value)
</script>

<template>
	<div class="team-header">
		<TeamAvatar :displayName="circle.displayName" :size="48" />
		<div class="team-header__info">
			<h2 class="team-header__name" :title="circle.displayName">
				{{ circle.displayName }}
			</h2>
			<div class="team-header__owner">
				<span>{{ t('circles', 'Team owner') }}</span>
				<NcUserBubble
					:user="circle.owner.userId"
					:displayName="circle.isOwner ? t('circles', 'you') : circle.owner.displayName" />
			</div>
		</div>

		<NcActions v-if="team && canManage" class="team-header__actions" :aria-label="t('circles', 'Team actions')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiCogOutline" :size="20" />
			</template>

			<NcActionButton closeAfterClick @click="onManage">
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
		</NcActions>
	</div>
</template>

<style lang="scss" scoped>
.team-header {
	display: flex;
	align-items: center;
	gap: 16px;
	padding: 20px;

	&__info {
		display: flex;
		flex-direction: column;
		gap: 2px;
		min-width: 0;
	}

	&__name {
		font-size: 1.5rem;
		font-weight: bold;
		margin: 0;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	&__owner {
		display: flex;
		align-items: center;
		gap: 4px;
		color: var(--color-text-maxcontrast);
	}

	&__actions {
		margin-inline-start: auto;
	}
}
</style>
