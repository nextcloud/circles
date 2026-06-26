/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ITeam } from '../types.ts'

import { shallowMount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import TeamMembers from './TeamMembers.vue'
import TeamResources from './TeamResources.vue'
import TeamsListItem from './TeamsListItem.vue'

/**
 * Build a minimal team fixture. Override only the fields a given test cares about.
 *
 * @param overrides - partial team to merge over the defaults
 */
function makeTeam(overrides: Partial<ITeam> = {}): ITeam {
	return {
		id: 'team-1',
		displayName: 'Marketing',
		url: 'https://cloud.example/apps/contacts/circle/team-1',
		members: [],
		resources: [],
		...overrides,
	}
}

describe('TeamsListItem', () => {
	// shallowMount stubs child components (TeamMembers, TeamResources, NcIconSvgWrapper),
	// so these tests assert TeamsListItem's own behaviour without their internals.

	it('renders the team name and links to the team URL', () => {
		const team = makeTeam()
		const wrapper = shallowMount(TeamsListItem, { props: { team } })

		expect(wrapper.text()).toContain('Marketing')
		expect(wrapper.get('a').attributes('href')).toBe(team.url)
	})

	it('hides members and resources when the team has none', () => {
		const wrapper = shallowMount(TeamsListItem, { props: { team: makeTeam() } })

		expect(wrapper.findComponent(TeamMembers).exists()).toBe(false)
		expect(wrapper.findComponent(TeamResources).exists()).toBe(false)
	})

	it('renders members and resources when the team has them', () => {
		const team = makeTeam({
			members: [
				{ singleId: 's1', displayName: 'Alice', type: 1, isUser: true, url: '#' },
			],
			resources: [
				{ id: 'r1', name: 'Notes', fallbackIcon: '', iconUrl: '', url: '#' },
			],
		})
		const wrapper = shallowMount(TeamsListItem, { props: { team } })

		expect(wrapper.findComponent(TeamMembers).exists()).toBe(true)
		expect(wrapper.findComponent(TeamResources).exists()).toBe(true)
	})
})
