<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { logger } from '../../logger.ts'
import { positiveDeckId, teamDeckRoute } from '../deck.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const props = defineProps<{
	teamId: string
	boardId: string
	cardId?: string
}>()

const router = useRouter()
const route = useRoute()
const resourcesStore = useTeamResourcesStore()
const resources = computed(() => resourcesStore.forTeam(props.teamId))
const available = loadState<boolean>('circles', 'deckEmbeddingAvailable', false)
const boardId = computed(() => positiveDeckId(props.boardId))
const cardId = computed(() => props.cardId === undefined ? null : positiveDeckId(props.cardId))
const invalidId = computed(() => boardId.value === null || (props.cardId !== undefined && cardId.value === null))
const resourcesLoading = computed(() => !resources.value.resourcesChecked && !resources.value.boardsChecked)
const sharedBoard = computed(() => resources.value.resources.some((resource) => resource.provider.id === 'deck' && String(resource.id) === String(boardId.value)))
const registered = ref(false)
const boardReady = ref(false)
const loadError = ref(false)
const widgetKey = ref(0)
let generation = 0
let timeout: ReturnType<typeof setTimeout> | undefined

function reportError(error: unknown): void {
	logger.error('Could not load the embedded Deck board', { error, teamId: props.teamId, boardId: props.boardId })
	loadError.value = true
}

async function registerElement(): Promise<void> {
	const current = ++generation
	boardReady.value = false
	loadError.value = false
	if (!available) {
		return
	}
	clearTimeout(timeout)
	try {
		await Promise.race([
			customElements.whenDefined('nextcloud-deck-board'),
			new Promise<never>((_, reject) => {
				timeout = setTimeout(() => reject(new Error('Deck board component did not register')), 10000)
			}),
		])
		if (current === generation) {
			registered.value = true
		}
	} catch (error) {
		if (current === generation) {
			reportError(error)
		}
	} finally {
		if (current === generation) {
			clearTimeout(timeout)
		}
	}
}

function retry(): void {
	widgetKey.value++
	registerElement()
}

function onDeckError(event: Event): void {
	reportError(event instanceof CustomEvent ? event.detail : event)
}

function onNavigation(event: Event): void {
	const detail: unknown = event instanceof CustomEvent ? event.detail : null
	if (typeof detail !== 'object' || detail === null || !('boardId' in detail) || !('cardId' in detail)) {
		logger.error('Invalid Deck navigation event', { detail })
		showError(t('circles', 'Could not open the Deck resource'))
		return
	}
	const targetBoard = positiveDeckId(detail.boardId)
	const targetCard = detail.cardId === null ? null : positiveDeckId(detail.cardId)
	if (targetBoard === null || (detail.cardId !== null && targetCard === null)) {
		logger.error('Invalid Deck navigation identifiers', { detail })
		showError(t('circles', 'Could not open the Deck resource'))
		return
	}
	if (!resources.value.resources.some((resource) => resource.provider.id === 'deck' && String(resource.id) === String(targetBoard))) {
		logger.warn('Deck navigation target is outside the current team', { teamId: props.teamId, boardId: targetBoard })
		showError(t('circles', 'This board is not shared with the current team'))
		return
	}
	const target = teamDeckRoute(props.teamId, targetBoard, targetCard)
	if (router.resolve(target).fullPath !== route.fullPath) {
		router.push(target).catch((error) => {
			logger.error('Could not navigate to the Deck resource', { error })
			showError(t('circles', 'Could not open the Deck resource'))
		})
	}
}

watch(invalidId, (invalid) => {
	if (invalid) {
		logger.error('Invalid embedded Deck route', { boardId: props.boardId, cardId: props.cardId })
	}
}, { immediate: true })

onMounted(registerElement)
onUnmounted(() => {
	generation++
	clearTimeout(timeout)
})
</script>

<template>
	<div class="team-deck">
		<NcEmptyContent
			v-if="!available"
			:name="t('circles', 'Deck integration unavailable')"
			:description="t('circles', 'Ask your administrator to enable a Deck version that supports embedded boards.')" />
		<NcEmptyContent
			v-else-if="invalidId"
			:name="t('circles', 'Invalid Deck resource')" />
		<div v-else-if="resourcesLoading" class="team-deck__loading">
			<NcLoadingIcon :size="44" />
		</div>
		<NcEmptyContent
			v-else-if="!resources.resourcesChecked"
			:name="t('circles', 'Team resources unavailable')"
			:description="t('circles', 'Could not load the team resources. Please try again later.')" />
		<NcEmptyContent
			v-else-if="!sharedBoard"
			:name="t('circles', 'Board not available in this team')"
			:description="t('circles', 'This board is not shared with the current team')" />
		<NcEmptyContent
			v-else-if="loadError"
			:name="t('circles', 'Deck board unavailable')"
			:description="t('circles', 'Could not load the board. Please try again.')">
			<template #action>
				<NcButton @click="retry">
					{{ t('circles', 'Retry') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<div v-else class="team-deck__board">
			<div v-if="!boardReady" class="team-deck__loading">
				<NcLoadingIcon :size="44" />
			</div>
			<nextcloud-deck-board
				v-if="registered"
				:key="widgetKey"
				class="team-deck__element"
				:board-id.attr="boardId"
				:card-id.attr="cardId"
				@deck-ready="boardReady = true"
				@deck-error="onDeckError"
				@deck-navigation="onNavigation" />
		</div>
	</div>
</template>

<style scoped lang="scss">
.team-deck {
	position: relative;
	height: 100%;
	min-height: 0;

	&__board {
		position: relative;
		height: 100%;
	}

	&__element {
		display: block;
		height: 100%;
		min-width: 0;
	}

	&__loading {
		position: absolute;
		inset: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		pointer-events: none;
	}
}
</style>
