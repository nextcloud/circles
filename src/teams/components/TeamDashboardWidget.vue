<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { TeamDashboardResult, TeamDashboardWidget } from '../types.ts'

import { mdiFileDocumentOutline, mdiRefresh } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDashboardWidget from '@nextcloud/vue/components/NcDashboardWidget'
import NcDashboardWidgetItem from '@nextcloud/vue/components/NcDashboardWidgetItem'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	widget: TeamDashboardWidget
	result?: TeamDashboardResult
	loading: boolean
}>()
defineEmits<{ retry: [] }>()

const data = computed(() => props.result?.status === 'ok' ? props.result.data : undefined)
const brokenImages = ref(new Set<string>())
</script>

<template>
	<section class="team-dashboard-widget" :aria-label="widget.title">
		<h2 class="team-dashboard-widget__heading">
			<img v-if="widget.icon_url" :src="widget.icon_url" alt="">
			<span v-else :class="widget.icon_class" aria-hidden="true" />
			{{ widget.title }}
		</h2>
		<NcEmptyContent v-if="result?.status === 'error'" :name="t('circles', 'Could not load content')">
			<template #action>
				<NcButton @click="$emit('retry')">
					<template #icon>
						<NcIconSvgWrapper :path="mdiRefresh" />
					</template>
					{{ t('circles', 'Try again') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<NcDashboardWidget
			v-else
			:items="data?.items ?? []"
			:loading="loading && !data"
			:showItemsAndEmptyContent="Boolean(data?.halfEmptyContentMessage)"
			:halfEmptyContentMessage="data?.halfEmptyContentMessage ?? ''">
			<template #default="{ item }">
				<NcDashboardWidgetItem
					:mainText="item.title"
					:subText="item.subtitle"
					:targetUrl="item.link"
					:overlayIconUrl="item.overlayIconUrl">
					<template #avatar>
						<img
							v-if="item.iconUrl && !brokenImages.has(item.iconUrl)"
							class="team-dashboard-widget__item-icon"
							:class="{ 'team-dashboard-widget__item-icon--round': widget.item_icons_round }"
							:src="item.iconUrl"
							alt=""
							@error="brokenImages.add(item.iconUrl)">
						<NcIconSvgWrapper v-else :path="mdiFileDocumentOutline" :size="44" />
					</template>
				</NcDashboardWidgetItem>
			</template>
			<template #empty-content>
				<NcEmptyContent :name="data?.emptyContentMessage || t('circles', 'No content yet')" />
			</template>
		</NcDashboardWidget>
	</section>
</template>

<style scoped lang="scss">
.team-dashboard-widget {
	min-width: 0;

	&__heading {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 12px;
		font-size: 20px;
		overflow-wrap: anywhere;

		img,
		span {
			width: 24px;
			height: 24px;
			flex-shrink: 0;
		}
	}

	&__item-icon {
		width: 44px;
		height: 44px;
		object-fit: contain;

		&--round {
			border-radius: 50%;
		}
	}
}
</style>
