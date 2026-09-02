/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamFolder } from '../api.ts'

import { flushPromises, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TeamFolderView from './TeamFolderView.vue'
import { useTeamResourcesStore } from '../resourcesStore.ts'

/**
 * The component reads these at construction time, so the mock values must be
 * in place before mount. `vi.hoisted` keeps them available to the factory
 * mocks below.
 */
const loadState = vi.hoisted(() => vi.fn((app: string, key: string, fallback: unknown) => fallback))
const getTeamFolder = vi.hoisted(() => vi.fn<(teamId: string) => Promise<TeamFolder | null>>(async () => null))
const upgradeTeamFolder = vi.hoisted(() => vi.fn<(teamId: string) => Promise<TeamFolder>>(async () => ({ id: 1, mountPoint: 'Team' })))
const showError = vi.hoisted(() => vi.fn())
const busEmit = vi.hoisted(() => vi.fn())
const getCircle = vi.hoisted(() => vi.fn())

vi.mock('@nextcloud/initial-state', () => ({ loadState }))
vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'owner-1', isAdmin: false }),
}))
vi.mock('@nextcloud/dialogs', () => ({ showError }))
vi.mock('@nextcloud/event-bus', () => ({
	emit: busEmit,
	subscribe: vi.fn(),
	unsubscribe: vi.fn(),
}))
vi.mock('@nextcloud/l10n', () => ({
	t: (app: string, text: string, vars?: Record<string, unknown>) => {
		if (vars) {
			return text.replace(/\{(\w+)\}/g, (_, key: string) => String(vars[key] ?? `{${key}}`))
		}
		return text
	},
}))
vi.mock('vuex', () => ({
	useStore: () => ({ getters: { getCircle } }),
}))
// The resources store imports the full api surface; only the folder
// functions are exercised here.
vi.mock('../api.ts', () => ({
	getTeamFolder,
	upgradeTeamFolder,
	fetchTeamPages: vi.fn(async () => []),
	fetchTeamResources: vi.fn(async () => []),
	fetchTeamDeckBoards: vi.fn(async () => []),
	fetchTabOrder: vi.fn(async () => []),
	saveTabOrder: vi.fn(),
}))
// The widget instantiates a WebDAV client on setup — keep it out of the test.
vi.mock('../components/TeamFolderWidget.vue', () => ({
	default: { name: 'TeamFolderWidget', template: '<div class="team-folder-widget-stub" />' },
}))

const OWNER_CIRCLE = { isOwner: true, isAdmin: false, isPersonal: false }
const MEMBER_CIRCLE = { isOwner: false, isAdmin: false, isPersonal: false }

/**
 * Mount the view with explicit permission and feature flags.
 *
 * @param flags - circle permissions, provider availability, linked folder
 */
function mountView(flags: {
	circle?: Record<string, unknown> | null
	providerAvailable?: boolean
	folder?: TeamFolder | null
} = {}) {
	loadState.mockImplementation((app: string, key: string, fallback: unknown) => {
		if (app !== 'circles') {
			return fallback
		}
		if (key === 'teamFolderProviderAvailable') {
			return flags.providerAvailable ?? true
		}
		return fallback
	})
	getTeamFolder.mockImplementation(async () => flags.folder ?? null)
	getCircle.mockImplementation(() => ('circle' in flags ? flags.circle : OWNER_CIRCLE))

	// One pinia for the component and the spec, so the folder load below
	// lands in the store instance the view reads.
	const pinia = createPinia()
	setActivePinia(pinia)

	const wrapper = shallowMount(TeamFolderView, {
		props: { teamId: 'team-1' },
		global: {
			plugins: [pinia],
			stubs: {
				// Render name/description/action so the empty-state texts and
				// the create button are visible in the rendered HTML.
				NcEmptyContent: {
					props: ['name', 'description'],
					template: '<div class="empty-stub"><span>{{ name }}</span><span>{{ description }}</span><slot name="action" /></div>',
				},
				NcButton: {
					template: '<button class="nc-button-stub"><slot /></button>',
				},
			},
		},
	})

	// The view only reads the store; the team page owns the initial load,
	// and the spec plays that role.
	useTeamResourcesStore().ensureFolder('team-1')

	return wrapper
}

describe('TeamFolderView empty state (team folder upgrade)', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('offers creating the folder to owners when the provider is available', async () => {
		const wrapper = mountView({ providerAvailable: true })
		await flushPromises()

		const html = wrapper.html()
		expect(html).toContain('Create one to share files with the whole team.')
		expect(html).toContain('Create team folder')
	})

	it('shows the "ask owner" hint to plain members', async () => {
		const wrapper = mountView({ circle: MEMBER_CIRCLE, providerAvailable: true })
		await flushPromises()

		const html = wrapper.html()
		expect(html).toContain('Ask a team owner to create one.')
		expect(html).not.toContain('Create team folder')
	})

	it('shows the "ask owner" hint while the circle is unknown', async () => {
		const wrapper = mountView({ circle: null, providerAvailable: true })
		await flushPromises()

		expect(wrapper.html()).toContain('Ask a team owner to create one.')
	})

	it('shows the "ask admin" hint when the provider is missing', async () => {
		const wrapper = mountView({ providerAvailable: false })
		await flushPromises()

		const html = wrapper.html()
		expect(html).toContain('Ask your administrator to enable the Team Folders app.')
		expect(html).not.toContain('Create team folder')
	})

	it('shows the folder widget instead of the empty state when a folder is linked', async () => {
		const wrapper = mountView({ folder: { id: 42, mountPoint: 'Marketing' } })
		await flushPromises()

		expect(wrapper.findComponent({ name: 'TeamFolderWidget' }).exists()).toBe(true)
		expect(wrapper.html()).not.toContain('No team folder yet')
	})

	it('shows neither widget nor empty state while loading', () => {
		const wrapper = mountView()

		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(true)
		expect(wrapper.html()).not.toContain('No team folder yet')
		expect(wrapper.findComponent({ name: 'TeamFolderWidget' }).exists()).toBe(false)
	})

	it('shows the empty state without an error when no folder is linked', async () => {
		const wrapper = mountView({ folder: null })
		await flushPromises()

		expect(wrapper.html()).toContain('No team folder yet')
		expect(showError).not.toHaveBeenCalled()
	})

	it('reports an unexpected loading error', async () => {
		getTeamFolder.mockRejectedValueOnce(new Error('Network error'))
		const wrapper = mountView()
		await flushPromises()

		expect(wrapper.html()).toContain('Team folder unavailable')
	})

	it('creates the team folder and shows the widget', async () => {
		const wrapper = mountView()
		await flushPromises()

		await wrapper.get('.nc-button-stub').trigger('click')
		await flushPromises()

		expect(upgradeTeamFolder).toHaveBeenCalledWith('team-1')
		expect(wrapper.findComponent({ name: 'TeamFolderWidget' }).exists()).toBe(true)
	})

	it('reports a creation error and clears its loading state', async () => {
		upgradeTeamFolder.mockRejectedValueOnce(new Error('Network error'))
		const wrapper = mountView()
		await flushPromises()

		await wrapper.get('.nc-button-stub').trigger('click')
		await flushPromises()

		expect(showError).toHaveBeenCalledWith('Could not create the team folder')
		// The action stays usable after the failure
		expect(wrapper.get('.nc-button-stub').attributes('disabled')).toBeUndefined()
	})
})
