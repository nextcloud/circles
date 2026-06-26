<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Team } from '../types.ts'

import { mdiCogOutline, mdiContentCopy, mdiExitToApp, mdiTrashCanOutline } from '@mdi/js'
import { showConfirmation, showError, showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import TeamAvatar from './TeamAvatar.vue'
import { logger } from '../../logger.ts'
import { useTeamsStore } from '../store.ts'

const props = defineProps<{
	team: Team
}>()

const router = useRouter()
const store = useTeamsStore()

const to = computed(() => ({ name: 'team', params: { teamId: props.team.id } }))
const isOwner = computed(() => props.team.myRole === 'owner')
const canManage = computed(() => ['owner', 'admin', 'moderator'].includes(props.team.myRole))
const canLeave = computed(() => !isOwner.value)
const canDelete = computed(() => isOwner.value)

/** Open the team and trigger its member picker (same as Contacts "Manage team"). */
async function onManage(): Promise<void> {
	await router.push(to.value)
	emit('contacts:circles:append', props.team.id)
}

/** Copy a direct link to the team. */
async function onCopyLink(): Promise<void> {
	const href = window.location.origin + router.resolve(to.value).href
	try {
		await navigator.clipboard.writeText(href)
		showSuccess(t('circles', 'Link copied to the clipboard'))
	} catch (error) {
		logger.error('Could not copy link', { error })
		showError(t('circles', 'Could not copy link to the clipboard'))
	}
}

/** Leave the team after confirmation. */
async function onLeave(): Promise<void> {
	const confirmed = await showConfirmation({
		name: t('circles', 'Leave team'),
		text: t('circles', 'Are you sure you want to leave {team}?', { team: props.team.displayName }),
		labelConfirm: t('circles', 'Leave team'),
		labelReject: t('circles', 'Cancel'),
		severity: 'warning',
	})
	if (!confirmed) {
		return
	}
	try {
		await store.leaveTeam(props.team.id)
		showSuccess(t('circles', 'You left "{name}"', { name: props.team.displayName }))
		if (router.currentRoute.value.params.teamId === props.team.id) {
			router.push({ name: 'home' })
		}
	} catch (error) {
		logger.error('Could not leave the team', { error })
		showError(t('circles', 'Could not leave the team'))
	}
}

/** Delete the team after confirmation. */
async function onDelete(): Promise<void> {
	const confirmed = await showConfirmation({
		name: t('circles', 'Delete team'),
		text: t('circles', 'Are you sure you want to delete {team}? This cannot be undone.', { team: props.team.displayName }),
		labelConfirm: t('circles', 'Delete team'),
		labelReject: t('circles', 'Cancel'),
		severity: 'error',
	})
	if (!confirmed) {
		return
	}
	try {
		await store.deleteTeam(props.team.id)
		showSuccess(t('circles', 'Team deleted'))
		if (router.currentRoute.value.params.teamId === props.team.id) {
			router.push({ name: 'home' })
		}
	} catch (error) {
		logger.error('Could not delete the team', { error })
		showError(t('circles', 'Could not delete the team'))
	}
}
</script>

<template>
	<NcAppNavigationItem :name="team.displayName" :to="to">
		<template #icon>
			<TeamAvatar :display-name="team.displayName" :size="32" />
		</template>
		<template #actions>
			<NcActionButton v-if="canManage" close-after-click @click="onManage">
				<template #icon>
					<NcIconSvgWrapper :path="mdiCogOutline" :size="20" />
				</template>
				{{ t('circles', 'Manage team') }}
			</NcActionButton>

			<NcActionButton close-after-click @click="onCopyLink">
				<template #icon>
					<NcIconSvgWrapper :path="mdiContentCopy" :size="20" />
				</template>
				{{ t('circles', 'Copy link') }}
			</NcActionButton>

			<NcActionButton v-if="canLeave" close-after-click @click="onLeave">
				<template #icon>
					<NcIconSvgWrapper :path="mdiExitToApp" :size="20" />
				</template>
				{{ t('circles', 'Leave team') }}
			</NcActionButton>

			<NcActionButton v-if="canDelete" close-after-click @click="onDelete">
				<template #icon>
					<NcIconSvgWrapper :path="mdiTrashCanOutline" :size="20" />
				</template>
				{{ t('circles', 'Delete team') }}
			</NcActionButton>
		</template>
	</NcAppNavigationItem>
</template>
