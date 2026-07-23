/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteRecordRaw } from 'vue-router'

import { generateUrl } from '@nextcloud/router'
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from './views/HomeView.vue'
import TeamDashboardView from './views/TeamDashboardView.vue'
import TeamPage from './views/TeamPage.vue'
import TeamSettingsView from './views/TeamSettingsView.vue'

const routes: RouteRecordRaw[] = [
	{
		name: 'home',
		path: '/',
		component: HomeView,
	},
	{
		path: '/team/:teamId',
		component: TeamPage,
		props: true,
		children: [
			{
				name: 'team',
				path: '',
				component: TeamDashboardView,
				props: true,
			},
			{
				name: 'team-settings',
				path: 'settings',
				component: TeamSettingsView,
				props: true,
			},
		],
	},
]

export const router = createRouter({
	// HTML5 history mode for clean, hash-free URLs. The server registers a
	// catch-all route (Page#indexPath) so deep-link reloads still serve the shell.
	history: createWebHistory(generateUrl('/apps/circles/teams')),
	routes,
})
