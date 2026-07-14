<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAccountGroupOutline, mdiMagnify, mdiPlus, mdiViewDashboard, mdiViewDashboardOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import TeamNavigationItem from './TeamNavigationItem.vue'
import { useTeamsStore } from '../store.ts'

const store = useTeamsStore()
const { loading } = storeToRefs(store)
const { openCreateTeamDialog } = store

const route = useRoute()
const query = ref('')

const filteredTeams = computed(() => store.searchTeams(query.value))

// Fill the Overview icon when its route is active, matching the Nextcloud
// navigation convention for the selected item.
const isOverviewActive = computed(() => route.name === 'home')
</script>

<template>
	<NcAppNavigation :aria-label="t('circles', 'Teams')">
		<template #default>
			<NcAppNavigationNew
				:text="t('circles', 'New team')"
				@click="openCreateTeamDialog()">
				<template #icon>
					<NcIconSvgWrapper :path="mdiPlus" :size="20" />
				</template>
			</NcAppNavigationNew>

			<NcAppNavigationSearch
				v-model="query"
				class="global-navigation__search"
				:label="t('circles', 'Search teams')">
				<template #icon>
					<NcIconSvgWrapper :path="mdiMagnify" :size="20" />
				</template>
			</NcAppNavigationSearch>
		</template>

		<template #list>
			<NcAppNavigationList>
				<NcAppNavigationItem
					:name="t('circles', 'Overview')"
					:to="{ name: 'home' }">
					<template #icon>
						<NcIconSvgWrapper :path="isOverviewActive ? mdiViewDashboard : mdiViewDashboardOutline" :size="20" />
					</template>
				</NcAppNavigationItem>
			</NcAppNavigationList>

			<div v-if="loading" class="global-navigation__loading">
				<NcLoadingIcon :size="32" />
			</div>

			<NcAppNavigationList v-else-if="filteredTeams.length > 0">
				<TeamNavigationItem
					v-for="team in filteredTeams"
					:key="team.id"
					:team="team" />
			</NcAppNavigationList>

			<NcEmptyContent
				v-else
				class="global-navigation__empty"
				:name="t('circles', 'No teams found')"
				:description="query ? t('circles', 'Try a different search.') : t('circles', 'Create a team to get started.')">
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccountGroupOutline" />
				</template>
			</NcEmptyContent>
		</template>
	</NcAppNavigation>
</template>
