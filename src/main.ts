/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './teams/App.vue'
import { logger } from './logger.ts'
import LegacyGlobalMixin from './teams/team-page/mixins/LegacyGlobalMixin.js'
import store from './teams/team-page/store/index.ts'
import { router } from './teams/router.ts'

// OCS endpoints require this header; the ported Contacts services rely on a
// global default rather than setting it per request.
axios.defaults.headers.common['OCS-APIRequest'] = 'true'

logger.debug('Mounting Teams app')

const app = createApp(App)
app.use(createPinia())
// Vuex store from the ported Contacts team page; coexists with Pinia during
// the migration (the new Teams code uses the Pinia `teams` store).
app.use(store)
app.use(router)
// Provides t/n/logger/OC/OCA to the ported (Options API) Contacts components.
app.mixin(LegacyGlobalMixin)
app.mount('#circles-teams')
