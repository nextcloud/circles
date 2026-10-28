/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './teams/App.vue'
import { logger } from './logger.ts'
import { initializeTeamApps } from './teams/integrations/registry.ts'
import { router } from './teams/router.ts'
import { installTeamAppRoutes } from './teams/router.ts'
import LegacyGlobalMixin from './teams/team-page/mixins/LegacyGlobalMixin.js'
import store from './teams/team-page/store/index.ts'

logger.debug('Mounting Teams app')

initializeTeamApps().then(() => {
	installTeamAppRoutes()
	const app = createApp(App)
	app.use(createPinia())
	app.use(store)
	app.use(router)
	app.mixin(LegacyGlobalMixin)
	app.mount('#circles-teams')
}).catch((error) => {
	logger.error('Could not start Teams', { error })
	throw error
})
