/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { teamResourceRoute } from './resource.ts'

describe('team resource routes', () => {
	it.each(['deck', 'tasks', 'talk'])('uses the same route for %s', (provider) => {
		expect(teamResourceRoute('team-1', provider, 7)).toEqual({
			name: 'team-resource',
			params: { teamId: 'team-1', providerId: provider, resourceId: '7' },
		})
	})

	it('keeps opaque identifiers scoped to their provider and safely encoded', () => {
		const router = createRouter({
			history: createMemoryHistory(),
			routes: [{ name: 'team-resource', path: '/team/:teamId/resource/:providerId/:resourceId', component: {} }],
		})
		const target = router.resolve(teamResourceRoute('team-1', 'tasks', 'list/7#8'))
		expect(target.params.resourceId).toBe('list/7#8')
		expect(target.path).toBe('/team/team-1/resource/tasks/list%2F7%238')
	})
})
