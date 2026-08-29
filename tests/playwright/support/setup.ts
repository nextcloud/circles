/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { configureNextcloud } from '@nextcloud/e2e-test-server/docker'
import { test as setup } from '@playwright/test'

// Runs once (the `setup` project) before the test projects. The web server start
// only waits for the URL to respond, which happens before Nextcloud is fully
// configured, so we enable circles and finish configuration here.
setup('Configure Nextcloud', async () => {
	await configureNextcloud(['circles'])
})
