<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, onMounted, ref } from 'vue'
import { useStore } from 'vuex'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import LightningBoltIcon from 'vue-material-design-icons/LightningBolt.vue'
import ContentHeading from '../team-page/components/CircleDetails/ContentHeading.vue'
import { logger } from '../../logger.ts'

interface Activity {
	activity_id: number
	user: string
	subject: string
	datetime: string | number | null
}

const props = defineProps<{
	teamId: string
}>()

const store = useStore()
const appsWebroots = ((window as typeof window & { OC?: { appswebroots?: Record<string, string> } }).OC?.appswebroots) ?? {}
const circle = computed(() => store.getters.getCircle(props.teamId))
const activityAppEnabled = computed(() => appsWebroots.activity !== undefined)

const activities = ref<Activity[]>([])
const loading = ref(false)
const loadingMore = ref(false)
const hasMore = ref(false)
const lastActivityId = ref(0)

function toTimestamp(datetime: Activity['datetime']): number | null {
	if (datetime === null || datetime === '') {
		return null
	}

	if (typeof datetime === 'number') {
		return Number.isFinite(datetime) ? datetime : null
	}

	const parsed = Date.parse(datetime)
	return Number.isNaN(parsed) ? null : parsed
}

function safeTimestamp(datetime: Activity['datetime']): number {
	return toTimestamp(datetime) ?? Date.now()
}

async function loadActivities() {
	if (loading.value || !circle.value) {
		return
	}

	loading.value = true
	try {
		const url = generateOcsUrl('/apps/activity/api/v2/activity/filter')
		const response = await axios.get(url, {
			params: {
				object_type: 'circles',
				object_id: circle.value.circleId,
				limit: 50,
			},
		})

		activities.value = response.data.ocs?.data ?? []
		hasMore.value = activities.value.length >= 50

		if (activities.value.length > 0) {
			lastActivityId.value = activities.value[activities.value.length - 1]?.activity_id ?? 0
		}
	} catch (error) {
		logger.error('Failed to load activities:', { error })
		activities.value = []
	} finally {
		loading.value = false
	}
}

async function loadMoreActivities() {
	if (loadingMore.value || lastActivityId.value === 0 || !circle.value) {
		return
	}

	loadingMore.value = true
	try {
		const url = generateOcsUrl('/apps/activity/api/v2/activity/filter')
		const response = await axios.get(url, {
			params: {
				object_type: 'circles',
				object_id: circle.value.circleId,
				limit: 50,
				since: lastActivityId.value,
			},
		})

		const newActivities = response.data.ocs?.data ?? []
		activities.value.push(...newActivities)
		hasMore.value = newActivities.length > 50

		if (newActivities.length > 0) {
			lastActivityId.value = newActivities[newActivities.length - 1]?.activity_id ?? lastActivityId.value
		}
	} catch (error) {
		logger.error('Failed to load more activities:', { error })
	} finally {
		loadingMore.value = false
	}
}

onMounted(() => {
	loadActivities()
})
</script>

<template>
	<div class="team-activity-view">
		<template v-if="circle && activityAppEnabled">
			<div class="team-activity-view__header">
				<ContentHeading>{{ t('circles', 'Team activity') }}</ContentHeading>
			</div>
			<div class="team-activity-view__panel">
				<NcLoadingIcon v-if="loading" class="team-activity-view__loading" :size="32" />
				<NcEmptyContent
					v-else-if="activities.length === 0"
					:name="t('circles', 'No activity yet')"
					class="team-activity-view__empty">
					<template #icon>
						<LightningBoltIcon :size="32" />
					</template>
				</NcEmptyContent>
				<div v-else class="team-activity-view__list">
					<div v-for="activity in activities" :key="activity.activity_id" class="activity-item">
						<NcAvatar
							:user="activity.user"
							:displayName="activity.user"
							:size="32"
							class="activity-item__avatar" />
						<div class="activity-item__content">
							<p class="activity-item__subject">
								{{ activity.subject }}
							</p>
							<NcDateTime
								v-if="toTimestamp(activity.datetime) !== null"
								class="activity-item__time"
								:timestamp="safeTimestamp(activity.datetime)"
								:ignoreSeconds="true" />
							<span v-else class="activity-item__time">
								{{ t('circles', 'Unknown time') }}
							</span>
						</div>
					</div>
					<NcButton
						v-if="hasMore"
						:disabled="loadingMore"
						variant="tertiary"
						class="team-activity-view__load-more"
						@click="loadMoreActivities">
						<template #icon>
							<NcLoadingIcon v-if="loadingMore" :size="16" />
						</template>
						{{ t('circles', 'Load more') }}
					</NcButton>
				</div>
			</div>
		</template>

		<NcEmptyContent
			v-else
			:name="t('circles', 'Activity unavailable')"
			:description="t('circles', 'The Activity app is disabled or this team could not be loaded.')" />
	</div>
</template>

<style scoped lang="scss">
.team-activity-view {
	height: 100%;
	display: flex;
	flex-direction: column;
	padding: 20px;
	gap: 12px;

	&__header {
		display: flex;
		align-items: center;
	}

	&__panel {
		flex: 1 1 auto;
		min-height: 0;
		border: 1px solid var(--color-border);
		border-radius: var(--border-radius-large);
		background-color: var(--color-main-background);
		display: flex;
		flex-direction: column;
		overflow-y: auto;
	}

	&__loading {
		display: flex;
		justify-content: center;
		align-items: center;
		height: 100%;
	}

	&__empty {
		flex: 1;
		display: flex;
		justify-content: center;
		align-items: center;
	}

	&__list {
		display: flex;
		flex-direction: column;
		gap: 8px;
		padding: 12px;
	}

	&__load-more {
		align-self: center;
		margin-top: 12px;
	}

	:deep(.empty-content) {
		flex: 1 1 auto;
	}
}

.activity-item {
	display: flex;
	gap: 8px;
	padding: 8px;
	border-radius: 4px;
	transition: background-color 0.2s;

	&:hover {
		background-color: var(--color-background-hover);
	}

	&__avatar {
		flex-shrink: 0;
	}

	&__content {
		flex: 1;
		min-width: 0;
		display: flex;
		flex-direction: column;
		justify-content: center;
	}

	&__subject {
		margin: 0;
		padding: 0;
		font-size: 14px;
		color: var(--color-text);
		word-wrap: break-word;
		overflow-wrap: break-word;
	}

	&__time {
		margin: 2px 0 0 0;
		padding: 0;
		font-size: 12px;
		color: var(--color-text-lighter);
	}
}
</style>
