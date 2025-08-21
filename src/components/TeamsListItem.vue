<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITeam } from '../types.ts'

import { mdiOpenInNew } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import TeamMembers from './TeamMembers.vue'
import TeamResources from './TeamResources.vue'

defineProps<{
	team: ITeam
}>()
</script>

<template>
	<li class="teams-list-item">
		<!-- Team Name with External Link Icon -->
		<div class="teams-list-item__header">
			<a :href="team.url" class="teams-list-item__header-link">
				<h3 class="teams-list-item__header-name">{{ team.displayName }}</h3>
				<NcIconSvgWrapper class="teams-list-item__header-icon" inline :path="mdiOpenInNew" />
			</a>
		</div>

		<TeamMembers v-if="team.members && team.members.length > 0" :members="team.members" />
		<div v-if="team.members?.length && team.resources?.length" class="teams-list-item__spacer" />
		<TeamResources
			v-if="team.resources && team.resources.length > 0"
			:resources="team.resources"
			:team-url="team.url" />
	</li>
</template>

<style scoped lang="scss">
.teams-list-item {
	padding-inline: 2px; // ensure the focus visible outline works
	padding-bottom: var(--default-grid-baseline); // ensure border looks not weird
	border-bottom: 1px solid var(--color-border-dark);

	&:last-child {
		border-bottom: none;
		padding-bottom: none;
	}

	&__spacer {
		height: calc(1.5 * var(--default-grid-baseline));
	}

	&__header {
		margin-bottom: var(--default-grid-baseline);

		&-link {
			display: flex;
			align-items: center;
			gap: calc(2 * var(--default-grid-baseline));
			text-decoration: none;
			padding-inline: 2px;

			&:focus-visible {
				// for accessibility add a focus-visible state
				outline: 2px solid var(--color-main-text);;
				border-radius: var(--border-radius-small);
			}
		}

		&-name {
			color: var(--color-main-text);
			font-size: 1.1em;
			margin: 0;
		}

		&-icon {
			color: var(--color-text-maxcontrast);
		}

		&:hover &-icon,
		&:hover &-name {
			color: var(--color-primary-element);
			transition: color var(--animation-quick) ease;
		}
	}
}
</style>
