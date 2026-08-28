/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteRecordRaw } from 'vue-router'

import { generateUrl } from '@nextcloud/router'
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from './views/HomeView.vue'
import JoinInvitation from './views/JoinInvitation.vue'
import PageView from './views/PageView.vue'
import TeamFolderView from './views/TeamFolderView.vue'
import TeamHomeView from './views/TeamHomeView.vue'
import TeamMembersView from './views/TeamMembersView.vue'
import TeamPage from './views/TeamPage.vue'

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
		// Deliberately unnamed: navigating a named parent with a default-path
		// child skips rendering the child — always target a child route.
		path: '/team/:teamId',
		component: TeamPage,
		props: true,
		children: [
			{
				// The folder tab needs its own path: links to an empty-path child
				// count as links to the parent, which vue-router marks active on
				// every child route.
				path: '',
				redirect: { name: 'team-folder' },
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
				name: 'team-page',
				path: 'page/:fileId',
				component: PageView,
				props: true,
			},
			{
				name: 'team-members',
				path: 'members',
				component: TeamMembersView,
				props: true,
			},
			{
				// The settings page shipped in earlier releases; its links
				// land on the team, where settings is in the sidebar footer.
				path: 'settings',
				redirect: { name: 'team-folder' },
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
