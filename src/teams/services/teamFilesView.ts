/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot, IFolder, INode } from '@nextcloud/files'
import type { InjectionKey, Ref } from 'vue'

import { mdiFolderAccountOutline } from '@mdi/js'
import { getNavigation, View } from '@nextcloud/files'
import { defaultRootPath, getClient, getDefaultPropfind, resultToNode } from '@nextcloud/files/dav'
import { t } from '@nextcloud/l10n'

/** Id of the Files navigation view backing the embedded team file list. */
export const TEAM_FILES_VIEW_ID = 'circles-team-files'

/** Element the Files sidebar is rendered into, provided by the app layout. */
export const SIDEBAR_HOST_KEY: InjectionKey<Ref<HTMLElement | null>> = Symbol('teams-sidebar-host')

export interface RenderFilesAppOptions {
	dir?: string
	rootDir?: string
	sidebarEl?: HTMLElement
}

export type RenderFilesApp = (el: HTMLElement, viewId: string, options?: RenderFilesAppOptions) => { destroy: () => void }

/**
 * The Files app's API to render its file list into another app,
 * only available on servers that provide it.
 */
export function getRenderFilesApp(): RenderFilesApp | undefined {
	return (window as unknown as { OCP?: { Files?: { renderFilesApp?: RenderFilesApp } } })
		.OCP?.Files?.renderFilesApp
}

/**
 * Resolve a path requested by the file list to a path within the team folder,
 * so the embedded file list cannot leave it.
 *
 * @param mountPoint - The team folder mount point of the current user
 * @param path - The requested path, relative to the user's files root
 */
export function resolveTeamFolderPath(mountPoint: string, path: string): string {
	const root = `/${mountPoint}`
	const normalized = path.replace(/\/+$/, '')
	if (normalized.split('/').includes('..')) {
		return root
	}
	return normalized === root || normalized.startsWith(`${root}/`) ? normalized : root
}

/**
 * List a folder within the team folder.
 *
 * @param mountPoint - The team folder mount point of the current user
 * @param path - The requested path, relative to the user's files root
 * @param signal - Signal to abort the request
 */
export async function getTeamFolderContents(mountPoint: string, path: string, signal?: AbortSignal): Promise<ContentsWithRoot> {
	const response = await getClient().getDirectoryContents(`${defaultRootPath}${resolveTeamFolderPath(mountPoint, path)}`, {
		details: true,
		data: getDefaultPropfind(),
		includeSelf: true,
		signal,
	})
	const [root, ...contents] = Array.isArray(response) ? response : response.data

	return {
		folder: resultToNode(root!, defaultRootPath) as IFolder,
		contents: contents.map((entry) => resultToNode(entry, defaultRootPath) as INode),
	}
}

/**
 * Register the Files navigation view listing the given team folder,
 * replacing the one of a previously shown team.
 *
 * @param mountPoint - The team folder mount point of the current user
 */
export function registerTeamFilesView(mountPoint: string): void {
	removeTeamFilesView()
	getNavigation().register(new View({
		id: TEAM_FILES_VIEW_ID,
		name: t('circles', 'Files'),
		caption: t('circles', 'Files of the team folder'),
		icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="${mdiFolderAccountOutline}" /></svg>`,
		hidden: true,
		getContents: (path, options) => getTeamFolderContents(mountPoint, path, options?.signal),
	}))
}

/**
 * Remove the Files navigation view of the team folder, if registered.
 */
export function removeTeamFilesView(): void {
	const navigation = getNavigation()
	if (navigation.views.some((view) => view.id === TEAM_FILES_VIEW_ID)) {
		navigation.remove(TEAM_FILES_VIEW_ID)
	}
}
