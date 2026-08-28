<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { SharedResource } from '../types.ts'

import { mdiAccountGroupOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { useStore } from 'vuex'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import ListItem from '@nextcloud/vue/components/NcListItem'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import ContentHeading from '../team-page/components/CircleDetails/ContentHeading.vue'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const props = defineProps<{
	teamId: string
}>()

const store = useStore()
const circle = computed(() => store.getters.getCircle(props.teamId))
const isMember = computed(() => circle.value?.isMember ?? false)

// The resources and the team folder come from the shared per-team store;
// the list updates reactively when a resource is created elsewhere (e.g. a
// collective from the sidebar's create menu).
const resourcesStore = useTeamResourcesStore()
const teamResources = computed(() => resourcesStore.forTeam(props.teamId))

// The folder is part of the filter below, so wait for its probe as well.
const loading = computed(() => !teamResources.value.resourcesChecked
	|| (!teamResources.value.folderChecked && !teamResources.value.folderError))

// The team folder and the collective have their own tabs, so they are
// filtered out of the shared resources. The groupfolders provider lists
// every folder the team can access with no flag marking the team's own
// folder, hence the id comparison.
const resources = computed<SharedResource[]>(() => {
	const folder = teamResources.value.folder
	return teamResources.value.resources.filter((resource) => {
		if (resource.provider.id === 'collectives') {
			return false
		}
		return folder === null
			|| resource.provider.id !== 'groupfolders'
			|| String(resource.id) !== String(folder.id)
	})
})

const groupedResources = computed(() => {
	return resources.value.reduce<Record<string, { name: string, resources: SharedResource[] }>>((acc, resource) => {
		const providerId = resource.provider.id
		if (!acc[providerId]) {
			acc[providerId] = {
				// The team's own folder is filtered out above, so the
				// groupfolders provider only contributes regular group
				// folders here — label the section accordingly.
				name: providerId === 'groupfolders'
					? t('circles', 'Group folders')
					: resource.provider.name,
				resources: [],
			}
		}
		acc[providerId].resources.push(resource)
		return acc
	}, {})
})

</script>

<template>
	<div class="team-home">
		<NcEmptyContent
			v-if="!isMember"
			:name="t('circles', 'You are not a member of {circle}', { circle: circle?.displayName ?? '' })">
			<template #icon>
				<NcIconSvgWrapper :path="mdiAccountGroupOutline" />
			</template>
		</NcEmptyContent>

		<div v-else-if="loading" class="team-home__loading">
			<NcLoadingIcon :size="44" />
		</div>

		<template v-else>
			<div
				v-for="(group, providerId) in groupedResources"
				:key="providerId"
				class="circle-details-section"
				:class="`circle-details-section--${providerId}`">
				<div class="section-header">
					<ContentHeading>{{ group.name }}</ContentHeading>
				</div>
				<ul class="item-list">
					<ListItem
						v-for="resource in group.resources"
						:key="resource.id"
						:href="resource.url"
						:name="resource.label">
						<template #icon>
							<NcIconSvgWrapper
								v-if="resource.iconSvg"
								:svg="resource.iconSvg"
								class="resource__icon" />
							<img
								v-else-if="resource.iconURL"
								:src="resource.iconURL"
								:alt="resource.label"
								class="resource__icon">
							<FileDocumentOutline v-else :size="20" />
						</template>
					</ListItem>
				</ul>
			</div>
		</template>
	</div>
</template>

<style lang="scss" scoped>
.team-home {
	height: 100%;
	overflow-y: auto;
	padding: 20px;

	&__loading {
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.circle-details-section {
		margin-bottom: 2rem;
		max-width: 500px;
		margin-inline: auto;

		.section-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			width: 100%;
			margin-bottom: 4px;

			:deep(h2),
			:deep(h3) {
				line-height: 2px;
				margin: 4px 0 8px 0;
			}
		}

		.item-list {
			list-style: none;
			padding: 0;
			margin: 0;
			display: flex;
			flex-direction: column;
			gap: 2px;
			max-height: 300px;
			overflow-y: auto;

			// Remove left padding added in ListItem (external component)
			:deep(.list-item__wrapper) {
				padding-inline-start: 0;
			}

			:deep(.resource__icon) {
				width: 44px;
				height: 44px;
				display: flex;
				align-items: center;
				justify-content: center;
				text-align: center;
				color: var(--color-main-text);
				svg {
					width: 20px;
					height: 20px;
					fill: currentColor;
					path, rect, circle, polygon, polyline, ellipse, line {
						fill: currentColor;
					}
				}
				img {
					border-radius: var(--border-radius-pill);
					overflow: hidden;
					width: 32px;
					height: 32px;
				}
			}
		}

		// Per-provider image treatment, as in the contacts app teams UI:
		// circular Talk conversation avatars, small radius for Files previews.
		&.circle-details-section--talk .item-list :deep(img.resource__icon) {
			border-radius: var(--border-radius-pill);
		}

		&.circle-details-section--files .item-list :deep(img.resource__icon) {
			border-radius: var(--border-radius-small);
		}
	}
}
</style>
