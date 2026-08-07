/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import AdminTeamFolders from './components/AdminTeamFolders.vue'

import 'vite/modulepreload-polyfill'

const app = createApp(AdminTeamFolders)
app.mount('#vue-admin-team-folders')
