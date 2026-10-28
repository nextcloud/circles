/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteRecordRaw } from 'vue-router'

import { generateUrl } from '@nextcloud/router'
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from './views/HomeView.vue'
import JoinInvitation from './views/JoinInvitation.vue'
import TeamAppView from './views/TeamAppView.vue'
import TeamFolderView from './views/TeamFolderView.vue'
import TeamHomeView from './views/TeamHomeView.vue'
import TeamLandingView from './views/TeamLandingView.vue'
import TeamMembersView from './views/TeamMembersView.vue'
import TeamPage from './views/TeamPage.vue'
import { getTeamAppProviders } from './integrations/registry.ts'

const routes: RouteRecordRaw[] = [
	{
		name: 'home',
		path: '/',
		component: HomeView,
	},
	{
		name: 'join-invitation',
		path: '/join/:invitationCode',
		component: JoinInvitation,
	},
	{
		// Named for provider route registration; navigation targets child routes.
		path: '/team/:teamId',
		name: 'team-scope',
		component: TeamPage,
		props: true,
		children: [
			{
				// The team's own URL: forwards to the first navigation entry
				// once the tab order is known. The entries keep their own
				// paths — links to an empty-path child count as links to the
				// parent, which vue-router marks active on every child route.
				name: 'team',
				path: '',
				component: TeamLandingView,
				props: true,
			},
			{
				name: 'team-folder',
				path: 'folder',
				component: TeamFolderView,
				props: true,
			},
			{
				name: 'team-home',
				path: 'home',
				component: TeamHomeView,
				props: true,
			},
			{
				name: 'team-app',
				path: 'app/:providerId/:entryId',
				component: TeamAppView,
				props: true,
			},
			{
				name: 'team-members',
				path: 'members',
				component: TeamMembersView,
				props: true,
			},
		],
	},
	{
		// An unmatched route renders an empty router view and no sidebar, so
		// send every unknown URL to the teams overview instead.
		path: '/:pathMatch(.*)*',
		redirect: { name: 'home' },
	},
]

export const router = createRouter({
	// HTML5 history mode for clean, hash-free URLs. The server registers a
	// catch-all route (Page#indexPath) so deep-link reloads still serve the shell.
	history: createWebHistory(generateUrl('/apps/circles/teams')),
	routes,
})

/**
 *
 */
export function installTeamAppRoutes(): void {
	for (const provider of getTeamAppProviders()) {
		for (const alias of provider.routeAliases ?? []) {
			router.addRoute('team-scope', {
				path: alias.path,
				redirect: (route) => ({
					name: 'team-app',
					params: {
						teamId: String(route.params.teamId),
						providerId: provider.id,
						entryId: String(route.params[alias.entryParam]),
					},
				}),
			})
		}
	}
}
