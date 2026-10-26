/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppResource, TeamAppResourcePickerOptions } from './types.ts'

import { reactive } from 'vue'

export const NAVIGATION_ENTRY_LIMIT = 100

export const resourcePickerState = reactive({
	open: false,
	title: '',
	resources: [] as TeamAppResource[],
	selectedIds: [] as string[],
})

let settle: ((ids: string[] | null) => void) | undefined

/**
 * Resolve the active resource selection without persisting it.
 *
 * @param ids Selected resource IDs, or null when cancelled
 */
export function finishResourcePicker(ids: string[] | null): void {
	if (!settle) {
		return
	}
	const selected = ids === null ? null : [...new Set(ids)]
	if (selected && (selected.length > NAVIGATION_ENTRY_LIMIT
		|| selected.some((id) => !resourcePickerState.resources.some((resource) => resource.id === id)))) {
		throw new Error('Invalid Teams resource selection')
	}
	const resolve = settle
	settle = undefined
	resourcePickerState.open = false
	resourcePickerState.resources = []
	resourcePickerState.selectedIds = []
	resolve(selected)
}

/**
 * Choose from resources already authorized by the owning provider.
 *
 * @param options Resource candidates and current selection
 * @param signal Team scope cancellation
 */
export function chooseTeamResources(options: TeamAppResourcePickerOptions, signal: AbortSignal): Promise<string[] | null> {
	signal.throwIfAborted()
	if (settle) {
		throw new Error('Another Teams resource picker is already open')
	}
	const ids = new Set<string>()
	for (const resource of options.resources) {
		if (!resource.id || new TextEncoder().encode(resource.id).length > 64 || !resource.label || ids.has(resource.id)) {
			throw new Error('Invalid or duplicate Teams resource candidate')
		}
		ids.add(resource.id)
	}
	resourcePickerState.title = options.title
	resourcePickerState.resources = options.resources.map((resource) => ({ ...resource }))
	resourcePickerState.selectedIds = [...new Set(options.selectedIds ?? [])].filter((id) => ids.has(id))
	resourcePickerState.open = true
	return new Promise((resolve) => {
		const cancel = () => finishResourcePicker(null)
		signal.addEventListener('abort', cancel, { once: true })
		settle = (selected) => {
			signal.removeEventListener('abort', cancel)
			resolve(selected)
		}
	})
}
