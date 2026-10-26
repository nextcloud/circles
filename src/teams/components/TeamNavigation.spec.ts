/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamPage } from '../api.ts'
import type { TeamNavigationEntry } from '../teamAppIntegrations/types.ts'

import { showError } from '@nextcloud/dialogs'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { computed, reactive } from 'vue'
import { useRoute } from 'vue-router'
import TeamNavigation from './TeamNavigation.vue'

const { run, actions, context, errors, getCircle, createFolder, createTeamPage, renameTeamPage, deleteTeamPage, ensurePages, refresh, push, saveOrder, appState } = vi.hoisted(() => ({
	run: vi.fn(),
	actions: vi.fn(),
	context: vi.fn(),
	errors: vi.fn(),
	getCircle: vi.fn(),
	createFolder: vi.fn(),
	createTeamPage: vi.fn(),
	renameTeamPage: vi.fn(),
	deleteTeamPage: vi.fn(),
	ensurePages: vi.fn(),
	refresh: vi.fn(),
	push: vi.fn(),
	saveOrder: vi.fn(),
	appState: { busy: false, empty: false },
}))

const resourceState = reactive({ navigation: [] as TeamNavigationEntry[], folderChecked: false, folderError: false, pagesError: false, pages: [] as TeamPage[], folder: null as { id: number, mountPoint?: string } | null })
const permissionState = reactive({ role: 'admin' })
const entryState = reactive({ handbook: true, calendar: true })

vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn(), showSuccess: vi.fn() }))
vi.mock('vue-router', async () => {
	const { reactive } = await import('vue')
	const route = reactive({ params: { teamId: 'team', fileId: '' }, name: 'team-home' })
	return {
		useRoute: () => route,
		useRouter: () => ({ push }),
	}
})
vi.mock('vuex', () => ({ useStore: () => ({ getters: { getCircle } }) }))
vi.mock('../store.ts', () => ({ useTeamsStore: () => ({ getTeam: () => null }) }))
vi.mock('../api.ts', () => ({ createTeamPage, renameTeamPage, deleteTeamPage }))
vi.mock('../resourcesStore.ts', () => ({ useTeamResourcesStore: () => ({ forTeam: () => resourceState, createFolder, saveOrder, ensurePages }) }))
vi.mock('../composables/useTeamActions.ts', () => ({
	useTeamActions: (getTeam: () => unknown) => {
		getTeam()
		return {
			canManage: computed(() => ['admin', 'owner', 'moderator'].includes(permissionState.role)),
			isTeamAdmin: computed(() => ['admin', 'owner'].includes(permissionState.role)),
		}
	},
	canCreateTeamFolder: () => true,
}))
vi.mock('../teamAppIntegrations/teamAppsStore.ts', () => ({
	useTeamAppsStore: () => ({
		run,
		refresh,
		context,
		entries: () => appState.empty
			? []
			: [{
					id: 'text-7',
					label: 'Handbook',
					icon: '\u{1F4DA}',
					providerId: 'text',
					entryId: '7',
					reference: { accessible: true, richObjectType: 'file' },
				}, {
					id: 'calendar-1',
					label: 'Calendar',
					providerId: 'calendar',
					entryId: '1',
					href: '/apps/calendar/1',
				}].filter((entry) => entry.id === 'text-7' ? entryState.handbook : entryState.calendar),
		actions,
		errors,
		get busy() { return appState.busy },
	}),
}))
vi.mock('./TeamHeader.vue', () => ({ default: { template: '<div />' } }))
vi.mock('./TeamSettingsDialog.vue', () => ({ default: { name: 'TeamSettingsDialog', props: ['open'], emits: ['update:open'], template: '<div data-settings-dialog />' } }))

describe('Teams navigation icons', () => {
	beforeEach(() => {
		run.mockReset().mockImplementation(async (action) => action.run?.())
		actions.mockReset().mockReturnValue([{ id: 'create', label: 'New', icon: '\u{1F195}' }])
		context.mockReset().mockReturnValue(undefined)
		errors.mockReset().mockReturnValue([])
		getCircle.mockReset().mockReturnValue({ isMember: true })
		vi.mocked(showError).mockClear()
		useRoute().params.teamId = 'team'
		useRoute().name = 'team-home'
		appState.busy = false
		appState.empty = false
		entryState.handbook = true
		entryState.calendar = true
		permissionState.role = 'admin'
		resourceState.navigation = [
			{ id: 'team-folder' },
			{ id: 'text-7', providerId: 'text', entryId: '7' },
			{ id: 'calendar-1', providerId: 'calendar', entryId: '1' },
			{ id: 'home' },
		]
		resourceState.folderChecked = false
		resourceState.folder = null
		resourceState.pages = []
		createTeamPage.mockReset().mockResolvedValue(undefined)
		renameTeamPage.mockReset().mockResolvedValue(undefined)
		deleteTeamPage.mockReset().mockResolvedValue(undefined)
		ensurePages.mockReset().mockResolvedValue(undefined)
		createFolder.mockReset().mockResolvedValue(undefined)
		refresh.mockReset().mockResolvedValue(undefined)
		push.mockReset().mockResolvedValue(undefined)
		saveOrder.mockReset().mockImplementation(async (_teamId: string, entries: TeamNavigationEntry[]) => {
			resourceState.navigation = entries
		})
	})

	function mountNavigation() {
		return mount(TeamNavigation, {
			global: {
				stubs: {
					NcAppNavigation: { template: '<nav><slot name="search" /><slot name="list" /><slot name="footer" /></nav>' },
					NcAppNavigationCaption: { template: '<div><span v-if="$slots.actionsTriggerIcon" class="actions-trigger"><slot name="actionsTriggerIcon" /></span><slot name="actions" /></div>' },
					NcAppNavigationItem: {
						name: 'NcAppNavigationItem',
						props: ['name', 'href', 'to', 'target', 'rel'],
						template: '<section :aria-label="name" :data-href="href" :data-to="to ? JSON.stringify(to) : undefined" :data-target="target" :data-rel="rel"><slot name="icon" /><slot name="counter" /><slot name="actions" /></section>',
					},
					NcActionButton: { props: { closeAfterClick: Boolean }, template: '<button :data-close-after-click="closeAfterClick"><slot name="icon" /><slot /></button>' },
					NcActionSeparator: { template: '<hr>' },
					NcIconSvgWrapper: true,
					NcDialog: { props: ['open'], template: '<div v-if="open" class="dialog"><slot /><slot name="actions" /></div>' },
					NcTextField: { props: ['modelValue'], emits: ['update:modelValue'], template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />' },
					NcButton: { template: '<button><slot /></button>' },
				},
			},
		})
	}

	it('creates pages from the sidebar and opens the native editor route', async () => {
		resourceState.folder = { id: 1, mountPoint: 'Team' }
		ensurePages.mockImplementation(async () => {
			resourceState.pages = [{ fileId: 42, title: 'Handbook', filePath: '/Team/.system/teams/pages/Handbook.md' }]
		})
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'New page')!.trigger('click')
		await flushPromises()
		await wrapper.get('input').setValue('Handbook')
		await wrapper.get('.dialog button').trigger('click')
		await flushPromises()
		expect(createTeamPage).toHaveBeenCalledWith('Team', 'Handbook')
		expect(ensurePages).toHaveBeenCalledWith('team', true)
		expect(push).toHaveBeenCalledWith({ name: 'team-page', params: { teamId: 'team', fileId: '42' } })
		wrapper.unmount()
	})

	it('shows native pages in saved order and renames them without changing their route', async () => {
		const page = { fileId: 42, title: 'Handbook', filePath: '/Team/.system/teams/pages/Handbook.md' }
		resourceState.pages = [page]
		resourceState.navigation.unshift({ id: 'page-42' })
		const wrapper = mountNavigation()
		const item = wrapper.findAllComponents({ name: 'NcAppNavigationItem' }).find((item) => item.props('name') === 'Handbook')
		const entry = wrapper.get('section[aria-label="Handbook"]')
		expect(JSON.parse(entry.attributes('data-to')!)).toEqual({ name: 'team-page', params: { teamId: 'team', fileId: '42' } })
		expect(item).toBeDefined()
		item!.vm.$emit('update:name', 'Guide')
		await flushPromises()
		expect(renameTeamPage).toHaveBeenCalledWith(page, 'Guide')
		expect(resourceState.navigation[0]?.id).toBe('page-42')
		wrapper.unmount()
	})

	it('confirms deletion and leaves the deleted page editor', async () => {
		const page = { fileId: 42, title: 'Handbook', filePath: '/Team/.system/teams/pages/Handbook.md' }
		resourceState.pages = [page]
		resourceState.navigation.unshift({ id: 'page-42' })
		useRoute().name = 'team-page'
		useRoute().params.fileId = '42'
		const wrapper = mountNavigation()
		await wrapper.get('section[aria-label="Handbook"] button').trigger('click')
		expect(deleteTeamPage).not.toHaveBeenCalled()
		await wrapper.get('.dialog button').trigger('click')
		await flushPromises()
		expect(deleteTeamPage).toHaveBeenCalledWith(page)
		expect(push).toHaveBeenCalledWith({ name: 'team-folder', params: { teamId: 'team' } })
		wrapper.unmount()
	})

	it('shows the app icon beside the create action', () => {
		actions.mockReturnValue([{ id: 'create', label: 'Add Example', iconUrl: '/apps/example/img/app.svg' }])
		const wrapper = mountNavigation()
		const action = wrapper.findAll('button').find((button) => button.text().includes('Add Example'))!
		expect(action.find('.team-navigation__action-icon').exists()).toBe(true)
		expect(action.get('.team-navigation__app-icon').attributes('style')).toContain('/apps/example/img/app.svg')
	})

	it('renders resource emojis in the team navigation', () => {
		const wrapper = mountNavigation()
		expect(wrapper.get('section[aria-label="Handbook"] .team-app-icon__emoji').text()).toBe('\u{1F4DA}')
	})

	it('centers action icons in the same fixed-size slot', () => {
		actions.mockReturnValue([{ id: 'create', label: 'Add Example', icon: '\u{1F195}' }])
		const wrapper = mountNavigation()
		const action = wrapper.findAll('button').find((button) => button.text().includes('Add Example'))!
		expect(action.find('.team-navigation__action-icon').exists()).toBe(true)
		expect(action.find('.team-app-icon__emoji').exists()).toBe(true)
	})

	it('does not retain or duplicate the folder command when eligibility changes', async () => {
		resourceState.folderChecked = true
		const wrapper = mountNavigation()
		const folderCommands = () => wrapper.findAll('button').filter((button) => button.text() === 'Create team folder')
		expect(folderCommands()).toHaveLength(1)
		resourceState.folder = { id: 1 }
		await flushPromises()
		expect(folderCommands()).toHaveLength(0)
		resourceState.folder = null
		await flushPromises()
		expect(folderCommands()).toHaveLength(1)
		expect(actions.mock.results[0]!.value).toHaveLength(1)
		await folderCommands()[0]!.trigger('click')
		await flushPromises()
		expect(createFolder).toHaveBeenCalledExactlyOnceWith('team')
		expect(refresh).toHaveBeenCalledExactlyOnceWith('team')
		expect(push).toHaveBeenCalledWith({ name: 'team-folder', params: { teamId: 'team' } })
		wrapper.unmount()
	})

	it.each(['creation', 'refresh', 'unmount'])('does not redirect after leaving the team during folder %s', async (stage) => {
		resourceState.folderChecked = true
		let finish!: () => void
		const pending = new Promise<void>((resolve) => {
			finish = resolve
		})
		if (stage === 'refresh') {
			refresh.mockReturnValueOnce(pending)
		} else {
			createFolder.mockReturnValueOnce(pending)
		}
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'Create team folder')!.trigger('click')
		if (stage === 'unmount') {
			wrapper.unmount()
		} else {
			useRoute().params.teamId = 'other'
		}
		finish()
		await flushPromises()
		expect(createFolder).toHaveBeenCalledExactlyOnceWith('team')
		expect(push).not.toHaveBeenCalled()
		if (stage === 'refresh') {
			expect(refresh).toHaveBeenCalledExactlyOnceWith('team')
		} else {
			expect(refresh).not.toHaveBeenCalled()
		}
		if (stage !== 'unmount') {
			wrapper.unmount()
		}
	})

	it('discards an in-progress drag when changing teams', async () => {
		const wrapper = mountNavigation()
		const entry = wrapper.get('section[aria-label="Handbook"]')
		await entry.trigger('dragstart')
		await wrapper.get('section[aria-label="Calendar"]').trigger('dragover', { clientY: 1 })
		expect(entry.classes()).toContain('team-navigation__entry--dragging')
		useRoute().params.teamId = 'other'
		await flushPromises()
		expect(entry.classes()).not.toContain('team-navigation__entry--dragging')
		await entry.trigger('dragend')
		expect(saveOrder).not.toHaveBeenCalled()
		wrapper.unmount()
	})

	it.each([
		{ from: 'Handbook', over: 'Calendar', clientY: 40 },
		{ from: 'Calendar', over: 'Handbook', clientY: 0 },
	])('persists dragging $from past $over', async ({ from, over, clientY }) => {
		const wrapper = mountNavigation()
		const dragged = wrapper.get(`section[aria-label="${from}"]`)
		const target = wrapper.get(`section[aria-label="${over}"]`)
		const geometry = vi.spyOn(target.element, 'getBoundingClientRect').mockReturnValue(new DOMRect(0, 0, 200, 40))
		const dataTransfer = { effectAllowed: 'none', dropEffect: 'none', setData: vi.fn() }
		await dragged.trigger('dragstart', { dataTransfer })
		expect(dataTransfer.effectAllowed).toBe('move')
		expect(dataTransfer.setData).toHaveBeenCalledWith('text/plain', from === 'Handbook' ? 'text-7' : 'calendar-1')
		await target.trigger('dragover', { clientY, dataTransfer })
		expect(dataTransfer.dropEffect).toBe('move')
		await dragged.trigger('dragend')
		await flushPromises()
		expect(saveOrder).toHaveBeenCalledExactlyOnceWith('team', [
			{ id: 'team-folder' },
			{ id: 'calendar-1', providerId: 'calendar', entryId: '1' },
			{ id: 'text-7', providerId: 'text', entryId: '7' },
			{ id: 'home' },
		])
		expect(wrapper.findAll('.team-navigation__entry').map((entry) => entry.attributes('aria-label')))
			.toEqual(['Team folder', 'Calendar', 'Handbook', 'Shared with the team'])
		expect(wrapper.find('.team-navigation__entry--dragging').exists()).toBe(false)
		geometry.mockRestore()
		wrapper.unmount()
	})

	it('renders the server navigation order, including interleaved built-in entries', () => {
		resourceState.navigation = [
			{ id: 'calendar-1', providerId: 'calendar', entryId: '1' },
			{ id: 'home' },
			{ id: 'text-7', providerId: 'text', entryId: '7' },
			{ id: 'team-folder' },
		]
		const wrapper = mountNavigation()
		expect(wrapper.findAll('.team-navigation__entry').map((entry) => entry.attributes('aria-label')))
			.toEqual(['Calendar', 'Shared with the team', 'Handbook', 'Team folder'])
		wrapper.unmount()
	})

	it('does not save an unchanged drag or allow a member to reorder', async () => {
		const wrapper = mountNavigation()
		const entry = wrapper.get('section[aria-label="Handbook"]')
		await entry.trigger('dragover')
		await wrapper.get('nav').trigger('dragover')
		await entry.trigger('dragstart')
		await entry.trigger('dragover')
		await entry.trigger('dragend')
		expect(saveOrder).not.toHaveBeenCalled()
		permissionState.role = 'member'
		await flushPromises()
		await entry.trigger('dragstart')
		await entry.trigger('dragend')
		expect(wrapper.find('.team-navigation__entry--dragging').exists()).toBe(false)
		expect(saveOrder).not.toHaveBeenCalled()
		wrapper.unmount()
	})

	it('ignores a drag target that appeared after the drag started', async () => {
		entryState.calendar = false
		const wrapper = mountNavigation()
		const dragged = wrapper.get('section[aria-label="Handbook"]')
		await dragged.trigger('dragstart')
		entryState.calendar = true
		await flushPromises()
		await wrapper.get('section[aria-label="Calendar"]').trigger('dragover')
		expect(wrapper.findAll('.team-navigation__entry').map((entry) => entry.attributes('aria-label')))
			.toEqual(['Team folder', 'Handbook', 'Shared with the team', 'Calendar'])
		wrapper.unmount()
	})

	it('keeps the existing order when hovering the same insertion position', async () => {
		const wrapper = mountNavigation()
		const dragged = wrapper.get('section[aria-label="Handbook"]')
		const target = wrapper.get('section[aria-label="Calendar"]')
		const geometry = vi.spyOn(target.element, 'getBoundingClientRect').mockReturnValue(new DOMRect(0, 0, 200, 40))
		await dragged.trigger('dragstart')
		await target.trigger('dragover', { clientY: 0 })
		await dragged.trigger('dragend')
		expect(saveOrder).not.toHaveBeenCalled()
		geometry.mockRestore()
		wrapper.unmount()
	})

	it('prevents native drops on the navigation and its entries', () => {
		const wrapper = mountNavigation()
		for (const target of [wrapper.get('nav'), wrapper.get('section[aria-label="Handbook"]')]) {
			const event = new Event('drop', { bubbles: true, cancelable: true })
			target.element.dispatchEvent(event)
			expect(event.defaultPrevented).toBe(true)
		}
		wrapper.unmount()
	})

	it('reports a failed navigation order save', async () => {
		saveOrder.mockRejectedValueOnce(new Error('offline'))
		const wrapper = mountNavigation()
		const entry = wrapper.get('section[aria-label="Handbook"]')
		await entry.trigger('dragstart')
		await wrapper.get('section[aria-label="Calendar"]').trigger('dragover', { clientY: 1 })
		await entry.trigger('dragend')
		await flushPromises()
		expect(showError).toHaveBeenCalledWith('Could not save the navigation order')
		expect(wrapper.find('.team-navigation__entry--dragging').exists()).toBe(false)
		wrapper.unmount()
	})

	it('retries a failed provider through its current context', async () => {
		errors.mockReturnValue(['text'])
		context.mockReturnValue({ refresh })
		const wrapper = mountNavigation()
		await wrapper.get('section[aria-label="Could not load text"]').trigger('click')
		await flushPromises()
		expect(context).toHaveBeenCalledWith('team', 'text')
		expect(refresh).toHaveBeenCalledOnce()
		wrapper.unmount()
	})

	it('uses the current error list if the provider recovers before retry is handled', async () => {
		errors.mockReturnValue(['text'])
		context.mockReturnValue({ refresh })
		const wrapper = mountNavigation()
		errors.mockReturnValue([])
		await wrapper.get('section[aria-label="Could not load text"]').trigger('click')
		await flushPromises()
		expect(context).toHaveBeenCalledWith('team', '')
		expect(refresh).toHaveBeenCalledOnce()
		wrapper.unmount()
	})

	it('reloads when a failed provider has no context', async () => {
		errors.mockReturnValue(['text'])
		const wrapper = mountNavigation()
		const reload = vi.fn()
		const click = new MouseEvent('click', { bubbles: true })
		vi.stubGlobal('window', { location: { reload } })
		try {
			wrapper.get('section[aria-label="Could not load text"]').element.dispatchEvent(click)
			expect(reload).toHaveBeenCalledOnce()
		} finally {
			vi.unstubAllGlobals()
			wrapper.unmount()
		}
	})

	it('opens and closes the team settings dialog', async () => {
		const wrapper = mountNavigation()
		await wrapper.get('section[aria-label="Team settings"]').trigger('click')
		expect(wrapper.find('[data-settings-dialog]').exists()).toBe(true)
		wrapper.getComponent({ name: 'TeamSettingsDialog' }).vm.$emit('update:open', false)
		await flushPromises()
		expect(wrapper.find('[data-settings-dialog]').exists()).toBe(false)
		wrapper.unmount()
	})

	it('keeps navigation available while team metadata has not loaded', () => {
		getCircle.mockReturnValue(null)
		const wrapper = mountNavigation()
		expect(wrapper.find('nav').exists()).toBe(true)
		expect(wrapper.find('.team-navigation__team-header').exists()).toBe(false)
		expect(wrapper.find('.team-navigation__footer').exists()).toBe(false)
		wrapper.unmount()
	})

	it('hides navigation and settings when the route has no team', async () => {
		const wrapper = mountNavigation()
		await wrapper.get('section[aria-label="Team settings"]').trigger('click')
		delete useRoute().params.teamId
		await flushPromises()
		expect(wrapper.find('nav').exists()).toBe(false)
		expect(wrapper.find('[data-settings-dialog]').exists()).toBe(false)
		wrapper.unmount()
	})

	it.each(['team-folder', 'team-members'])('renders navigation for the active %s section', (name) => {
		useRoute().name = name
		const wrapper = mountNavigation()
		expect(wrapper.find('section[aria-label="Team folder"]').exists()).toBe(true)
		expect(wrapper.find('section[aria-label="Members"]').exists()).toBe(true)
		wrapper.unmount()
	})

	it('shows every app entry without add or remove controls', () => {
		const wrapper = mountNavigation()
		expect(wrapper.find('section[aria-label="Handbook"]').exists()).toBe(true)
		expect(wrapper.find('section[aria-label="Calendar"]').exists()).toBe(true)
		expect(wrapper.text()).not.toContain('Add existing resource')
		expect(wrapper.text()).not.toContain('Remove resource from navigation')
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		wrapper.unmount()
	})

	it.each(['member', 'moderator'])('hides the entire resource action group from a %s', (role) => {
		permissionState.role = role
		const wrapper = mountNavigation()
		expect(wrapper.findAll('nav button')).toHaveLength(0)
		expect(wrapper.find('.actions-trigger').exists()).toBe(false)
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		wrapper.unmount()
	})

	it.each(['admin', 'owner'])('shows the action trigger to an %s even without resource entries', (role) => {
		permissionState.role = role
		appState.empty = true
		actions.mockReturnValue([])
		const wrapper = mountNavigation()
		expect(wrapper.find('.actions-trigger').exists()).toBe(true)
		expect(wrapper.findAll('nav button')).toHaveLength(0)
		wrapper.unmount()
	})

	it('runs a create action immediately without opening a naming dialog', async () => {
		const create = vi.fn()
		actions.mockReturnValue([{ id: 'create', label: 'New', run: create }])
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'New')!.trigger('click')
		await flushPromises()
		expect(run).toHaveBeenCalledOnce()
		expect(create).toHaveBeenCalledOnce()
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		wrapper.unmount()
	})
})
