<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITeamResource } from '../types.ts'

defineProps<{
	resources: ITeamResource[]
	/**
	 * The URL to see the full team page
	 */
	teamUrl: string
}>()
</script>

<template>
	<div class="team-resources">
		<ul class="team-resources__list">
			<li
				v-for="resource in resources.slice(0, 5)"
				:key="resource.id"
				class="team-resources__box"
				:title="resource.name"
				:style="{ '--fallback-icon': `url('${resource.fallbackIcon}')` }">
				<a :href="resource.url" class="team-resources__link">
					<img
						:src="resource.iconUrl"
						class="team-resources__icon"
						:alt="resource.name">
				</a>
			</li>
			<li
				v-if="resources.length > 5"
				class="team-resources__box">
				<a :href="teamUrl" class="team-resources__link">
					<div class="team-resources__link-more">+{{ resources.length - 5 }}</div>
				</a>
			</li>
		</ul>
	</div>
</template>

<style scoped lang="scss">
.team-resources {
	--resources-list-gap: calc(2 * var(--default-grid-baseline));
	--resource-box-size: var(--default-clickable-area);

	&__list {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: var(--resources-list-gap);
		list-style: none;
	}

	&__box {
		display: block;
		position: relative;
		width: var(--resource-box-size);
		height: var(--resource-box-size);
		border-radius: var(--border-radius-element);
		background-color: var(--color-main-background);
		background-size: calc(var(--resource-box-size) - 4px);
		background-repeat: no-repeat;
		background-position: center;
		transition: all var(--animation-quick) ease;

		&:hover {
			transform: scale(1.1);
			background-color: var(--color-background-hover);
		}

		&:has(.team-resources__icon:error) {
			background-image: var(--fallback-icon);
		}

		&:has(.team-resources__link:focus-visible) {
			// for accessibility we need a focus visible outline
			outline: 2px solid var(--color-main-text);
		}
	}

	&__link {
		display: flex;
		inset: 0;
		width: 100%;
		height: 100%;
		text-decoration: none;
		padding: 0;
		margin: 0;
		border: none !important;
		outline: none !important;
	}

	&__link-more {
		align-self: center;
		height: fit-content;
		width: fit-content;
		margin: auto;
	}

	&__link:hover &__link-more {
		color: var(--color-primary-element);
	}

	&__icon {
		border-radius: var(--border-radius-small);
		display: block;
		height: 100%;
		width: 100%;
		object-fit: cover;

		&:error {
			display: none;
		}
	}
}

/* Responsive adjustments */
@media (max-width: 480px) {
	.team-resources {
		--resources-list-gap: var(--default-grid-baseline);
		--resource-box-size: max(var(--clickable-area-small), calc(var(--default-clickable-area) - 2 * var(--default-grid-baseline)));
	}
}
</style>
