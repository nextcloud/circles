/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteRecordRaw } from 'vue-router'

import { generateUrl } from '@nextcloud/router'
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from './views/HomeView.vue'
import TeamPage from './views/TeamPage.vue'

const routes: RouteRecordRaw[] = [
	{
		name: 'home',
		path: '/',
		component: HomeView,
	},
	{
		name: 'team',
		path: '/team/:teamId',
		component: TeamPage,
		props: true,
	},
]

export const router = createRouter({
	// HTML5 history mode for clean, hash-free URLs. The server registers a
	// catch-all route (Page#indexPath) so deep-link reloads still serve the shell.
	history: createWebHistory(generateUrl('/apps/circles/teams')),
	routes,
})
