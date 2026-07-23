<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiFileOutline, mdiFolderOutline, mdiOpenInNew, mdiViewGridOutline, mdiViewListOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { FileType, formatFileSize } from '@nextcloud/files'
import { defaultRootPath, getClient, getDefaultPropfind, resultToNode } from '@nextcloud/files/dav'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { logger } from '../../logger.ts'

/**
 * Minimal node shape needed by the widget.
 *
 * The `@nextcloud/files` Node class exposes the same public interface, but
 * TypeScript requires us to keep the private fields when using the class
 * type directly. Using our own interface keeps the template code simple.
 */
interface TeamFolderNode {
	source: string
	basename: string
	displayname: string
	type: FileType
	mime?: string
	size?: number
	mtime?: Date
	fileid?: number
	attributes: Record<string, unknown>
}

const props = defineProps<{
	mountPoint: string
	rootFolderId?: number
	folderPath?: string
}>()

const emit = defineEmits<{
	(e: 'update:folderPath', path: string): void
}>()

const client = getClient()
const rootPath = defaultRootPath

const currentPath = ref(props.folderPath ?? '')
const nodes = ref<TeamFolderNode[]>([])
const currentFolderFileId = ref<number | undefined>(props.rootFolderId)
const loading = ref(false)
const error = ref<string | null>(null)
const viewMode = ref<'grid' | 'list'>('grid')

/**
 * Build the breadcrumb trail from the team folder mount point and the
 * current sub path.
 */
const breadcrumbs = computed(() => {
	const crumbs = [{ name: props.mountPoint, path: '' }]
	if (!currentPath.value) {
		return crumbs
	}

	const parts = currentPath.value.split('/').filter(Boolean)
	let built = ''
	for (const part of parts) {
		built += '/' + part
		crumbs.push({ name: part, path: built.slice(1) })
	}
	return crumbs
})

/**
 * Toggle between grid and list view.
 */
function toggleViewMode(): void {
	viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid'
}

/**
 * Build the directory path for the current folder inside the team folder.
 */
const currentDir = computed(() => currentPath.value
	? `/${props.mountPoint}/${currentPath.value}`
	: `/${props.mountPoint}`)

/**
 * URL that opens the current folder in the Files app.
 *
 * The Files app accepts file id 0 when the target is a folder; the actual
 * folder path is taken from the `dir` query parameter.
 */
const currentFolderUrl = computed(() => {
	const fileid = currentFolderFileId.value ?? 0
	const url = generateUrl('/apps/files/files/{fileid}', { fileid })
	return `${url}?dir=${encodeDir(currentDir.value)}`
})

/**
 * Encode a directory path for a query parameter while keeping slashes readable.
 *
 * The Files app uses literal `/` characters in its `dir` query parameter, so
 * we encode everything else (spaces, etc.) but leave path separators alone.
 *
 * @param dir - The directory path
 */
function encodeDir(dir: string): string {
	return encodeURIComponent(dir).replace(/%2F/g, '/')
}

/**
 * Build the URL for a single node so the list items are real links.
 *
 * Both files and folders open in the Files app using the file id based URL
 * that the Files app itself uses for navigation.
 *
 * @param node - The team folder node
 */
function getNodeUrl(node: TeamFolderNode): string {
	const dir = node.type === FileType.Folder
		? `${currentDir.value}/${node.basename}`
		: currentDir.value
	const url = generateUrl('/apps/files/files/{fileid}', {
		fileid: node.fileid,
	})
	const openFile = node.type === FileType.File ? '&openfile=true' : ''
	return `${url}?dir=${encodeDir(dir)}${openFile}`
}

/**
 * Return the icon path for the given node type.
 *
 * @param node - The team folder node
 */
function nodeIconPath(node: TeamFolderNode): string {
	return node.type === FileType.Folder ? mdiFolderOutline : mdiFileOutline
}

/**
 * Return the preview image URL for a file node, mimicking the Files app.
 *
 * @param node - The team folder node
 * @param size - The preview size in pixels
 */
function nodePreviewUrl(node: TeamFolderNode, size = 128): string | undefined {
	if (node.type === FileType.Folder) {
		return undefined
	}

	const hasPreview = node.attributes['has-preview'] === true
	const mime = node.mime
	if (!hasPreview && mime && mime !== 'application/octet-stream') {
		const url = new URL(window.location.origin + generateUrl('/core/mimeicon?mime={mime}', { mime }))
		return url.href
	}

	const previewUrl = (node.attributes.previewUrl as string | undefined)
		|| generateUrl('/core/preview?fileId={fileid}', { fileid: String(node.fileid) })
	const url = new URL(window.location.origin + previewUrl)
	url.searchParams.set('x', size.toString())
	url.searchParams.set('y', size.toString())
	url.searchParams.set('mimeFallback', 'true')
	url.searchParams.set('v', ((node.attributes.etag as string | undefined) || node.mtime?.getTime() || '').toString().slice(0, 6))
	url.searchParams.set('a', '0')
	return url.href
}

/**
 * Handle a click on a node.
 *
 * Folders are navigated inside the widget. Files use the real link href
 * to open in the Files app, so we do not intercept those clicks.
 *
 * @param node - The team folder node
 * @param event - The click event
 */
function onNodeClick(node: TeamFolderNode, event: MouseEvent): void {
	if (node.type !== FileType.Folder) {
		return
	}

	event.preventDefault()
	const newPath = currentPath.value
		? `${currentPath.value}/${node.basename}`
		: node.basename
	navigateTo(newPath)
}

/**
 * Navigate the widget to a specific breadcrumb path.
 *
 * @param path - The relative path inside the team folder
 */
function navigateTo(path: string): void {
	currentPath.value = path
	emit('update:folderPath', path)
}

/**
 * Load the contents of the current folder via WebDAV.
 */
async function loadContents(): Promise<void> {
	loading.value = true
	error.value = null
	nodes.value = []

	const relativePath = currentPath.value
		? `${props.mountPoint}/${currentPath.value}`
		: props.mountPoint
	const davPath = `${rootPath}/${relativePath}`

	try {
		const response = await client.getDirectoryContents(davPath, {
			details: true,
			data: getDefaultPropfind(),
			includeSelf: true,
		})
		const data = Array.isArray(response) ? response : response.data

		if (!Array.isArray(data)) {
			throw new Error('Invalid response from server')
		}

		// The first entry is the current directory itself. Use its file id
		// for the "Open in Files" header button, because the GroupFolders
		// folder id is not the same as the DAV file id.
		const currentEntry = data[0]
		if (currentEntry) {
			const currentNode = resultToNode(currentEntry, rootPath) as TeamFolderNode
			currentFolderFileId.value = currentNode.fileid ?? props.rootFolderId
		}

		nodes.value = data
			.slice(1)
			.map((entry) => {
				const node = resultToNode(entry, rootPath) as TeamFolderNode
				// resultToNode returns a @nextcloud/files Node instance whose
				// `attributes` property is read-only, so build a plain object.
				return {
					source: node.source,
					basename: node.basename,
					displayname: node.displayname,
					type: node.type,
					mime: node.mime,
					size: node.size,
					mtime: node.mtime,
					fileid: node.fileid,
					attributes: entry.props ? { ...entry.props } : {},
				} as TeamFolderNode
			})
			.sort((a, b) => {
				if (a.type === b.type) {
					return a.basename.localeCompare(b.basename)
				}
				return a.type === FileType.Folder ? -1 : 1
			})
	} catch (err) {
		const status = (err as { status?: number })?.status
		// A 404 on the team folder root likely means the folder is empty and
		// has not been physically created by GroupFolders yet. Show the empty
		// state instead of an error in that case.
		if (status === 404 && currentPath.value === '') {
			nodes.value = []
		} else {
			logger.error('Could not load team folder contents', { err, path: davPath })
			error.value = t('circles', 'Could not load folder contents')
			showError(error.value)
		}
	} finally {
		loading.value = false
	}
}

watch(() => props.folderPath, (path) => {
	currentPath.value = path ?? ''
}, { immediate: true })

watch(() => [props.mountPoint, currentPath.value], loadContents, { immediate: true })
</script>

<template>
	<div class="team-folder-widget">
		<div class="team-folder-widget__header">
			<nav
				class="team-folder-widget__breadcrumbs"
				:aria-label="t('circles', 'Team space breadcrumbs')">
				<ol class="team-folder-widget__breadcrumbs-list">
					<li
						v-for="(crumb, index) in breadcrumbs"
						:key="crumb.path"
						class="team-folder-widget__breadcrumbs-item">
						<NcButton
							v-if="index < breadcrumbs.length - 1"
							variant="tertiary"
							size="small"
							@click="navigateTo(crumb.path)">
							{{ crumb.name }}
						</NcButton>
						<span
							v-else
							class="team-folder-widget__breadcrumbs-current"
							aria-current="location">
							{{ crumb.name }}
						</span>
						<span
							v-if="index < breadcrumbs.length - 1"
							class="team-folder-widget__breadcrumbs-separator"
							aria-hidden="true">
							/
						</span>
					</li>
				</ol>
			</nav>

			<div class="team-folder-widget__actions">
				<NcButton
					variant="tertiary"
					size="small"
					:aria-label="viewMode === 'grid' ? t('circles', 'Switch to list view') : t('circles', 'Switch to grid view')"
					@click="toggleViewMode">
					<template #icon>
						<NcIconSvgWrapper :path="viewMode === 'grid' ? mdiViewListOutline : mdiViewGridOutline" :size="18" />
					</template>
				</NcButton>
				<NcButton
					:href="currentFolderUrl"
					variant="tertiary"
					size="small"
					:aria-label="t('circles', 'Open folder in Files')">
					<template #icon>
						<NcIconSvgWrapper :path="mdiOpenInNew" :size="18" />
					</template>
					{{ t('circles', 'Files') }}
				</NcButton>
			</div>
		</div>

		<div v-if="loading" class="team-folder-widget__loading">
			<NcLoadingIcon :size="44" />
		</div>

		<NcEmptyContent
			v-else-if="error"
			:name="t('circles', 'Folder contents unavailable')"
			:description="error" />

		<NcEmptyContent
			v-else-if="nodes.length === 0"
			:name="t('circles', 'Empty folder')"
			:description="t('circles', 'This team space is empty.')" />

		<ul
			v-else
			class="team-folder-widget__list"
			:class="{ 'team-folder-widget__list--grid': viewMode === 'grid' }"
			:aria-label="t('circles', 'Folder contents')">
			<li
				v-for="node in nodes"
				:key="node.source"
				class="team-folder-widget__item"
				:class="{ 'team-folder-widget__item--folder': node.type === FileType.Folder }">
				<a
					class="team-folder-widget__tile"
					:class="{ 'team-folder-widget__tile--list': viewMode === 'list' }"
					:href="getNodeUrl(node)"
					@click="onNodeClick(node, $event)">
					<div class="team-folder-widget__tile-preview">
						<img
							v-if="nodePreviewUrl(node, 64) && viewMode === 'grid'"
							:src="nodePreviewUrl(node, 64)"
							:alt="t('circles', 'Preview of {name}', { name: node.basename })"
							loading="lazy"
							class="team-folder-widget__preview">
						<NcIconSvgWrapper
							v-else
							class="team-folder-widget__tile-icon"
							:path="nodeIconPath(node)"
							:size="viewMode === 'grid' ? 32 : 20" />
					</div>
					<div class="team-folder-widget__tile-info">
						<span class="team-folder-widget__tile-name" :title="node.displayname || node.basename">
							{{ node.displayname || node.basename }}
						</span>
						<span class="team-folder-widget__tile-meta">
							<span v-if="node.type === FileType.Folder" class="team-folder-widget__tile-type">
								{{ t('circles', 'Folder') }}
							</span>
							<span v-else-if="node.size !== undefined" class="team-folder-widget__tile-size">
								{{ formatFileSize(node.size) }}
							</span>
							<span v-if="node.mtime" class="team-folder-widget__tile-mtime">
								<NcDateTime :timestamp="node.mtime" :relativeTime="false" ignoreSeconds />
							</span>
						</span>
					</div>
				</a>
			</li>
		</ul>
	</div>
</template>

<style lang="scss" scoped>
.team-folder-widget {
	display: flex;
	flex-direction: column;
	height: 100%;

	&__header {
		flex: 0 0 auto;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		padding: 8px 16px;
		border-bottom: 1px solid var(--color-border);
	}

	&__breadcrumbs {
		flex: 1 1 auto;
		min-width: 0;
	}

	&__breadcrumbs-list {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 4px;
		margin: 0;
		padding: 0;
		list-style: none;
	}

	&__breadcrumbs-item {
		display: flex;
		align-items: center;
		gap: 4px;
	}

	&__breadcrumbs-current {
		padding: 4px 8px;
		font-weight: bold;
	}

	&__breadcrumbs-separator {
		color: var(--color-text-maxcontrast);
	}

	&__loading {
		flex: 1 1 auto;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	&__actions {
		display: flex;
		align-items: center;
		gap: 4px;
		flex-shrink: 0;
	}

	&__list {
		flex: 1 1 auto;
		overflow-y: auto;
		margin: 0;
		padding: 0;
		list-style: none;
	}

	&__list--grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
		gap: calc(2 * var(--default-grid-baseline));
		padding: calc(2 * var(--default-grid-baseline));
		align-content: start;
	}

	&__list--grid &__item {
		width: 100%;
	}

	&__tile {
		display: flex;
		flex-direction: column;
		gap: calc(2 * var(--default-grid-baseline));
		padding: calc(2 * var(--default-grid-baseline));
		border: 2px solid var(--color-border);
		border-radius: var(--border-radius-container, 16px);
		background-color: var(--color-main-background);
		color: var(--color-main-text);
		text-decoration: none;

		&:hover {
			background-color: var(--color-background-hover);
			border-color: var(--color-primary-element);
		}

		&:focus-visible {
			outline: 2px solid var(--color-main-text);
			outline-offset: 2px;
		}

		&--list {
			flex-direction: row;
			align-items: center;
			gap: calc(2 * var(--default-grid-baseline));
			padding: calc(1.5 * var(--default-grid-baseline)) calc(2 * var(--default-grid-baseline));
			border-width: 0 0 1px 0;
			border-radius: 0;

			&:hover {
				background-color: var(--color-background-hover);
			}
		}
	}

	&__tile-preview {
		aspect-ratio: 1 / 1;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		border-radius: var(--border-radius-large);
		background-color: var(--color-background-dark);
		flex-shrink: 0;
		width: 100%;
		height: auto;

		.team-folder-widget__tile--list & {
			width: 32px;
			height: 32px;
			border-radius: var(--border-radius);
			background-color: transparent;
		}
	}

	&__tile-icon {
		color: var(--color-text-maxcontrast);
	}

	&__preview {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	&__tile-info {
		display: flex;
		flex-direction: column;
		gap: var(--default-grid-baseline);
		min-width: 0;
		width: 100%;

		.team-folder-widget__tile--list & {
			flex-direction: row;
			align-items: center;
			justify-content: space-between;
			gap: calc(2 * var(--default-grid-baseline));
		}
	}

	&__tile-name {
		font-weight: 600;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		flex: 1 1 auto;
		min-width: 0;
	}

	&__tile-meta {
		display: flex;
		flex-wrap: wrap;
		gap: 0 calc(2 * var(--default-grid-baseline));
		color: var(--color-text-maxcontrast);
		font-size: 0.9em;
		flex-shrink: 0;

		.team-folder-widget__tile--list & {
			justify-content: flex-end;
			min-width: 140px;
		}
	}

	&__tile-mtime {
		white-space: nowrap;
	}
}
</style>
