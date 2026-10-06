/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext, Locator, Page } from '@playwright/test'

import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { expect, request } from '@playwright/test'
import { randomUUID } from 'node:crypto'
import { userTest as test } from '../support/fixtures.ts'
import { waitForApiResponse } from '../support/helpers.ts'

const CIRCLES_API = '/ocs/v2.php/apps/circles/circles'
const CONFIG_VISIBLE = 8
const CONFIG_OPEN = 16
const CONFIG_REQUEST = 64
const OPEN_CONFIG = CONFIG_VISIBLE | CONFIG_OPEN
const REQUEST_CONFIG = OPEN_CONFIG | CONFIG_REQUEST
const HOME_PATH = 'apps/circles/teams'

interface OcsResponse<T> {
	ocs: {
		data: T
	}
}

interface TeamFixture {
	id: string
	name: string
}

interface CircleMemberFixture {
	userId?: string | null
	level?: number
	status?: string
}

interface DiscoveryFixtures {
	open: TeamFixture
	request: TeamFixture
	closed: TeamFixture
	hidden: TeamFixture
}

type CreateTeam = (label: string, config?: number) => Promise<TeamFixture>

test.describe('Discoverable teams', () => {
	test.describe.configure({ mode: 'serial' })

	test('lists visible teams without probing protected non-member endpoints', async ({ page, baseURL }) => {
		await withOwnerTeams(baseURL, async (_ownerApi, createTeam) => {
			const teams = await createDiscoveryFixtures(createTeam)
			const fixtureIds = new Set(Object.values(teams).map((team) => team.id))
			const protectedRequests: string[] = []
			const forbiddenResponses: string[] = []

			page.on('request', (apiRequest) => {
				if (isProtectedCircleRequest(apiRequest.url(), fixtureIds)) {
					protectedRequests.push(`${apiRequest.method()} ${apiRequest.url()}`)
				}
			})
			page.on('response', (response) => {
				if (response.status() === 403) {
					forbiddenResponses.push(`${response.status()} ${response.url()}`)
				}
			})

			const circlesResponsePromise = page.waitForResponse((response) => response.url().includes('/ocs/v2.php/apps/circles/circles?limit=-1'))
			await visitTeamsHome(page)
			const circlesResponse = await circlesResponsePromise
			if (!circlesResponse.ok()) {
				throw new Error(`Circle listing failed at ${circlesResponse.url()} with HTTP ${circlesResponse.status()}: ${await circlesResponse.text()}`)
			}
			const listedCircleIds = (await circlesResponse.json() as OcsResponse<Array<{ id: string }>>).ocs.data.map(({ id }) => id)
			expect(listedCircleIds).toEqual(expect.arrayContaining([teams.open.id, teams.request.id, teams.closed.id]))
			await expectInitialDiscovery(page, teams)

			for (const team of [teams.open, teams.request, teams.closed]) {
				await expect(getDiscoverableCard(page, team).getByRole('link')).toHaveCount(0)
			}

			expect(protectedRequests).toEqual([])
			expect(forbiddenResponses).toEqual([])
		})
	})

	test('joins an open team and persists a pending request', async ({ page, baseURL }) => {
		await withOwnerTeams(baseURL, async (ownerApi, createTeam) => {
			const openTeam = await createTeam('Open', OPEN_CONFIG)
			const requestTeam = await createTeam('Request', REQUEST_CONFIG)

			await visitTeamsHome(page)
			const homeURL = page.url()

			const openJoin = waitForApiResponse(page, 'PUT')
			await getDiscoverableCard(page, openTeam).getByRole('button', { name: 'Join', exact: true }).click()
			const openJoinResponse = await openJoin
			expect(openJoinResponse.url()).toContain(`/circles/${openTeam.id}/join`)
			expect(openJoinResponse.ok()).toBeTruthy()

			await expect(page.getByText(`You have joined the team ${openTeam.name}.`, { exact: true })).toBeVisible()
			await expectJoinedTeamPage(page, openTeam)

			const openMembers = await readMembers(ownerApi, openTeam)
			const joinedViewer = openMembers.find((member) => member.level === 1 && member.status === 'Member')
			expect(joinedViewer?.userId).toBeTruthy()
			const viewerId = joinedViewer?.userId
			if (!viewerId) {
				throw new Error('The joined member response did not include a user ID')
			}

			await visitTeamsHome(page)
			await expect(getTeamLink(page, openTeam)).toBeVisible()
			await expect(getDiscoverableCard(page, openTeam)).toHaveCount(0)

			const requestJoin = waitForApiResponse(page, 'PUT')
			await getDiscoverableCard(page, requestTeam).getByRole('button', { name: 'Join', exact: true }).click()
			const requestJoinResponse = await requestJoin
			expect(requestJoinResponse.url()).toContain(`/circles/${requestTeam.id}/join`)
			expect(requestJoinResponse.ok()).toBeTruthy()

			await expect(page.getByText('Your request to join this team is pending approval', { exact: true })).toBeVisible()
			await expect(page).toHaveURL(homeURL)
			const pendingCard = getDiscoverableCard(page, requestTeam)
			await expect(pendingCard.getByText('Request pending', { exact: true })).toBeVisible()
			await expect(pendingCard.getByRole('button')).toHaveCount(0)
			await expect(getTeamLink(page, requestTeam)).toHaveCount(0)

			await page.reload({ waitUntil: 'networkidle' })
			await expect(page).toHaveURL(homeURL)
			await expect(getDiscoverableCard(page, requestTeam).getByText('Request pending', { exact: true })).toBeVisible()
			await expect(getDiscoverableCard(page, requestTeam).getByRole('button')).toHaveCount(0)

			const requestMembers = await readMembers(ownerApi, requestTeam)
			expect(requestMembers).toContainEqual(expect.objectContaining({
				userId: viewerId,
				level: 0,
				status: 'Requesting',
			}))
		})
	})

	test('reloads a closed team after join failure and filters both team lists', async ({ page, baseURL }) => {
		await withOwnerTeams(baseURL, async (ownerApi, createTeam) => {
			const openTeam = await createTeam('Open', OPEN_CONFIG)
			const requestTeam = await createTeam('Request', REQUEST_CONFIG)
			const closedTeam = await createTeam('Closed', CONFIG_VISIBLE)
			const closingTeam = await createTeam('Closing', OPEN_CONFIG)

			await visitTeamsHome(page)
			const homeURL = page.url()

			const openJoin = waitForApiResponse(page, 'PUT')
			await getDiscoverableCard(page, openTeam).getByRole('button', { name: 'Join', exact: true }).click()
			const openJoinResponse = await openJoin
			expect(openJoinResponse.ok()).toBeTruthy()
			await expectJoinedTeamPage(page, openTeam)

			await visitTeamsHome(page)
			await setTeamConfig(ownerApi, closingTeam.id, CONFIG_VISIBLE)

			const failedJoin = waitForApiResponse(page, 'PUT')
			await getDiscoverableCard(page, closingTeam).getByRole('button', { name: 'Join', exact: true }).click()
			const failedJoinResponse = await failedJoin
			expect(failedJoinResponse.url()).toContain(`/circles/${closingTeam.id}/join`)
			await expect(page.getByText('Unable to join the team', { exact: true })).toBeVisible()
			await expect(page).toHaveURL(homeURL)
			await expect(getDiscoverableCard(page, closingTeam).getByRole('button')).toHaveCount(0)

			const search = page.getByRole('textbox', { name: 'Search teams', exact: true })
			await search.fill(requestTeam.name)
			await expect(getTeamLink(page, openTeam)).toHaveCount(0)
			await expect(getDiscoverableCard(page, requestTeam)).toBeVisible()
			await expect(getDiscoverableCard(page, closedTeam)).toHaveCount(0)
			await expect(getDiscoverableCard(page, closingTeam)).toHaveCount(0)

			await search.fill('')
			await expect(getTeamLink(page, openTeam)).toBeVisible()
			for (const team of [requestTeam, closedTeam, closingTeam]) {
				await expect(getDiscoverableCard(page, team)).toBeVisible()
			}
		})
	})

	test('returns a team to discovery after the viewer leaves', async ({ page, baseURL }) => {
		await withOwnerTeams(baseURL, async (_ownerApi, createTeam) => {
			const openTeam = await createTeam('Open', OPEN_CONFIG)
			await visitTeamsHome(page)
			const homeURL = page.url()

			const openJoin = waitForApiResponse(page, 'PUT')
			await getDiscoverableCard(page, openTeam).getByRole('button', { name: 'Join', exact: true }).click()
			const openJoinResponse = await openJoin
			expect(openJoinResponse.ok()).toBeTruthy()
			await expectJoinedTeamPage(page, openTeam)

			await page.getByRole('button', { name: 'Switch team', exact: true }).click()
			await page.getByRole('button', { name: 'Team actions', exact: true }).click()
			await page.getByRole('menuitem', { name: 'Leave team', exact: true }).click()

			const leaveDialog = page.getByRole('dialog', { name: 'Leave team', exact: true })
			await expect(leaveDialog).toBeVisible()
			const leave = waitForApiResponse(page, 'PUT')
			await leaveDialog.getByRole('button', { name: 'Leave team', exact: true }).click()
			const leaveResponse = await leave
			expect(leaveResponse.url()).toContain(`/circles/${openTeam.id}/leave`)
			expect(leaveResponse.ok()).toBeTruthy()

			await expect(page.getByText(`You left "${openTeam.name}"`, { exact: true })).toBeVisible()
			await expect(page).toHaveURL(homeURL)
			const openCard = getDiscoverableCard(page, openTeam)
			await expect(openCard).toBeVisible()
			await expect(openCard.getByRole('button', { name: 'Join', exact: true })).toBeVisible()
			await expect(getTeamLink(page, openTeam)).toHaveCount(0)
		})
	})

	test('discovers and joins an open team at mobile width', async ({ page, baseURL }) => {
		await withOwnerTeams(baseURL, async (_ownerApi, createTeam) => {
			const teams = await createDiscoveryFixtures(createTeam)
			await page.setViewportSize({ width: 375, height: 800 })
			await visitTeamsHome(page)
			await expectInitialDiscovery(page, teams)

			for (const team of [teams.open, teams.request, teams.closed]) {
				await expectFitsViewport(getDiscoverableCard(page, team), 375)
			}
			await expectFitsViewport(getDiscoverableCard(page, teams.open).getByRole('button', { name: 'Join', exact: true }), 375)
			await expectFitsViewport(getDiscoverableCard(page, teams.request).getByRole('button', { name: 'Join', exact: true }), 375)

			const openJoin = waitForApiResponse(page, 'PUT')
			await getDiscoverableCard(page, teams.open).getByRole('button', { name: 'Join', exact: true }).click()
			const openJoinResponse = await openJoin
			expect(openJoinResponse.url()).toContain(`/circles/${teams.open.id}/join`)
			expect(openJoinResponse.ok()).toBeTruthy()
			await expect(page.getByText(`You have joined the team ${teams.open.name}.`, { exact: true })).toBeVisible()
			await expectJoinedTeamPage(page, teams.open)
		})
	})
})

async function withOwnerTeams(
	baseURL: string | undefined,
	run: (ownerApi: APIRequestContext, createTeam: CreateTeam) => Promise<void>,
): Promise<void> {
	if (!baseURL) {
		throw new Error('Playwright baseURL is required')
	}

	const owner: User = await createRandomUser()
	const ownerApi = await request.newContext({
		baseURL,
		extraHTTPHeaders: {
			'OCS-APIRequest': 'true',
			Accept: 'application/json',
		},
	})
	await login(ownerApi, owner)
	const createdTeams: TeamFixture[] = []
	const createTeam: CreateTeam = async (label, config) => {
		const name = `${label} ${randomUUID()}`
		const response = await ownerApi.post(CIRCLES_API, {
			data: { name, createTeamFolder: false },
		})
		if (!response.ok()) {
			throw new Error(`Team creation failed at ${response.url()} with HTTP ${response.status()}: ${await response.text()}`)
		}
		const result = await response.json() as OcsResponse<{ id: string }>
		const team = { id: result.ocs.data.id, name }
		expect(team.id).toBeTruthy()
		createdTeams.push(team)

		if (config !== undefined) {
			await setTeamConfig(ownerApi, team.id, config)
		}

		return team
	}

	try {
		await run(ownerApi, createTeam)
	} finally {
		try {
			for (const team of createdTeams.reverse()) {
				const response = await ownerApi.delete(`${CIRCLES_API}/${encodeURIComponent(team.id)}`)
				expect(response.ok()).toBeTruthy()
			}
		} finally {
			await ownerApi.dispose()
		}
	}
}

async function createDiscoveryFixtures(createTeam: CreateTeam): Promise<DiscoveryFixtures> {
	return {
		open: await createTeam('Open', OPEN_CONFIG),
		request: await createTeam('Request', REQUEST_CONFIG),
		closed: await createTeam('Closed', CONFIG_VISIBLE),
		hidden: await createTeam('Hidden'),
	}
}

async function setTeamConfig(ownerApi: APIRequestContext, teamId: string, value: number): Promise<void> {
	const response = await ownerApi.put(`${CIRCLES_API}/${encodeURIComponent(teamId)}/config`, {
		data: { value },
	})
	expect(response.ok()).toBeTruthy()
}

async function readMembers(ownerApi: APIRequestContext, team: TeamFixture): Promise<CircleMemberFixture[]> {
	const response = await ownerApi.get(`${CIRCLES_API}/${encodeURIComponent(team.id)}/members`)
	expect(response.ok()).toBeTruthy()
	const result = await response.json() as OcsResponse<CircleMemberFixture[]>
	return result.ocs.data
}

async function visitTeamsHome(page: Page): Promise<void> {
	await page.goto(HOME_PATH, { waitUntil: 'networkidle' })
	await expect(page.getByRole('heading', { name: 'My teams', exact: true })).toBeVisible()
}

async function expectInitialDiscovery(page: Page, teams: DiscoveryFixtures): Promise<void> {
	await expect(page.getByText('No teams yet', { exact: true })).toBeVisible()
	await expect(page.getByRole('heading', { name: 'Other teams', exact: true })).toBeVisible()

	for (const team of [teams.open, teams.request, teams.closed]) {
		await expect(getDiscoverableCard(page, team)).toBeVisible()
	}
	await expect(getDiscoverableCard(page, teams.hidden)).toHaveCount(0)
	await expect(getDiscoverableCard(page, teams.open).getByRole('button', { name: 'Join', exact: true })).toBeVisible()
	await expect(getDiscoverableCard(page, teams.request).getByRole('button', { name: 'Join', exact: true })).toBeVisible()
	await expect(getDiscoverableCard(page, teams.closed).getByRole('button')).toHaveCount(0)
}

function getDiscoverableCard(page: Page, team: TeamFixture) {
	return page.getByRole('region', { name: 'Other teams', exact: true })
		.getByRole('article', { name: team.name, exact: true })
}

function getTeamLink(page: Page, team: TeamFixture) {
	return page.getByRole('link', { name: new RegExp(escapeRegExp(team.name)) })
}

async function expectJoinedTeamPage(page: Page, team: TeamFixture): Promise<void> {
	// The team landing route forwards to its first section, e.g. /home or /folder.
	const teamRoute = new RegExp(`/apps/circles/teams/team/${escapeRegExp(team.id)}(?:/.*)?$`)
	await expect(page).toHaveURL(teamRoute)
	const openNavigationButton = page.getByRole('button', { name: 'Open navigation', exact: true })
	if (await openNavigationButton.isVisible()) {
		await openNavigationButton.click()
	}
	await expect(page.getByRole('button', { name: 'Switch team', exact: true })).toContainText(team.name)
	await expect(page.getByText('Team not found', { exact: true })).toHaveCount(0)
}

async function expectFitsViewport(locator: Locator, viewportWidth: number): Promise<void> {
	const box = await locator.boundingBox()
	expect(box).not.toBeNull()
	if (box === null) {
		return
	}

	expect(box.x).toBeGreaterThanOrEqual(0)
	expect(box.x + box.width).toBeLessThanOrEqual(viewportWidth)
}

function isProtectedCircleRequest(url: string, circleIds: Set<string>): boolean {
	const match = new URL(url).pathname.match(/\/apps\/circles\/circles\/([^/]+)(?:\/avatar)?\/?$/)
	return match !== null && circleIds.has(decodeURIComponent(match[1]))
}

function escapeRegExp(value: string): string {
	return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}
