/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import DashboardTeamsWidget from './views/DashboardTeamsWidget.vue'
import { logger } from './logger.ts'

const app = createApp(DashboardTeamsWidget)
let mounted = false

window.addEventListener('DOMContentLoaded', () => {
	logger.debug('Registering teams widget with dashboard')

	window.OCA.Dashboard.register('circles', (el) => {
		logger.debug('Mounting teams widget to element', { element: el })

		// Vue 3 does not replace the wrapper so we must enforce 100% height
		el.style.height = '100%'
		if (mounted) {
			app.unmount()
		}
		app.mount(el)
		mounted = true
	})
})
