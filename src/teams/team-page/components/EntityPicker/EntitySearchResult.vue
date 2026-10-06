<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<h4 v-if="source.heading" :key="source.id" class="entity-picker__option-caption">
		{{ t('circles', 'Add {type}', { type: source.label.toLowerCase() }) }}
	</h4>

	<NcListItem
		v-else
		:name="source.label"
		:active="isSelected"
		compact
		@click.stop.prevent="onClick(source)">
		<template #icon>
			<NcAvatar
				:user="source.shareType === ShareType.User ? source.user : undefined"
				:display-name="source.label"
				:is-no-user="source.shareType !== ShareType.User"
				:size="32"
				disable-menu
				hide-status />
		</template>
		<template v-if="source.subname" #subname>
			{{ source.subname }}
		</template>
	</NcListItem>
</template>

<script>
// @ts-nocheck
import { ShareType } from '@nextcloud/sharing'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcListItem from '@nextcloud/vue/components/NcListItem'

export default {
	name: 'EntitySearchResult',

	components: {
		NcAvatar,
		NcListItem,
	},

	props: {
		source: {
			type: Object,
			default() {
				return {}
			},
		},

		onClick: {
			type: Function,
			default() {},
		},

		selection: {
			type: Object,
			default: () => ({}),
		},
	},

	setup() {
		return {
			ShareType,
		}
	},

	computed: {
		isSelected() {
			return this.source.id in this.selection
		},
	},
}

</script>

<style lang="scss" scoped>
@use 'sass:math';

// https://uxplanet.org/7-rules-for-mobile-ui-button-design-e9cf2ea54556
// recommended is 48px
// 44px is what we choose and have very good visual-to-usability ratio
$clickable-area: 44px;

.entity-picker__option-caption {
	list-style-type: none;
	user-select: none;
	white-space: nowrap;
	text-overflow: ellipsis;
	pointer-events: none;
	box-shadow: none !important;
	line-height: $clickable-area;

	&:not(:first-child) {
		margin-top: math.div($clickable-area, 2);
	}
}
</style>
