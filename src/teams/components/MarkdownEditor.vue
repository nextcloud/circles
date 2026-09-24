<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import { logger } from '../../logger.ts'

/** The mounted Text editor instance, as returned by `OCA.Text.createEditor`. */
interface TextEditorHandle {
	destroy(): void
	setContent(content: string): void
	setReadOnly(value: boolean): void
}

const markdown = defineModel<string>({ default: '' })

const props = withDefaults(defineProps<{
	/** Field label, shown above the editor and used by the fallback input */
	label?: string
	/** Placeholder of the plain-text fallback input */
	placeholder?: string
	/** Disable editing */
	readOnly?: boolean
}>(), {
	label: undefined,
	placeholder: undefined,
	readOnly: false,
})

const editorEl = ref<HTMLElement | null>(null)
const textAvailable = window.OCA.Text?.createEditor !== undefined

let editor: TextEditorHandle | null = null
let unmounted = false

// The markdown last seen by the editor — guards the model watcher from
// pushing the editor's own updates back into it.
let knownValue = markdown.value

/** Mount the Text editor on the container element. */
async function setupEditor(): Promise<void> {
	editor?.destroy()
	editor = null
	if (!editorEl.value) {
		return
	}
	try {
		const created = await window.OCA.Text!.createEditor!({
			el: editorEl.value,
			content: markdown.value,
			readOnly: props.readOnly,
			autofocus: false,
			onUpdate: ({ markdown: value }) => {
				knownValue = value
				markdown.value = value
			},
		})
		// The unmount may have hit while the editor was being created.
		if (unmounted) {
			created.destroy()
			return
		}
		editor = created
	} catch (error) {
		logger.error('Could not create the markdown editor', { error })
	}
}

watch(markdown, (value) => {
	if (value === knownValue) {
		return
	}
	knownValue = value
	editor?.setContent(value)
})

watch(() => props.readOnly, (value) => editor?.setReadOnly(value))

onMounted(setupEditor)

onBeforeUnmount(() => {
	unmounted = true
	editor?.destroy()
	editor = null
})
</script>

<template>
	<NcTextArea
		v-if="!textAvailable"
		v-model="markdown"
		:label="label"
		:placeholder="placeholder"
		:disabled="readOnly" />

	<div
		v-else
		class="markdown-editor"
		role="group"
		:aria-label="label">
		<span v-if="label" class="markdown-editor__label">{{ label }}</span>
		<div ref="editorEl" class="markdown-editor__editor" />
	</div>
</template>

<style lang="scss" scoped>
.markdown-editor {
	&__label {
		display: block;
		margin-block-end: var(--default-grid-baseline);
		color: var(--color-text-maxcontrast);
	}

	&__editor {
		border: 2px solid var(--color-border-maxcontrast);
		border-radius: var(--border-radius-large);
		min-height: 120px;
		max-height: 320px;
		overflow-y: auto;

		// Image uploads need a file context the description does not have
		:deep(.text-editor__wrapper button.entry-action__image-upload) {
			display: none;
		}

		:deep(.text-readonly-bar) {
			display: none !important;
		}

		// Keep the menubar above the surrounding dialog content; can be
		// dropped once the Text app sets the z-index itself.
		:deep(.text-menubar) {
			z-index: 1 !important;
		}
	}
}
</style>
