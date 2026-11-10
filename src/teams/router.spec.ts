/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'

vi.mock('./views/HomeView.vue', () => ({ default: {} }))
vi.mock('./views/JoinInvitation.vue', () => ({ default: {} }))
vi.mock('./views/TeamAppView.vue', () => ({ default: {} }))
vi.mock('./views/TeamFolderView.vue', () => ({ default: {} }))
vi.mock('./views/TeamHomeView.vue', () => ({ default: {} }))
vi.mock('./views/TeamLandingView.vue', () => ({ default: {} }))
vi.mock('./views/TeamMembersView.vue', () => ({ default: {} }))
vi.mock('./views/TeamPage.vue', () => ({ default: {} }))

import { router } from './router.ts'

describe('Teams routes', () => {
	it('opens entries through the generic provider route', async () => {
		await router.push({ name: 'team-app', params: { teamId: 'team', providerId: 'whiteboard', entryId: '166' } })
		expect(router.currentRoute.value.path).toBe('/team/team/app/whiteboard/166')
		expect(router.currentRoute.value.params).toMatchObject({ teamId: 'team', providerId: 'whiteboard', entryId: '166' })
	})

	it.each(['/team/team/page/42', '/team/team/whiteboard/166'])('redirects removed route %s to the overview', async (path) => {
		await router.push(path)
		expect(router.currentRoute.value.name).toBe('home')
	})
})
