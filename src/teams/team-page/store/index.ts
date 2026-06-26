// @ts-nocheck
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createStore } from 'vuex'
import circles from './circles.js'

// Register the (non-namespaced) circles module from Contacts so the ported
// team page components can use this.$store.getters.getCircle etc.
export default createStore({
	modules: { circles },
})
