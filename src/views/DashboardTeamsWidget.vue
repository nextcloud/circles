<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { ITeam } from '../types.ts'

import { mdiAccountGroupOutline, mdiAlertCircleOutline } from '@mdi/js'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { nextTick, onMounted, ref, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import TeamsList from '../components/TeamsList.vue'
import { logger } from '../logger.ts'

const LOADING_LIMIT = 3
const createTeamHref = generateUrl('/apps/contacts/#/circles')

const teamsList = useTemplateRef('teamsListKey')

const shownTeams = ref<ITeam[]>([])
const loading = ref(false)
const hasError = ref(false)
const currentApiOffset = ref(0)
const hasMoreTeams = ref(true)

onMounted(() => loadTeams())

/**
 * @param isLoadMore - If more teams should be appended or the content should be replaced
 */
async function loadTeams(isLoadMore: boolean = false) {
	loading.value = true
	hasError.value = false

	try {
		const params = new URLSearchParams({
			limit: LOADING_LIMIT.toString(),
			offset: currentApiOffset.value.toString(),
		})

		const { data } = await axios.get<OCSResponse>(generateOcsUrl(`apps/circles/teams/dashboard/widget?${params}`))
		const teams = data.ocs.data || []

		// Process teams data that already includes members and resources
		// @ts-expect-error TODO: we should add types to the ocs response
		const processedTeams: ITeam[] = teams.map((team) => ({
			id: team.singleId,
			displayName: team.displayName || team.name,
			url: team.url,
			// @ts-expect-error TODO: we should add types to the ocs response
			members: (team.members || []).map((member) => ({
				userId: member.userId || member.singleId,
				displayName: member.displayName,
				type: member.type,
				isUser: member.type === 1, // TYPE_USER = 1
				url: generateUrl(`/u/${member.userId || member.singleId}`),
			})),
			resources: team.resources || [],
		}))

		if (isLoadMore) {
			shownTeams.value.push(...processedTeams)
			currentApiOffset.value += LOADING_LIMIT
		} else {
			shownTeams.value = processedTeams
			currentApiOffset.value = LOADING_LIMIT // Set offset for next load

			nextTick(() => {
				if (teamsList.value) {
					teamsList.value.scrollTop()
				}
			})
		}

		// Check if there are more teams
		hasMoreTeams.value = teams.length === LOADING_LIMIT
	} catch (error) {
		hasError.value = true
		logger.error('Failed to load teams', { error })
		showError(t('circles', 'Failed to load teams'))
		if (!isLoadMore) {
			shownTeams.value = []
		}
	} finally {
		loading.value = false
	}
}

/**
 * Trigger loading more teams
 */
async function loadMoreTeams() {
	if (!hasMoreTeams.value || loading.value) {
		return
	}

	await loadTeams(true)
}
</script>

<template>
	<div class="teams-dashboard-widget">
		<NcLoadingIcon v-if="loading" :size="48" />
		<NcEmptyContent
			v-else-if="hasError"
			:name="t('circles', 'Failed to load teams')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiAlertCircleOutline" />
			</template>
			<template #action>
				<NcButton @click="loadTeams()">
					{{ t('circles', 'Try again') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<NcEmptyContent
			v-else-if="shownTeams.length === 0"
			:name="t('circles', 'No teams found')"
			:description="t('circles', 'Join or create teams to see them here.')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiAccountGroupOutline" />
			</template>
			<template #action>
				<NcButton :href="createTeamHref">
					{{ t('circles', 'Create your first team') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<div v-else class="teams-dashboard-widget__container">
			<TeamsList
				ref="teamsListKey"
				:teams="shownTeams" />

			<!-- Show More Button -->
			<div v-if="hasMoreTeams" class="teams-dashboard-widget__actions">
				<NcButton
					class="teams-dashboard-widget__show-more"
					:disabled="loading"
					variant="secondary"
					wide
					@click="loadMoreTeams">
					{{ loading ? t('circles', 'Loading…') : t('circles', 'More teams') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<style scoped lang="scss">
.teams-dashboard-widget {
	padding: 2px;
	height: 100%;
	display: flex;
	flex-direction: column;
	box-sizing: border-box;

	* {
		box-sizing: border-box;
	}

	&__container {
		display: flex;
		flex-direction: column;
		height: 100%;
		flex: 1;
	}

	&__actions {
		display: flex;
		padding: 4px 0 0 0;
		margin-top: auto;
	}
}
</style>
