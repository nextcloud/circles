<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<h3 :class="$style.appContentHeading">
		<slot />
		<div v-if="loading" :class="[$style.appContentHeadingLoader, 'icon-loading-small']" />
	</h3>
</template>

<script>
// @ts-nocheck
export default {
	name: 'ContentHeading',

	props: {
		loading: {
			type: Boolean,
			default: false,
		},
	},
}
</script>

<style module lang="scss">
.app-content-heading {
	font-weight: bold;
	display: flex;
	line-height: 0;
	font-size: 1.2rem;
	margin-bottom: 16px;

	&__loader {
		margin-inline-start: 8px;
	}
}
</style>
