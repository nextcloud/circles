/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type Circle from '../team-page/models/circle.ts'
import type { Team } from '../types.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { showConfirmation, showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { logger } from '../../logger.ts'
import { useTeamsStore } from '../store.ts'

/**
 * Whether the user may create the team folder. Creating it is an owner-level
 * act — the server accepts only the team owner or a server admin — and
 * personal teams have no team folder.
 *
 * @param circle - The team's circle model (may be undefined while loading)
 */
export function canCreateTeamFolder(circle: Circle | null | undefined): boolean {
	if (!circle) {
		return false
	}
	return (circle.isOwner && !circle.isPersonal) || Boolean(getCurrentUser()?.isAdmin)
}

/**
 * Team-level actions (copy link/leave/delete) shared by the team header and
 * the settings dialog.
 *
 * @param getTeam a getter returning the team the actions apply to (may be
 * undefined while it is still loading)
 */
export function useTeamActions(getTeam: () => Team | undefined) {
	const router = useRouter()
	const store = useTeamsStore()

	const to = computed(() => ({ name: 'team', params: { teamId: getTeam()?.id } }))
	const isOwner = computed(() => getTeam()?.myRole === 'owner')
	// Management gates with deliberately different thresholds: the settings
	// entry includes moderators, while creating team resources, managing
	// pages and reordering hit endpoints the server restricts to admins+.
	const canManage = computed(() => {
		const team = getTeam()
		return !!team && ['owner', 'admin', 'moderator'].includes(team.myRole)
	})
	const isTeamAdmin = computed(() => {
		const team = getTeam()
		return !!team && ['owner', 'admin'].includes(team.myRole)
	})
	const canLeave = computed(() => !!getTeam() && !isOwner.value)
	const canDelete = computed(() => isOwner.value)

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
		const team = getTeam()
		if (!team) {
			return
		}
		const confirmed = await showConfirmation({
			name: t('circles', 'Leave team'),
			text: t('circles', 'Are you sure you want to leave {team}?', { team: team.displayName }),
			labelConfirm: t('circles', 'Leave team'),
			labelReject: t('circles', 'Cancel'),
			severity: 'warning',
		})
		if (!confirmed) {
			return
		}
		try {
			await store.leaveTeam(team.id)
			showSuccess(t('circles', 'You left "{name}"', { name: team.displayName }))
			if (router.currentRoute.value.params.teamId === team.id) {
				router.push({ name: 'home' })
			}
		} catch (error) {
			logger.error('Could not leave the team', { error })
			showError(t('circles', 'Could not leave the team'))
		}
	}

	/** Delete the team after confirmation. */
	async function onDelete(): Promise<void> {
		const team = getTeam()
		if (!team) {
			return
		}
		const confirmed = await showConfirmation({
			name: t('circles', 'Delete team'),
			text: t('circles', 'Are you sure you want to delete {team}? This cannot be undone.', { team: team.displayName }),
			labelConfirm: t('circles', 'Delete team'),
			labelReject: t('circles', 'Cancel'),
			severity: 'error',
		})
		if (!confirmed) {
			return
		}
		try {
			await store.deleteTeam(team.id)
			showSuccess(t('circles', 'Team deleted'))
			if (router.currentRoute.value.params.teamId === team.id) {
				router.push({ name: 'home' })
			}
		} catch (error) {
			logger.error('Could not delete the team', { error })
			showError(t('circles', 'Could not delete the team'))
		}
	}

	return {
		canManage,
		isTeamAdmin,
		canLeave,
		canDelete,
		onCopyLink,
		onLeave,
		onDelete,
	}
}
