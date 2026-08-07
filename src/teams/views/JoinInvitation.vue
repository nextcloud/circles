<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="wrapper">
		<NcEmptyContent v-if="membershipStatus === 'LOADING'" :name="t('circles', 'Loading')">
			<template #icon>
				<NcLoadingIcon />
			</template>
		</NcEmptyContent>

		<NcEmptyContent
			v-else-if="membershipStatus === 'MEMBER'"
			:name="t('circles', 'Invitation to the team')">
			<template #description>
				<div
					v-html="t('circles', 'You are already a member of the team <b>{circleName}</b>.', { circleName })" />
			</template>
			<template #action>
				<NcButton :href="circleUrl">
					{{ t('circles', 'Go to team page') }}
				</NcButton>
			</template>
		</NcEmptyContent>

		<NcEmptyContent
			v-else-if="membershipStatus === 'REQUESTED_MEMBERSHIP'"
			:name="t('circles', 'Invitation to the team')">
			<template #description>
				<div
					v-html="t('circles', 'You already requested to join the team <b>{circleName}</b>.', { circleName })" />
			</template>
			<template #action>
				<NcButton :href="homeUrl">
					{{ t('circles', 'Go back') }}
				</NcButton>
			</template>
		</NcEmptyContent>

		<NcEmptyContent
			v-else-if="membershipStatus === 'NOT_A_MEMBER'"
			:name="t('circles', 'Invitation to the team')">
			<template #description>
				<div
					v-html="t('circles', 'Do you want to join the team <b>{circleName}</b>?', { circleName })" />
			</template>
			<template #action>
				<NcButton variant="primary" @click="acceptInvitation">
					{{ t('circles', 'Join') }}
				</NcButton>
				<NcButton :href="homeUrl">
					{{ t('circles', 'Go back') }}
				</NcButton>
			</template>
		</NcEmptyContent>

		<NcEmptyContent
			v-else-if="membershipStatus === 'INVALID'"
			:name="t('circles', 'Invalid invitation link')">
			<template #description>
				{{ t('circles', 'This invitation link is not valid or has expired.') }}
			</template>
			<template #action>
				<NcButton :href="homeUrl">
					{{ t('circles', 'Go back') }}
				</NcButton>
			</template>
		</NcEmptyContent>
	</div>
</template>

<script setup lang="ts">
import { showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { getInvitation, joinInvitation } from '../team-page/services/circles.ts'

const route = useRoute()
const router = useRouter()

const invitationCode = computed<string>(() => {
	const code = route.params.invitationCode
	return Array.isArray(code) ? code[0] : code
})
const circleId = ref('')
const circleName = ref('')
const membershipStatus = ref('LOADING')
const homeUrl = generateUrl('/apps/circles/teams')
const circleUrl = computed(() => generateUrl('/apps/circles/teams/team/{circleId}', { circleId: circleId.value }))

onMounted(async () => {
	try {
		const invitation = await getInvitation(invitationCode.value)
		circleId.value = invitation.circleId
		circleName.value = invitation.circleName
		membershipStatus.value = invitation.membershipStatus
	} catch {
		membershipStatus.value = 'INVALID'
	}
})

/**
 * Accept the invitation to join the team
 */
async function acceptInvitation() {
	try {
		await joinInvitation(invitationCode.value)

		showSuccess(t('circles', 'You have joined the team {circleName}.', { circleName: circleName.value }))

		window.setTimeout(() => {
			router.push({ name: 'team', params: { teamId: circleId.value } })
		}, 500)
	} catch {
		membershipStatus.value = 'INVALID'
	}
}
</script>

<style lang="scss" scoped>
.wrapper {
	background-color: var(--color-main-background);
	margin: calc(25 * var(--default-grid-baseline)) auto;
	width: fit-content;
	padding: calc(5 * var(--default-grid-baseline));
}

:deep(.empty-content__action) {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);
}
</style>
