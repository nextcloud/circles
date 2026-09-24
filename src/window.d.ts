/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

interface DashboardRegisterContext {
	id: string
	title: string
	iconClass: string
	iconUrl: string
	url?: string
}

type DashboardRegisterCallback = (el: HTMLElement, context: DashboardRegisterContext) => void

/** The mounted editor instance returned by the Text app's editor factories. */
interface TextEditorHandle {
	destroy(): void
	setContent(content: string): void
	setReadOnly(value: boolean): void
}

declare global {
	interface Window {
		OCA: {
			Dashboard: {
				register: (appid: string, callback: DashboardRegisterCallback) => void
			}
			/** Present when the Text app is enabled. */
			Text?: {
				createCollaborativeEditor: (options: {
					el: HTMLElement
					fileId: number
					filePath: string
				}) => Promise<{ destroy(): void }>
				/**
				 * Mount a Text editor; without a `fileId` it edits plain
				 * markdown content instead of a file session.
				 */
				createEditor?: (options: {
					el: HTMLElement
					content?: string
					readOnly?: boolean
					autofocus?: boolean
					onUpdate?: (event: { markdown: string }) => void
				}) => Promise<TextEditorHandle>
			}
		}
	}
}

export { }
