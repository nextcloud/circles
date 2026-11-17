<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiOpenInNew } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, onBeforeUnmount, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { NcReferenceList } from '@nextcloud/vue/components/NcRichText'
import { isReferenceEntry } from '../firstTab.ts'
import { getTeamAppProvider } from '../integrations/registry.ts'
import { useTeamAppsStore } from '../integrations/store.ts'

const props = defineProps<{ teamId: string, providerId: string, entryId: string }>()
const apps = useTeamAppsStore()
const entry = computed(() => apps.entries(props.teamId).find((entry) => entry.providerId === props.providerId && entry.entryId === props.entryId))
const providerError = computed(() => apps.errors(props.teamId).includes(props.providerId))
const loading = computed(() => !apps.providerReady(props.teamId, props.providerId) && !!getTeamAppProvider(props.providerId) && !providerError.value)
const referenceUrl = computed(() => entry.value && isReferenceEntry(entry.value)
	? entry.value.referenceUrl
	: undefined)
const referenceKey = computed(() => JSON.stringify([props.teamId, props.providerId, props.entryId, entry.value?.referenceUrl]))
const scriptsReady = ref(document.readyState === 'complete')
const listeners = new AbortController()
/** Mark deferred reference scripts ready before mounting the list. */
function onScriptsReady() {
	scriptsReady.value = true
	listeners.abort()
}
if (!scriptsReady.value) {
	const options = { signal: listeners.signal, once: true }
	document.addEventListener('DOMContentLoaded', onScriptsReady, options)
	window.addEventListener('load', onScriptsReady, options)
}
onBeforeUnmount(() => listeners.abort())
</script>

<template>
	<div class="team-app-view">
		<NcLoadingIcon v-if="loading" :size="44" />
		<template v-else>
			<NcEmptyContent v-if="providerError || !entry" :name="t('circles', 'Could not open the view')" />
			<template v-else-if="referenceUrl">
				<div class="team-app-view__toolbar">
					<NcButton
						v-if="entry.href"
						:href="entry.href"
						target="_blank"
						rel="noopener noreferrer"
						variant="tertiary"
						size="small">
						<template #icon>
							<NcIconSvgWrapper :path="mdiOpenInNew" :size="18" />
						</template>
						{{ t('circles', 'Open {name}', { name: entry.label }) }}
					</NcButton>
				</div>
				<NcReferenceList
					v-if="scriptsReady"
					:key="referenceKey"
					:text="referenceUrl"
					:limit="1"
					interactive
					displayFallback />
				<NcLoadingIcon v-else :size="44" />
			</template>
			<NcButton
				v-else-if="entry.href"
				:href="entry.href"
				target="_blank"
				rel="noopener noreferrer">
				{{ t('circles', 'Open {name}', { name: entry.label }) }}
			</NcButton>
		</template>
	</div>
</template>

<style scoped>
.team-app-view {
	height: 100%;
	display: flex;
	flex-direction: column;
	gap: 16px;
	padding: 8px 16px 16px 16px;
	overflow: auto;
}

.team-app-view__toolbar {
	display: flex;
	align-items: center;
	justify-content: end;
	gap: 12px;
}
</style>
