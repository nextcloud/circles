<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAccountGroupOutline, mdiAlertCircleOutline, mdiMagnify, mdiPlus } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import { useIsDarkTheme } from '@nextcloud/vue/composables/useIsDarkTheme'
import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import TeamCard from '../components/TeamCard.vue'
import { useTeamsStore } from '../store.ts'

const store = useTeamsStore()
const { teams, loading, loadError } = storeToRefs(store)
const { loadTeams, openCreateTeamWizard } = store

const query = ref('')
const filteredTeams = computed(() => store.searchTeams(query.value))

const illustrationSrc = imagePath('circles', 'teams-illustration.svg')
const isDarkTheme = useIsDarkTheme()
</script>

<template>
	<div :class="$style.homeView">
		<div :class="[$style.homeViewBanner, { [$style.homeViewBannerDark]: isDarkTheme }]">
			<div :class="$style.homeViewBannerContent">
				<h2 :class="$style.homeViewTitle">
					{{ t('circles', 'My teams') }}
				</h2>
				<p :class="$style.homeViewSubtitle">
					{{ t('circles', 'and everything shared with them across Nextcloud.') }}
				</p>
				<div :class="$style.homeViewBannerAction">
					<NcButton variant="primary" @click="openCreateTeamWizard()">
						<template #icon>
							<NcIconSvgWrapper :path="mdiPlus" :size="20" />
						</template>
						{{ t('circles', 'New team') }}
					</NcButton>
				</div>
			</div>
			<div :class="$style.homeViewBannerIllustration">
				<img alt="" :class="$style.homeViewBannerIllustrationImage" :src="illustrationSrc">
			</div>
		</div>

		<div :class="$style.homeViewBody">
			<div :class="$style.homeViewActions">
				<NcTextField
					v-model="query"
					:class="$style.homeViewSearch"
					:label="t('circles', 'Search teams')">
					<template #icon>
						<NcIconSvgWrapper :path="mdiMagnify" :size="20" />
					</template>
				</NcTextField>
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
					<NcButton variant="primary" @click="openCreateTeamWizard()">
						{{ t('circles', 'Create your first team') }}
					</NcButton>
				</template>
			</NcEmptyContent>

			<template v-else>
				<section :class="$style.homeViewSection">
					<div v-if="filteredTeams.length > 0" :class="$style.homeViewGrid">
						<TeamCard v-for="team in filteredTeams" :key="team.id" :team="team" />
					</div>
					<NcEmptyContent
						v-else
						:name="t('circles', 'No teams found')"
						:description="t('circles', 'Try a different search.')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiMagnify" />
						</template>
					</NcEmptyContent>
				</section>
			</template>
		</div>
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

	// Banner matching the governance app's welcome box: text column beside
	// an object-fit image column, capped in height, on a radial gradient.
	&__banner {
		display: flex;
		align-items: center;
		justify-content: space-between;
		max-height: 188px;
		overflow: hidden;
		border-radius: var(--border-radius-large);
		padding: calc(6 * var(--default-grid-baseline)) calc(5 * var(--default-grid-baseline));
		background: radial-gradient(
			circle at 100% 100%,
			#dbedf7 0%,
			#1cafff 26.6%,
			#34b6fd 29.3%,
			#60c5fc 34.9%,
			#86d1fa 40.7%,
			#a5dbf9 46.7%,
			#bce3f8 53%,
			#cde8f7 59.7%,
			#d7ebf7 67%,
			#dbedf7 76%
		);

		@media screen and (max-width: 512px) {
			max-height: none;
		}
	}

	&__banner--dark {
		background: radial-gradient(
			circle at 100% 100%,
			#dbedf7 0%,
			#1cafff 35%,
			#0082c9 100%
		);
	}

	&__banner-content {
		flex: 3 0;
	}

	&__banner-illustration {
		flex: 1 1;
		text-align: center;

		@media screen and (max-width: 512px) {
			display: none;
		}
	}

	// The artwork may bleed into the banner padding to render larger
	&__banner-illustration-image {
		width: 100%;
		max-height: calc(188px - 6 * var(--default-grid-baseline));
		object-fit: contain;
	}

	&__body {
		display: flex;
		flex-direction: column;
		gap: calc(6 * var(--default-grid-baseline));
	}

	&__actions {
		display: flex;
		align-items: center;
		gap: calc(2 * var(--default-grid-baseline));
	}

	&__banner-action {
		margin-block-start: calc(3 * var(--default-grid-baseline));
	}

	&__search {
		max-width: 300px;
	}

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

	&__grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
		gap: calc(3 * var(--default-grid-baseline));
	}
}
</style>
