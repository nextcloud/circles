/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Team } from '../types.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useTeamActions } from './useTeamActions.ts'

vi.mock('@nextcloud/auth', () => ({ getCurrentUser: vi.fn() }))
vi.mock('@nextcloud/dialogs', () => ({
	showConfirmation: vi.fn(),
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))
vi.mock('@nextcloud/l10n', () => ({ t: (app: string, text: string) => text }))
vi.mock('vue-router', () => ({ useRouter: () => ({}) }))
vi.mock('../store.ts', () => ({ useTeamsStore: () => ({}) }))
vi.mock('../../logger.ts', () => ({ logger: { error: vi.fn() } }))

describe('useTeamActions copy link', () => {
	const writeText = vi.fn()
	let team: Team | undefined

	beforeEach(() => {
		vi.clearAllMocks()
		writeText.mockResolvedValue(undefined)
		vi.stubGlobal('navigator', { clipboard: { writeText } })
		vi.stubGlobal('_oc_webroot', '')
		vi.stubGlobal('OC', { config: { modRewriteWorking: true } })
		team = {
			id: 'MhoB14OeDtLNDtgvfPJgNadWVQ4TU4n',
			displayName: 'Team',
			description: '',
			isFavorite: false,
			favoritePosition: null,
			memberCount: 1,
			myRole: 'member',
			members: [],
			resources: [],
		}
	})

	afterEach(() => vi.unstubAllGlobals())

	it.each([
		['', true, '/t/'],
		['', false, '/index.php/t/'],
		['/nextcloud', true, '/nextcloud/t/'],
		['/nextcloud', false, '/nextcloud/index.php/t/'],
	])('copies a short link with webroot %s and rewriting %s', async (webroot, rewrite, path) => {
		vi.stubGlobal('_oc_webroot', webroot)
		vi.stubGlobal('OC', { config: { modRewriteWorking: rewrite } })

		await useTeamActions(() => team).onCopyLink()

		expect(writeText).toHaveBeenCalledWith(window.location.origin + path + team!.id)
		expect(showSuccess).toHaveBeenCalledWith('Link copied to the clipboard')
		expect(showError).not.toHaveBeenCalled()
	})

	it('does not copy a link before the team has loaded', async () => {
		team = undefined

		await useTeamActions(() => team).onCopyLink()

		expect(writeText).not.toHaveBeenCalled()
		expect(showSuccess).not.toHaveBeenCalled()
	})

	it('uses the currently selected team', async () => {
		const actions = useTeamActions(() => team)
		team = { ...team!, id: 'another-team' }

		await actions.onCopyLink()

		expect(writeText).toHaveBeenCalledWith(window.location.origin + '/t/another-team')
	})

	it('reports a clipboard failure', async () => {
		writeText.mockRejectedValue(new Error('Clipboard unavailable'))

		await useTeamActions(() => team).onCopyLink()

		expect(showError).toHaveBeenCalledWith('Could not copy link to the clipboard')
		expect(showSuccess).not.toHaveBeenCalled()
	})
})
