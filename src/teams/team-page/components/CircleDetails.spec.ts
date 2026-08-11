/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamFolder } from '../../api.ts'

import { shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import Circle from '../models/circle.ts'
import CircleDetails from './CircleDetails.vue'

/**
 * The component reads these at construction time (in data()), so the mock
 * values must be in place before mount. `vi.hoisted` keeps them available to
 * the factory mock below.
 */
const loadState = vi.hoisted(() => vi.fn((app: string, key: string, fallback: unknown) => fallback))
const getTeamFolder = vi.hoisted(() => vi.fn<(teamId: string) => Promise<TeamFolder | null>>(async () => null))
const upgradeTeamFolder = vi.hoisted(() => vi.fn<(teamId: string) => Promise<TeamFolder>>(async () => ({ id: 1, mountPoint: 'Team' })))

vi.mock('@nextcloud/initial-state', () => ({ loadState }))
vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'owner-1', isAdmin: false }),
}))
vi.mock('@nextcloud/router', async (importOriginal) => {
	const actual = await importOriginal<typeof import('@nextcloud/router')>()
	return {
		...actual,
		generateUrl: (tpl: string) => tpl,
		generateOcsUrl: (tpl: string) => tpl,
		generateRemoteUrl: (tpl: string) => tpl,
	}
})
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: vi.fn(async () => ({ data: { ocs: { data: { resources: [] } } } })),
		post: vi.fn(async () => ({ data: { ocs: { data: {} } } })),
		request: vi.fn(async () => ({ data: {} })),
	},
}))
vi.mock('@nextcloud/dialogs', () => ({
	FilePickerClosed: {},
	FilePickerType: { Choose: 1 },
	getFilePickerBuilder: () => ({
		setMultiSelect: () => ({ setMimeTypeFilter: () => ({ setType: () => ({ allowDirectories: () => ({ build: () => ({}) }) }) }) }),
	}),
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))
vi.mock('@nextcloud/event-bus', () => ({ emit: vi.fn() }))
vi.mock('../../api.ts', () => ({ getTeamFolder, upgradeTeamFolder }))

/**
 * Build a Circle instance with the fields the component under test needs.
 *
 * @param overrides - partial raw data merged over the defaults
 */
function makeCircle(overrides: Record<string, unknown> = {}): Circle {
	return new Circle({
		id: 'team-1',
		displayName: 'Marketing',
		description: '',
		creation: 0,
		population: 1,
		populationInherited: 1,
		config: 0,
		settings: {},
		owner: { singleId: 'owner-1', userId: 'owner-1', displayName: 'Owner', level: 9, type: 1, isUser: true, instance: '', source: '' },
		initiator: { singleId: 'owner-1', userId: 'owner-1', displayName: 'Owner', level: 9, type: 1, isUser: true, instance: '', source: '' },
		...overrides,
	})
}

const MEMBER_INITIATOR = { singleId: 'm-1', userId: 'm-1', displayName: 'Member', level: 1, type: 1, isUser: true, instance: '', source: '' }

/**
 * Mount the component with owner-level permissions and explicit feature flags.
 *
 * @param overrides - circle raw-data overrides (e.g. initiator level)
 * @param flags - feature flags for loadState + optional pre-linked team folder
 */
function mountDetails(
	overrides: Record<string, unknown> = {},
	flags: { autoCreate?: boolean; providerAvailable?: boolean; teamFolder?: TeamFolder | null } = {},
) {
	loadState.mockImplementation((app: string, key: string, fallback: unknown) => {
		if (app !== 'circles') return fallback
		if (key === 'teamFolderAutoCreate') return flags.autoCreate ?? true
		if (key === 'teamFolderProviderAvailable') return flags.providerAvailable ?? true
		return fallback
	})
	getTeamFolder.mockImplementation(async () => flags.teamFolder ?? null)
	const circle = makeCircle(overrides)
	return shallowMount(CircleDetails, {
		props: { circle },
		global: {
			mocks: {
				t: (pkg: string, text: string, vars?: Record<string, unknown>) => {
					if (vars && typeof text === 'string') {
						return text.replace(/\{(\w+)\}/g, (_, k: string) => String(vars[k] ?? `{${k}}`))
					}
					return text
				},
				$store: {
					getters: { getCircle: () => null },
					dispatch: vi.fn(),
					commit: vi.fn(),
				},
				$router: { push: vi.fn(), resolve: () => ({ href: '#' }) },
			},
			stubs: {
				// Render NcNoteCard and NcButton slot/text content so banner
				// text is visible in the rendered HTML.
				NcNoteCard: {
					template: '<div class="nc-notecard-stub"><slot /></div>',
				},
				NcButton: {
					template: '<button class="nc-button-stub"><slot /></button>',
				},
			},
		},
	})
}

describe('CircleDetails folder button', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('renders no folder button in the Create section (banner handles it)', () => {
		const wrapper = mountDetails({}, { autoCreate: true, providerAvailable: true })
		expect(wrapper.vm.folderButtonType).toBeNull()
		// The Create section has Talk/Collective/Calendar buttons but no 'teamfolder'
		const buttons = wrapper.findAllComponents({ name: 'TeamResourceButton' })
		const ids = buttons.map((b) => b.props('resourceType')?.id)
		expect(ids).not.toContain('teamfolder')
		expect(ids).not.toContain('folder')
	})

	it('renders no folder button when a team folder already exists', async () => {
		const wrapper = mountDetails({}, {
			autoCreate: true,
			providerAvailable: true,
			teamFolder: { id: 42, mountPoint: 'Marketing' },
		})
		await vi.waitFor(() => expect(wrapper.vm.teamFolder).not.toBeNull())
		expect(wrapper.vm.folderButtonType).toBeNull()
	})
})

describe('CircleDetails team folder upgrade banner', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('renders the banner with create text for owners when provider is available', async () => {
		const wrapper = mountDetails({}, { autoCreate: true, providerAvailable: true })
		await vi.waitFor(() => expect(wrapper.vm.loadingTeamFolder).toBe(false))

		expect(wrapper.vm.showTeamFolderBanner).toBe(true)
		const html = wrapper.html()
		expect(html).toContain('Create one to share files with the whole team.')
		expect(html).toContain('Create team folder')
	})

	it('renders the banner with "ask owner" text for non-owners', async () => {
		const wrapper = mountDetails({ initiator: MEMBER_INITIATOR }, { autoCreate: true, providerAvailable: true })
		await vi.waitFor(() => expect(wrapper.vm.loadingTeamFolder).toBe(false))

		const html = wrapper.html()
		expect(html).toContain('Ask a team owner to create one.')
		expect(html).not.toContain('Create team folder')
	})

	it('renders the banner with "ask admin" text when provider is missing', async () => {
		const wrapper = mountDetails({}, { autoCreate: true, providerAvailable: false })
		await vi.waitFor(() => expect(wrapper.vm.loadingTeamFolder).toBe(false))

		const html = wrapper.html()
		expect(html).toContain('Ask your administrator to enable the Team Folders app.')
		expect(html).not.toContain('Create team folder')
	})

	it('hides the banner when a team folder exists', async () => {
		const wrapper = mountDetails({}, {
			autoCreate: true,
			providerAvailable: true,
			teamFolder: { id: 42, mountPoint: 'Marketing' },
		})
		await vi.waitFor(() => expect(wrapper.vm.teamFolder).not.toBeNull())

		expect(wrapper.vm.showTeamFolderBanner).toBe(false)
		const html = wrapper.html()
		expect(html).not.toContain('This team does not have a team folder yet.')
	})

	it('hides the banner while loading', () => {
		const wrapper = mountDetails({}, { autoCreate: true, providerAvailable: true })
		// loadTeamFolder is in-flight; loadingTeamFolder is true
		expect(wrapper.vm.loadingTeamFolder).toBe(true)
		expect(wrapper.vm.showTeamFolderBanner).toBe(false)
	})

	it('shows the banner even when auto-create is off (owner can still create)', async () => {
		const wrapper = mountDetails({}, { autoCreate: false, providerAvailable: true })
		await vi.waitFor(() => expect(wrapper.vm.loadingTeamFolder).toBe(false))

		const html = wrapper.html()
		expect(html).toContain('Create one to share files with the whole team.')
		expect(html).toContain('Create team folder')
	})
})