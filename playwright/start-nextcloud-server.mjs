/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { startNextcloud, stopNextcloud } from '@nextcloud/e2e-test-server/docker'
import { readFileSync } from 'node:fs'
import process from 'node:process'

// Match the server branch to the app's supported Nextcloud version, so the
// throwaway container runs the same major the app targets (e.g. stable35).
/**
 * Start a throwaway Nextcloud container on the branch the app targets, with
 * circles bind-mounted, exposed on port 8089.
 */
async function start() {
	const appinfo = readFileSync('appinfo/info.xml').toString()
	const maxVersion = appinfo.match(/max-version="(\d+)"/)?.[1]
	const branch = maxVersion ? `stable${maxVersion}` : undefined

	return await startNextcloud(branch, true, {
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
