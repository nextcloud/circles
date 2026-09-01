/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: MIT
 */

import { configureNextcloud, runExec, startNextcloud, stopNextcloud, waitOnNextcloud } from '@nextcloud/e2e-test-server/docker'
import { readFileSync } from 'fs'
import { execSync } from 'node:child_process'

async function start() {
	const port = Number.parseInt(process.env.NEXTCLOUD_PORT ?? '8042', 10)
	const appinfo = readFileSync('appinfo/info.xml').toString()
	const maxVersion = appinfo.match(/<nextcloud min-version="\d+" max-version="(\d\d+)"\/>/)?.[1]
	let branch = 'master'
	if (maxVersion) {
		const refs = execSync('git ls-remote --refs').toString('utf-8')
		branch = refs.includes(`refs/heads/stable${maxVersion}`) ? `stable${maxVersion}` : branch
	}

	const ip = await startNextcloud(branch, true, {
		exposePort: port,
		forceRecreate: true,
	})

	await waitOnNextcloud(ip)
	await configureNextcloud(['circles'])

	process.stdout.write('\nApply custom configuration for Playwright tests\n')
	await runExec(['php', '-r', '$db = new SQLite3("data/owncloud.db");$db->busyTimeout(5000);$db->exec("PRAGMA journal_mode = wal;");'])
	process.stdout.write('├─ Enabled SQLite WAL mode for better performance\n')

	process.stdout.write('└─ Nextcloud container ready to run Playwright tests\n')
}

async function stop() {
	process.stderr.write('Stopping Nextcloud server…\n')
	await stopNextcloud()
	process.exit(0)
}

process.on('SIGTERM', stop)
process.on('SIGINT', stop)

await start()

while (true) {
	await new Promise((resolvePromise) => setTimeout(resolvePromise, 5000))
}
