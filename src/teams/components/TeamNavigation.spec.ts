/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { computed, reactive } from 'vue'
import { useRoute } from 'vue-router'
import TeamAppIcon from './TeamAppIcon.vue'
import TeamNavigation from './TeamNavigation.vue'

const { run, actions, context, errors, provider, getCircle, createFolder, refresh, push, saveOrder, removeFromNavigation, appState } = vi.hoisted(() => ({
	run: vi.fn(),
	actions: vi.fn(),
	context: vi.fn(),
	errors: vi.fn(),
	provider: vi.fn(),
	getCircle: vi.fn(),
	createFolder: vi.fn(),
	refresh: vi.fn(),
	push: vi.fn(),
	saveOrder: vi.fn(),
	removeFromNavigation: vi.fn(),
	appState: { busy: false, empty: false, noCandidates: false },
}))

const resourceState = reactive({ tabOrder: [] as string[], folderChecked: false, folder: null as { id: number } | null })
const permissionState = reactive({ role: 'admin' })
const entryState = reactive({ handbook: true, calendar: true })

vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))
vi.mock('vue-router', async () => {
	const { reactive } = await import('vue')
	const route = reactive({ params: { teamId: 'team' }, name: 'team-home' })
	return {
		useRoute: () => route,
		useRouter: () => ({ push }),
	}
})
vi.mock('vuex', () => ({ useStore: () => ({ getters: { getCircle } }) }))
vi.mock('../store.ts', () => ({ useTeamsStore: () => ({ getTeam: () => null }) }))
vi.mock('../resourcesStore.ts', () => ({ useTeamResourcesStore: () => ({ forTeam: () => resourceState, createFolder, saveOrder }) }))
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
vi.mock('../integrations/registry.ts', () => ({
	getTeamAppProvider: provider,
}))
vi.mock('../integrations/store.ts', () => ({
	useTeamAppsStore: () => ({
		run,
		refresh,
		removeFromNavigation,
		context,
		entries: () => appState.empty
			? []
			: [{
					id: 'text-7',
					label: 'Handbook',
					icon: '\u{1F4DA}',
					providerId: 'text',
					entryId: '7',
					referenceUrl: 'https://cloud.example/f/7',
				}, {
					id: 'calendar-1',
					label: 'Calendar',
					providerId: 'calendar',
					entryId: '1',
					href: '/apps/calendar/1',
					referenceUrl: 'http://localhost/apps/collectives/1',
					openMode: 'link',
				}].filter((entry) => entry.id === 'text-7' ? entryState.handbook : entryState.calendar),
		actions,
		existingResources: () => appState.empty || appState.noCandidates
			? []
			: [{
					id: 'text',
					name: 'Text',
					actions: [{ id: 'circles-add-text-7', label: 'Handbook', icon: 'M0 0h24v24z' }],
				}, {
					id: 'collectives',
					name: 'Collectives',
					actions: [{ id: 'circles-add-collectives-1', label: 'Journal', icon: 'M0 0h24v24z' }],
				}],
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
		provider.mockReset().mockImplementation((id: string) => ({ name: id === 'text' ? 'Text' : 'Calendar' }))
		getCircle.mockReset().mockReturnValue({ isMember: true })
		vi.mocked(showError).mockClear()
		useRoute().params.teamId = 'team'
		useRoute().name = 'team-home'
		removeFromNavigation.mockReset().mockResolvedValue(undefined)
		appState.busy = false
		appState.empty = false
		appState.noCandidates = false
		entryState.handbook = true
		entryState.calendar = true
		permissionState.role = 'admin'
		resourceState.tabOrder = []
		resourceState.folderChecked = false
		resourceState.folder = null
		createFolder.mockReset().mockResolvedValue(undefined)
		refresh.mockReset().mockResolvedValue(undefined)
		push.mockReset().mockResolvedValue(undefined)
		saveOrder.mockReset().mockResolvedValue(undefined)
	})

	function mountNavigation() {
		return mount(TeamNavigation, {
			global: {
				stubs: {
					NcAppNavigation: { template: '<nav><slot name="search" /><slot name="list" /><slot name="footer" /></nav>' },
					NcAppNavigationCaption: { template: '<div><span v-if="$slots.actionsTriggerIcon" class="actions-trigger"><slot name="actionsTriggerIcon" /></span><slot name="actions" /></div>' },
					NcDialog: { name: 'NcDialog', props: ['name', 'open'], emits: ['update:open'], template: '<div role="dialog" :aria-label="name"><slot /><slot name="actions" /></div>' },
					NcButton: { name: 'NcButton', props: ['disabled'], template: '<button :disabled="disabled"><slot name="icon" /><slot /></button>' },
					NcCheckboxRadioSwitch: {
						props: ['modelValue', 'value', 'disabled'],
						emits: ['update:modelValue'],
						template: '<label><input type="checkbox" :value="value" :checked="modelValue.includes(value)" :disabled="disabled" @change="$emit(\'update:modelValue\', $event.target.checked ? [...modelValue, value] : modelValue.filter(id => id !== value))"><slot /></label>',
					},
					NcTextField: {
						name: 'NcTextField',
						props: ['modelValue', 'label'],
						emits: ['update:modelValue', 'trailing-button-click'],
						template: '<div><slot name="icon" /><input :aria-label="label" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></div>',
					},
					NcListItem: { props: ['name'], template: '<li><button><slot name="icon" />{{ name }}</button><span><slot name="subname" /></span></li>' },
					NcEmptyContent: { props: ['name'], template: '<div><p>{{ name }}</p><slot name="icon" /></div>' },
					NcAppNavigationItem: {
						props: ['name', 'href', 'to', 'target', 'rel'],
						template: '<section :aria-label="name" :data-href="href" :data-to="to ? JSON.stringify(to) : undefined" :data-target="target" :data-rel="rel"><slot name="icon" /><slot name="counter" /><slot name="actions" /></section>',
					},
					NcActionButton: { props: { closeAfterClick: Boolean }, template: '<button :data-close-after-click="closeAfterClick"><slot name="icon" /><slot /></button>' },
					NcActionSeparator: { template: '<hr>' },
					NcIconSvgWrapper: true,
				},
			},
		})
	}

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
		expect(saveOrder).toHaveBeenCalledExactlyOnceWith('team', ['team-folder', 'calendar-1', 'text-7', 'home'])
		expect(wrapper.find('.team-navigation__entry--dragging').exists()).toBe(false)
		geometry.mockRestore()
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

	it('uses the provider ID when its display name is unavailable', async () => {
		provider.mockReturnValue(undefined)
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!.trigger('click')
		expect(wrapper.findAll('.team-navigation__removal-app').map((label) => label.text())).toEqual(['text', 'calendar'])
		wrapper.unmount()
	})

	it('separates creation from removal even when there are no resources to add', () => {
		appState.noCandidates = true
		const wrapper = mountNavigation()
		expect(wrapper.find('.team-action-separator').exists()).toBe(true)
		expect(wrapper.findAll('button').map((button) => button.text())).toEqual(['🆕 New', 'Remove resource from navigation'])
		wrapper.unmount()
	})

	it('searches existing resources by name or app before adding them', async () => {
		const wrapper = mountNavigation()
		const entry = wrapper.get('section[aria-label="Handbook"]')
		const directLink = wrapper.get('section[aria-label="Calendar"]')
		expect(directLink.attributes('data-href')).toBe('/apps/calendar/1')
		expect(directLink.attributes('data-to')).toBeUndefined()
		expect(directLink.attributes('data-target')).toBe('_blank')
		expect(directLink.attributes('data-rel')).toBe('noopener noreferrer')
		expect(entry.attributes('data-target')).toBeUndefined()
		const entryIcons = wrapper.findAllComponents(TeamAppIcon).filter((icon) => entry.element.contains(icon.element))
		expect(entryIcons.map((icon) => icon.props('icon')))
			.toEqual(['\u{1F4DA}'])
		expect(entry.get('.team-app-icon__emoji').text()).toBe('\u{1F4DA}')
		expect(wrapper.findAllComponents(TeamAppIcon).filter((icon) => icon.props('icon') === '\u{1F195}'))
			.toHaveLength(1)
		expect(wrapper.findAll('.team-action-separator')).toHaveLength(1)
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		expect(wrapper.findAll('button').map((button) => button.text()))
			.toEqual(['🆕 New', 'Add existing resource', 'Remove resource from navigation'])
		expect(wrapper.findAll('button').some((button) => button.text() === 'Add to team')).toBe(false)
		const addExisting = wrapper.findAll('button').find((button) => button.text() === 'Add existing resource')!
		expect(addExisting.attributes('data-close-after-click')).toBe('true')
		await addExisting.trigger('click')
		const dialog = wrapper.get('[role="dialog"]')
		expect(dialog.attributes('aria-label')).toBe('Add existing resource')
		expect(dialog.get('p').text()).toBe('Choose a resource to add to this team\'s navigation.')
		expect(dialog.findAll('li span').map((subname) => subname.text())).toEqual(['Text', 'Collectives'])
		expect(dialog.findAll('button').map((button) => button.text())).toEqual(['Handbook', 'Journal', 'Cancel'])
		const search = dialog.get('input[aria-label="Search resources"]')
		await search.setValue('  HANDBOOK  ')
		expect(dialog.findAll('li button').map((button) => button.text())).toEqual(['Handbook'])
		await search.setValue('collectives')
		expect(dialog.findAll('li button').map((button) => button.text())).toEqual(['Journal'])
		await search.setValue('missing resource')
		expect(dialog.findAll('li')).toHaveLength(0)
		expect(dialog.text()).toContain('No resources found')
		wrapper.findComponent({ name: 'NcTextField' }).vm.$emit('trailing-button-click')
		await wrapper.vm.$nextTick()
		expect(dialog.findAll('li')).toHaveLength(2)
		await search.setValue('journal')
		await dialog.findAll('button').at(-1)!.trigger('click')
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		expect(run).not.toHaveBeenCalled()
		await addExisting.trigger('click')
		expect(wrapper.get('[role="dialog"] input').element).toHaveProperty('value', '')
		expect(wrapper.findAll('[role="dialog"] li')).toHaveLength(2)
		appState.busy = true
		await wrapper.get('[role="dialog"] li button').trigger('click')
		expect(run).not.toHaveBeenCalled()
		expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
		appState.busy = false
		await wrapper.get('[role="dialog"] li button').trigger('click')
		expect(run).toHaveBeenCalledWith(expect.objectContaining({ id: 'circles-add-text-7' }))
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		wrapper.unmount()
	})

	it('keeps checkbox selections across searches and only removes selected entries on confirmation', async () => {
		const wrapper = mountNavigation()
		expect(wrapper.get('section[aria-label="Handbook"]').find('button').exists()).toBe(false)
		const remove = wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!
		await remove.trigger('click')
		const dialog = wrapper.get('[role="dialog"]')
		expect(dialog.attributes('aria-label')).toBe('Remove resource from navigation')
		expect(dialog.get('p').text()).toContain('The resources themselves will not be deleted.')
		expect(dialog.findAll('li label').map((label) => label.text())).toEqual(['HandbookText', 'CalendarCalendar'])
		const save = dialog.findAll('button').find((button) => button.text() === 'Remove')!
		expect(save.attributes('disabled')).toBeDefined()
		await dialog.get('input[value="text-7"]').setValue(true)
		await dialog.get('input[value="text-7"]').setValue(false)
		expect(save.attributes('disabled')).toBeDefined()
		await dialog.get('input[value="text-7"]').setValue(true)
		expect(run).not.toHaveBeenCalled()
		expect(save.attributes('disabled')).toBeUndefined()
		const search = dialog.get('input[aria-label="Search resources"]')
		await search.setValue('missing')
		expect(dialog.text()).toContain('No resources found')
		await search.setValue(' TEXT ')
		expect(dialog.findAll('li')).toHaveLength(1)
		expect(dialog.get('input[type="checkbox"]').element).toHaveProperty('checked', true)
		appState.busy = true
		await save.trigger('click')
		expect(run).not.toHaveBeenCalled()
		expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
		appState.busy = false
		await save.trigger('click')
		await flushPromises()
		expect(removeFromNavigation).toHaveBeenCalledExactlyOnceWith('team', 'text', '7')
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		wrapper.unmount()
	})

	it('discards selection on Cancel and saves multiple removals sequentially', async () => {
		const wrapper = mountNavigation()
		const remove = wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!
		await remove.trigger('click')
		await wrapper.get('input[value="text-7"]').setValue(true)
		await wrapper.findAll('[role="dialog"] button').find((button) => button.text() === 'Cancel')!.trigger('click')
		expect(run).not.toHaveBeenCalled()
		await remove.trigger('click')
		expect(wrapper.get('input[value="text-7"]').element).toHaveProperty('checked', false)
		await wrapper.get('input[value="text-7"]').setValue(true)
		await wrapper.get('input[value="calendar-1"]').setValue(true)
		let finishFirst!: () => void
		removeFromNavigation.mockImplementationOnce(() => new Promise<void>((resolve) => {
			finishFirst = resolve
		}))
		await wrapper.findAll('[role="dialog"] button').find((button) => button.text() === 'Remove')!.trigger('click')
		expect(removeFromNavigation).toHaveBeenCalledExactlyOnceWith('team', 'text', '7')
		expect(wrapper.get('input[value="calendar-1"]').attributes('disabled')).toBeDefined()
		finishFirst()
		await flushPromises()
		expect(removeFromNavigation).toHaveBeenCalledTimes(2)
		expect(removeFromNavigation).toHaveBeenNthCalledWith(2, 'team', 'calendar', '1')
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		wrapper.unmount()
	})

	it.each(['team change', 'permission loss'])('stops a removal batch after %s', async (reason) => {
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!.trigger('click')
		await wrapper.get('input[value="text-7"]').setValue(true)
		await wrapper.get('input[value="calendar-1"]').setValue(true)
		let finish!: () => void
		removeFromNavigation.mockImplementationOnce(() => new Promise<void>((resolve) => {
			finish = resolve
		}))
		await wrapper.findAll('[role="dialog"] button').find((button) => button.text() === 'Remove')!.trigger('click')
		if (reason === 'team change') {
			useRoute().params.teamId = 'other'
		} else {
			permissionState.role = 'member'
		}
		finish()
		await flushPromises()
		expect(removeFromNavigation).toHaveBeenCalledExactlyOnceWith('team', 'text', '7')
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		wrapper.unmount()
	})

	it('does not close a new team dialog when an old team removal finishes', async () => {
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!.trigger('click')
		await wrapper.get('input[value="text-7"]').setValue(true)
		let finish!: () => void
		removeFromNavigation.mockImplementationOnce(() => new Promise<void>((resolve) => {
			finish = resolve
		}))
		await wrapper.findAll('[role="dialog"] button').find((button) => button.text() === 'Remove')!.trigger('click')
		useRoute().params.teamId = 'other'
		await flushPromises()
		await wrapper.findAll('button').find((button) => button.text() === 'Add existing resource')!.trigger('click')
		finish()
		await flushPromises()
		expect(wrapper.get('[role="dialog"]').attributes('aria-label')).toBe('Add existing resource')
		expect(removeFromNavigation).toHaveBeenCalledExactlyOnceWith('team', 'text', '7')
		wrapper.unmount()
	})

	it('ignores a removal confirmation without selections', async () => {
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!.trigger('click')
		wrapper.findAllComponents({ name: 'NcButton' }).find((button) => button.text() === 'Remove')!.vm.$emit('click')
		await flushPromises()
		expect(run).not.toHaveBeenCalled()
		expect(removeFromNavigation).not.toHaveBeenCalled()
		wrapper.unmount()
	})

	it('clears resource dialog state when dismissed', async () => {
		const wrapper = mountNavigation()
		const remove = wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!
		await remove.trigger('click')
		await wrapper.get('input[value="text-7"]').setValue(true)
		await wrapper.get('input[aria-label="Search resources"]').setValue('Text')
		wrapper.getComponent({ name: 'NcDialog' }).vm.$emit('update:open', false)
		await flushPromises()
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		await wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!.trigger('click')
		expect(wrapper.get('input[value="text-7"]').element).toHaveProperty('checked', false)
		expect(wrapper.get('input[aria-label="Search resources"]').element).toHaveProperty('value', '')
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

	it.each(['admin', 'owner'])('allows an %s to remove any app navigation entry without entry flags', async (role) => {
		permissionState.role = role
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!.trigger('click')
		const dialog = wrapper.get('[role="dialog"]')
		expect(dialog.findAll('input[type="checkbox"]').map((input) => input.attributes('value'))).toEqual(['text-7', 'calendar-1'])
		expect(dialog.text()).not.toContain('Team folder')
		expect(dialog.text()).not.toContain('Shared with the team')
		wrapper.unmount()
	})

	it('closes the remove dialog and blocks saving when admin permission is revoked', async () => {
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!.trigger('click')
		await wrapper.get('input[value="text-7"]').setValue(true)
		const confirm = wrapper.findAll('[role="dialog"] button').find((button) => button.text() === 'Remove')!
		permissionState.role = 'member'
		await confirm.trigger('click')
		await flushPromises()
		expect(removeFromNavigation).not.toHaveBeenCalled()
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		wrapper.unmount()
	})

	it('keeps the selection open and stops the batch when a removal fails', async () => {
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'Remove resource from navigation')!.trigger('click')
		await wrapper.get('input[value="text-7"]').setValue(true)
		await wrapper.get('input[value="calendar-1"]').setValue(true)
		removeFromNavigation.mockRejectedValueOnce(new Error('Could not remove resource'))
		run.mockImplementationOnce(async (action) => action.run().catch(() => undefined))
		await wrapper.findAll('[role="dialog"] button').find((button) => button.text() === 'Remove')!.trigger('click')
		await flushPromises()
		expect(removeFromNavigation).toHaveBeenCalledExactlyOnceWith('team', 'text', '7')
		expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
		expect(wrapper.get('input[value="text-7"]').element).toHaveProperty('checked', true)
		expect(wrapper.findAll('[role="dialog"] button').find((button) => button.text() === 'Remove')!.attributes('disabled')).toBeUndefined()
		wrapper.unmount()
	})

	async function openNamePrompt(controller = new AbortController()) {
		const result = vi.fn()
		actions.mockImplementation((_teamId: string, prompt: (title: string, signal: AbortSignal) => Promise<string | null>) => [{
			id: 'create',
			label: 'New',
			run: async () => result(await prompt('Name', controller.signal)),
		}])
		const wrapper = mountNavigation()
		await wrapper.findAll('button').find((button) => button.text() === 'New')!.trigger('click')
		return { wrapper, result }
	}

	it.each(['button', 'enter'])('submits the navigation-owned prompt using %s and trims the name', async (method) => {
		const { wrapper, result } = await openNamePrompt()
		const dialog = wrapper.get('[role="dialog"][aria-label="Name"]')
		const save = dialog.findAll('button').find((button) => button.text() === 'Save')!
		expect(save.attributes('disabled')).toBeDefined()
		const input = dialog.get('input')
		await input.setValue('   ')
		await input.trigger('keyup', { key: 'Enter' })
		expect(result).not.toHaveBeenCalled()
		await input.setValue('  Sketch  ')
		if (method === 'enter') {
			await input.trigger('keyup', { key: 'Enter' })
		} else {
			await save.trigger('click')
		}
		await flushPromises()
		expect(result).toHaveBeenCalledExactlyOnceWith('Sketch')
		expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
		wrapper.unmount()
	})

	it.each(['cancel', 'dismiss', 'abort', 'team change', 'unmount'])('cancels the name prompt on %s', async (method) => {
		const controller = new AbortController()
		const { wrapper, result } = await openNamePrompt(controller)
		if (method === 'cancel') {
			await wrapper.findAll('[role="dialog"] button').find((button) => button.text() === 'Cancel')!.trigger('click')
		} else if (method === 'dismiss') {
			wrapper.getComponent({ name: 'NcDialog' }).vm.$emit('update:open', false)
		} else if (method === 'abort') {
			controller.abort()
		} else if (method === 'team change') {
			useRoute().params.teamId = 'other'
		} else {
			wrapper.unmount()
		}
		await flushPromises()
		expect(result).toHaveBeenCalledExactlyOnceWith(null)
		if (method !== 'unmount') {
			expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
			wrapper.unmount()
		}
	})

	it('rejects concurrent and already-aborted prompts and ignores late prompts after unmount', async () => {
		const { wrapper, result } = await openNamePrompt()
		const prompt = actions.mock.calls[0]![1] as (title: string, signal: AbortSignal) => Promise<string | null>
		expect(() => prompt('Another name', new AbortController().signal)).toThrow('Another Teams app dialog is already open')
		const controller = new AbortController()
		controller.abort()
		expect(() => prompt('Aborted name', controller.signal)).toThrow()
		wrapper.unmount()
		await flushPromises()
		expect(result).toHaveBeenCalledExactlyOnceWith(null)
		await expect(prompt('Late name', new AbortController().signal)).resolves.toBeNull()
	})
})
