<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

const oidcConnected = Boolean(loadState('circles', 'oidc_connected', false))

const connectUrl = generateUrl('/apps/circles/oidc/connect')
</script>

<template>
	<NcSettingsSection
		:name="t('circles', 'Connect to OIDC provider')"
		:description="t('circles', 'Connect your account to an external OIDC provider to automatically sync your memberships with Nextcloud teams.')">
		<div class="oidc-section">
			<p v-if="oidcConnected">
				{{ t('circles', 'Your account is connected.') }}
			</p>
			<NcButton
				:href="connectUrl"
				variant="primary"
				wide>
				{{ oidcConnected ? t('circles', 'Reconnect') : t('circles', 'Connect') }}
			</NcButton>
		</div>
	</NcSettingsSection>
</template>

<style lang="scss" scoped>
.oidc-section {
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
	max-width: 300px;
}
</style>
