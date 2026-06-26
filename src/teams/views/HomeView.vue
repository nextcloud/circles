<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAccountGroupOutline, mdiAlertCircleOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { storeToRefs } from 'pinia'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import TeamCard from '../components/TeamCard.vue'
import { useTeamsStore } from '../store.ts'

const store = useTeamsStore()
const { teams, loading, loadError } = storeToRefs(store)
const { loadTeams, openCreateTeamDialog } = store
</script>

<template>
	<div :class="$style.homeView">
		<div class="home-view__header">
			<h2 :class="$style.homeViewTitle">
				{{ t('circles', 'Teams') }}
			</h2>
			<p :class="$style.homeViewSubtitle">
				{{ t('circles', 'Your teams and everything shared with them across Nextcloud.') }}
			</p>
		</div>

		<div v-if="loading && teams.length === 0" :class="$style.homeViewLoading">
			<NcLoadingIcon :size="44" />
		</div>

		<NcEmptyContent
			v-else-if="loadError"
			:name="t('circles', 'Could not load teams')"
			:description="t('circles', 'Something went wrong while loading your teams.')">
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
			v-else-if="teams.length === 0"
			:name="t('circles', 'No teams yet')"
			:description="t('circles', 'Create your first team to start collaborating.')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiAccountGroupOutline" />
			</template>
			<template #action>
				<NcButton variant="primary" @click="openCreateTeamDialog()">
					{{ t('circles', 'Create your first team') }}
				</NcButton>
			</template>
		</NcEmptyContent>

		<template v-else>
			<section :class="$style.homeViewSection">
				<h3 :class="$style.homeViewSectionTitle">
					{{ t('circles', 'Your teams') }}
				</h3>
				<div :class="$style.homeViewGrid">
					<TeamCard v-for="team in teams" :key="team.id" :team="team" />
				</div>
			</section>
		</template>
	</div>
</template>

<style module lang="scss">
.home-view {
	max-width: 1200px;
	margin-inline: auto;
	padding: calc(4 * var(--default-grid-baseline));
	display: flex;
	flex-direction: column;
	gap: calc(6 * var(--default-grid-baseline));

	&__title {
		margin: 0;
		font-size: 1.5em;
		font-weight: 700;
	}

	&__subtitle {
		margin: calc(0.5 * var(--default-grid-baseline)) 0 0;
		color: var(--color-text-maxcontrast);
	}

	&__loading {
		display: flex;
		justify-content: center;
		padding: calc(10 * var(--default-grid-baseline)) 0;
	}

	&__section {
		display: flex;
		flex-direction: column;
		gap: calc(3 * var(--default-grid-baseline));
	}

	&__section-title {
		margin: 0;
		font-size: 1.1em;
		font-weight: 600;
	}

	&__grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
		gap: calc(3 * var(--default-grid-baseline));
	}
}
</style>
