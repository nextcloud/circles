<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { useStore } from 'vuex'
import CircleDetails from '../team-page/components/CircleDetails.vue'

const props = defineProps<{
	teamId: string
}>()

const store = useStore()
const circle = computed(() => store.getters.getCircle(props.teamId))
</script>

<template>
	<CircleDetails v-if="circle" :circle="circle" />
</template>
