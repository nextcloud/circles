/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { reactive } from 'vue'

export const promptState = reactive({ open: false, title: '', value: '' })
let settle: ((value: string | null) => void) | undefined

/**
 *
 * @param value Entered name or null when cancelled
 */
export function finishPrompt(value: string | null): void {
	promptState.open = false
	settle?.(value)
	settle = undefined
}

/**
 *
 * @param title Dialog heading
 * @param value Initial name
 * @param signal Team scope cancellation
 */
export function promptForName(title: string, value: string | undefined, signal: AbortSignal): Promise<string | null> {
	signal.throwIfAborted()
	if (settle) {
		throw new Error('Another Teams app dialog is already open')
	}
	promptState.title = title
	promptState.value = value ?? ''
	promptState.open = true
	return new Promise((resolve) => {
		const cancel = () => finishPrompt(null)
		signal.addEventListener('abort', cancel, { once: true })
		settle = (result) => {
			signal.removeEventListener('abort', cancel)
			resolve(result)
		}
	})
}
