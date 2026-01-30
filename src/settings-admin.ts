/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'

import 'vite/modulepreload-polyfill'

const app = createApp(AdminSettings)
app.mount('#vue-admin-federated-teams')
