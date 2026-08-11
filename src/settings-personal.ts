/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'

const app = createApp(PersonalSettings)
app.mount('#vue-personal-circles')
