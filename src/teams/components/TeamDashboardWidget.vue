<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { TeamDashboardItems, TeamDashboardWidget } from '../types.ts'

import { mdiArrowTopRight, mdiFileDocumentOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDashboardWidget from '@nextcloud/vue/components/NcDashboardWidget'
import NcDashboardWidgetItem from '@nextcloud/vue/components/NcDashboardWidgetItem'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	widget: TeamDashboardWidget
	items?: TeamDashboardItems
}>()

const buttons = computed(() => (props.widget.buttons ?? []).filter((button) => button.type !== 'setup' || props.items?.items.length === 0))
const brokenImages = ref(new Set<string>())
</script>

<template>
	<section class="team-dashboard-widget" :aria-label="widget.title">
		<h2 class="team-dashboard-widget__heading">
			<img v-if="widget.icon_url" :src="widget.icon_url" alt="">
			<span v-else :class="widget.icon_class" aria-hidden="true" />
			<a v-if="widget.widget_url" :href="widget.widget_url">{{ widget.title }}</a>
			<template v-else>
				{{ widget.title }}
			</template>
		</h2>
		<NcDashboardWidget
			:items="items?.items ?? []"
			:showItemsAndEmptyContent="Boolean(items?.halfEmptyContentMessage)"
			:halfEmptyContentMessage="items?.halfEmptyContentMessage ?? ''">
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
				<NcEmptyContent :name="items?.emptyContentMessage || t('circles', 'No content yet')" />
			</template>
		</NcDashboardWidget>
		<div v-if="items && buttons.length" class="team-dashboard-widget__actions">
			<NcButton v-for="button in buttons" :key="`${button.type}:${button.link}`" :href="button.link">
				<template #icon>
					<NcIconSvgWrapper :path="mdiArrowTopRight" />
				</template>
				{{ button.text }}
			</NcButton>
		</div>
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

		img, span {
			width: 24px;
			height: 24px;
			flex-shrink: 0;
		}

		img { filter: var(--background-invert-if-dark); }
		a { color: inherit; }
	}

	&__actions {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
	}

	&__item-icon {
		width: 44px;
		height: 44px;
		object-fit: contain;
		&--round { border-radius: 50%; }
	}
}
</style>
