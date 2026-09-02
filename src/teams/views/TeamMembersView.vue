<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type Member from '../team-page/models/member.ts'

import { mdiAccountMultiplePlusOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import ContentHeading from '../team-page/components/CircleDetails/ContentHeading.vue'
import MemberList from '../team-page/components/MemberList/MemberList.vue'

const props = defineProps<{
	teamId: string
}>()

const store = useStore()
const circle = computed(() => store.getters.getCircle(props.teamId))

const members = computed<Member[]>(() => Object.values(circle.value?.members ?? {}))

const memberList = ref<{ onShowPicker: (circleId: string) => void } | null>(null)

/** Open the member picker of the member list. */
function addMembers(): void {
	memberList.value?.onShowPicker(props.teamId)
}
</script>

<template>
	<div class="team-members">
		<div class="team-members__header">
			<ContentHeading>{{ t('circles', 'Members') }}</ContentHeading>
			<NcButton
				v-if="circle?.canManageMembers"
				variant="secondary"
				@click="addMembers">
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccountMultiplePlusOutline" :size="20" />
				</template>
				{{ t('circles', 'Add') }}
			</NcButton>
		</div>

		<MemberList ref="memberList" :list="members" />
	</div>
</template>

<style lang="scss" scoped>
.team-members {
	height: 100%;
	max-width: 500px;
	margin-inline: auto;
	padding-inline: 20px;
	padding-block: calc(2 * var(--default-grid-baseline));
	overflow-y: auto;

	&__header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-block-end: var(--default-grid-baseline);
	}

	:deep(.app-content-list) {
		max-width: 100%;
		border: 0;
	}
}
</style>
