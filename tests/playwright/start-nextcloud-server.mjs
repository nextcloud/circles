/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { startNextcloud, stopNextcloud } from '@nextcloud/e2e-test-server/docker'
import process from 'node:process'

async function start() {
	return await startNextcloud('master', true, {
		exposePort: 8089,
	})
}

// Start the container, then idle until Playwright tears the web server down.
await start()

process.on('beforeExit', () => {
	stopNextcloud()
})

while (true) {
	await new Promise((resolve) => setTimeout(resolve, 5000))
}
