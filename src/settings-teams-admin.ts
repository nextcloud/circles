/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import TeamsAdminSettings from './components/TeamsAdminSettings.vue'

import 'vite/modulepreload-polyfill'

const app = createApp(TeamsAdminSettings)
app.mount('#vue-admin-teams')
