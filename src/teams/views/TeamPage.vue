<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAlertCircleOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import { useStore } from 'vuex'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import TeamHeader from '../components/TeamHeader.vue'
import { logger } from '../../logger.ts'

const props = defineProps<{
	teamId: string
}>()

const store = useStore()

const loading = ref(true)
const circle = computed(() => store.getters.getCircle(props.teamId))

/** Load the circle and its members into the Vuex store. */
async function loadCircle(): Promise<void> {
	loading.value = true
	try {
		await store.dispatch('getCircle', props.teamId)
		await store.dispatch('getCircleMembers', props.teamId)
	} catch (error) {
		logger.error('Could not load the team', { error })
		showError(t('circles', 'Could not load the team'))
	} finally {
		loading.value = false
	}
}

watch(() => props.teamId, loadCircle, { immediate: true })
</script>

<template>
	<div class="team-page">
		<div v-if="loading && !circle" class="team-page__loading">
			<NcLoadingIcon :size="44" />
		</div>

		<NcEmptyContent
			v-else-if="!circle"
			class="team-page__missing"
			:name="t('circles', 'Team not found')"
			:description="t('circles', 'This team may have been removed.')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiAlertCircleOutline" />
			</template>
		</NcEmptyContent>

		<template v-else>
			<TeamHeader :circle="circle" />
			<div class="team-page__content">
				<router-view />
			</div>
		</template>
	</div>
</template>

<style lang="scss" scoped>
.team-page {
	display: flex;
	flex-direction: column;
	height: 100%;

	&__loading,
	&__missing {
		flex: 1 1 auto;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	&__content {
		flex: 1 1 auto;
		min-height: 0;
		overflow: hidden;
	}
}
</style>
