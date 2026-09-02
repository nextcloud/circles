<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { INode } from '@nextcloud/files'

import { mdiFile, mdiFolder, mdiFormatListBulletedSquare, mdiOpenInNew, mdiViewGridOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { FileType, formatFileSize } from '@nextcloud/files'
import { defaultRootPath, getClient, getDefaultPropfind, resultToNode } from '@nextcloud/files/dav'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, ref, watch } from 'vue'
import NcBreadcrumb from '@nextcloud/vue/components/NcBreadcrumb'
import NcBreadcrumbs from '@nextcloud/vue/components/NcBreadcrumbs'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { logger } from '../../logger.ts'

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
const nodes = ref<INode[]>([])
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
 * Hidden entries in Nextcloud start with a dot and should not be shown in
 * the Team space widget.
 *
 * @param node - The team folder node
 */
function isHiddenEntry(node: INode): boolean {
	return node.basename.startsWith('.')
}

/**
 * Build the URL for a single node so the list items are real links.
 *
 * Both files and folders open in the Files app using the file id based URL
 * that the Files app itself uses for navigation.
 *
 * @param node - The team folder node
 */
function getNodeUrl(node: INode): string {
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
 * Return the icon path for the given node type. The files app renders the
 * filled folder/file icons in the primary color.
 *
 * @param node - The team folder node
 */
function nodeIconPath(node: INode): string {
	return node.type === FileType.Folder ? mdiFolder : mdiFile
}

/**
 * Display name of a node.
 *
 * @param node - The team folder node
 */
function nodeDisplayName(node: INode): string {
	return node.displayname || node.basename
}

/**
 * File extension (including the dot) of a node's display name. Folders have
 * none, matching the files app's basename/extension split.
 *
 * @param node - The team folder node
 */
function nodeExtension(node: INode): string {
	if (node.type === FileType.Folder) {
		return ''
	}
	const name = nodeDisplayName(node)
	const dot = name.lastIndexOf('.')
	return dot > 0 ? name.slice(dot) : ''
}

/**
 * Display name without the extension.
 *
 * @param node - The team folder node
 */
function nodeBasename(node: INode): string {
	const extension = nodeExtension(node)
	return extension ? nodeDisplayName(node).slice(0, -extension.length) : nodeDisplayName(node)
}

/**
 * Fade small file sizes towards maxcontrast — same math as the files app
 * (FileEntry.vue `sizeOpacity`): quadratic ramp up to 10 MiB.
 *
 * @param node - The team folder node
 */
function sizeStyle(node: INode): Record<string, string> {
	const maxOpacitySize = 10 * 1024 * 1024
	const size = node.size
	if (size === undefined || isNaN(size) || size < 0) {
		return {}
	}
	const ratio = Math.round(Math.min(100, 100 * ((size / maxOpacitySize) ** 2)))
	return { color: `color-mix(in srgb, var(--color-main-text) ${ratio}%, var(--color-text-maxcontrast))` }
}

/**
 * Fade old modification times towards maxcontrast — same math as the files
 * app (FileEntryMixin.ts `mtimeOpacity`): linear fade over 31 days.
 *
 * @param node - The team folder node
 */
function mtimeStyle(node: INode): Record<string, string> {
	if (!node.mtime) {
		return {}
	}
	const maxOpacityTime = 31 * 24 * 60 * 60 * 1000
	const timeDiff = Date.now() - node.mtime.getTime()
	if (timeDiff < 0) {
		return {}
	}
	const percentage = Math.round(Math.max(0, maxOpacityTime - timeDiff) * 100 / maxOpacityTime)
	return { color: `color-mix(in srgb, var(--color-main-text) ${percentage}%, var(--color-text-maxcontrast))` }
}

/**
 * Drop the loading background of a preview once the image has loaded,
 * mimicking the files app's loaded state.
 *
 * @param event - The image load event
 */
function markPreviewLoaded(event: Event): void {
	(event.target as HTMLImageElement).dataset.loaded = 'true'
}

/**
 * Return the preview image URL for a file node, mimicking the Files app.
 *
 * @param node - The team folder node
 * @param size - The preview size in pixels
 */
function nodePreviewUrl(node: INode, size = 128): string | undefined {
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
function onNodeClick(node: INode, event: MouseEvent): void {
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
			const currentNode = resultToNode(currentEntry, rootPath)
			currentFolderFileId.value = currentNode.fileid ?? props.rootFolderId
		}

		nodes.value = data
			.slice(1)
			.map((entry) => resultToNode(entry, rootPath))
			.filter((node) => !isHiddenEntry(node))
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
			<NcBreadcrumbs
				class="team-folder-widget__breadcrumbs"
				:aria-label="t('circles', 'Team folder breadcrumbs')">
				<NcBreadcrumb
					v-for="crumb in breadcrumbs"
					:key="crumb.path"
					:name="crumb.name"
					@click="navigateTo(crumb.path)" />
			</NcBreadcrumbs>

			<div class="team-folder-widget__actions">
				<NcButton
					variant="tertiary"
					size="small"
					:aria-label="viewMode === 'grid' ? t('circles', 'Switch to list view') : t('circles', 'Switch to grid view')"
					@click="toggleViewMode">
					<template #icon>
						<NcIconSvgWrapper :path="viewMode === 'grid' ? mdiFormatListBulletedSquare : mdiViewGridOutline" :size="18" />
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
			:description="t('circles', 'This team folder is empty.')" />

		<ul
			v-else-if="viewMode === 'grid'"
			class="team-folder-widget__list team-folder-widget__list--grid"
			:aria-label="t('circles', 'Folder contents')">
			<li
				v-for="node in nodes"
				:key="node.source"
				class="team-folder-widget__item">
				<a
					class="team-folder-widget__tile"
					:href="getNodeUrl(node)"
					@click="onNodeClick(node, $event)">
					<div class="team-folder-widget__tile-preview">
						<img
							v-if="nodePreviewUrl(node, 256)"
							:src="nodePreviewUrl(node, 256)"
							:alt="t('circles', 'Preview of {name}', { name: node.basename })"
							loading="lazy"
							class="team-folder-widget__preview"
							@load="markPreviewLoaded">
						<NcIconSvgWrapper
							v-else
							class="team-folder-widget__tile-icon"
							:path="nodeIconPath(node)"
							:size="128" />
					</div>
					<div class="team-folder-widget__tile-info">
						<span class="team-folder-widget__tile-name" :title="nodeDisplayName(node)" dir="auto">
							<span class="team-folder-widget__name-base">{{ nodeBasename(node) }}</span>
							<span v-if="nodeExtension(node)" class="team-folder-widget__name-ext">{{ nodeExtension(node) }}</span>
						</span>
					</div>
					<span class="team-folder-widget__tile-mtime" :style="mtimeStyle(node)">
						<NcDateTime v-if="node.mtime" :timestamp="node.mtime" ignoreSeconds />
					</span>
				</a>
			</li>
		</ul>

		<!-- List mode replicates the files app list (FilesListVirtual.vue). -->
		<div v-else class="team-folder-widget__table">
			<div class="team-folder-widget__table-header" aria-hidden="true">
				<span class="team-folder-widget__row-icon" />
				<span class="team-folder-widget__row-name team-folder-widget__row-name--header">{{ t('circles', 'Name') }}</span>
				<span class="team-folder-widget__row-size">{{ t('circles', 'Size') }}</span>
				<span class="team-folder-widget__row-mtime">{{ t('circles', 'Modified') }}</span>
			</div>
			<ul class="team-folder-widget__rows" :aria-label="t('circles', 'Folder contents')">
				<li
					v-for="node in nodes"
					:key="node.source">
					<a
						class="team-folder-widget__row"
						:href="getNodeUrl(node)"
						@click="onNodeClick(node, $event)">
						<span class="team-folder-widget__row-icon">
							<img
								v-if="nodePreviewUrl(node, 32)"
								:src="nodePreviewUrl(node, 32)"
								:alt="t('circles', 'Preview of {name}', { name: node.basename })"
								loading="lazy"
								class="team-folder-widget__row-preview"
								@load="markPreviewLoaded">
							<NcIconSvgWrapper
								v-else
								:path="nodeIconPath(node)"
								:size="node.type === FileType.Folder ? 30 : 24" />
						</span>
						<span class="team-folder-widget__row-name">
							<span class="team-folder-widget__row-name-text" :title="nodeDisplayName(node)" dir="auto">
								<span class="team-folder-widget__name-base">{{ nodeBasename(node) }}</span>
								<span v-if="nodeExtension(node)" class="team-folder-widget__name-ext">{{ nodeExtension(node) }}</span>
							</span>
						</span>
						<span class="team-folder-widget__row-size" :style="sizeStyle(node)">
							{{ node.size !== undefined ? formatFileSize(node.size, true) : '–' }}
						</span>
						<span class="team-folder-widget__row-mtime" :style="mtimeStyle(node)">
							<NcDateTime v-if="node.mtime" :timestamp="node.mtime" ignoreSeconds />
							<template v-else>{{ t('circles', 'Unknown date') }}</template>
						</span>
					</a>
				</li>
			</ul>
		</div>
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
		// Clear the app navigation toggle, like the files app header
		padding-inline: calc(var(--default-clickable-area) + 2 * var(--app-navigation-padding, 4px)) 16px;
		border-bottom: 1px solid var(--color-border);
	}

	&__breadcrumbs {
		flex: 1 1 auto;
		min-width: 0;
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

	// Shared basename/extension split, as in the files app: the extension
	// renders in maxcontrast next to the main-text basename.
	&__name-base {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	&__name-ext {
		color: var(--color-text-maxcontrast);
		white-space: nowrap;
		overflow: visible;
	}

	// Grid mode replicating the files app grid (FilesListVirtual.vue grid
	// style block): fixed 198px tiles (166px preview + 16px padding),
	// name row at clickable-area height, small relative mtime below.
	&__list {
		flex: 1 1 auto;
		overflow-y: auto;
		margin: 0;
		padding: 0;
		list-style: none;
	}

	&__list--grid {
		--item-padding: 16px;
		--icon-preview-size: 166px;
		--name-height: var(--default-clickable-area);
		--mtime-height: calc(var(--font-size-small) + var(--default-grid-baseline));
		--row-width: calc(var(--icon-preview-size) + var(--item-padding) * 2);
		display: grid;
		grid-template-columns: repeat(auto-fill, var(--row-width));
		justify-content: space-around;
		align-content: start;
		padding-block: calc(2 * var(--default-grid-baseline));
	}

	&__tile {
		display: flex;
		flex-direction: column;
		width: var(--row-width);
		box-sizing: border-box;
		padding: var(--item-padding);
		border-radius: var(--border-radius-large);
		color: var(--color-main-text);
		text-decoration: none;

		&:hover,
		&:focus {
			background-color: var(--color-background-hover);
		}

		&:focus-visible {
			outline: 2px solid var(--color-main-text);
			outline-offset: -2px;
		}
	}

	&__tile-preview {
		width: var(--icon-preview-size);
		height: var(--icon-preview-size);
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		border-radius: var(--border-radius);
	}

	&__tile-icon {
		color: var(--color-primary-element);
	}

	&__preview {
		width: 100%;
		height: 100%;
		object-fit: contain;
		object-position: center;

		&:not([data-loaded]) {
			background: var(--color-loading-dark);
		}
	}

	&__tile-info {
		display: flex;
		align-items: center;
		width: var(--icon-preview-size);
		height: var(--name-height);
		min-width: 0;
	}

	&__tile-name {
		display: inline-flex;
		min-width: 0;
		max-width: 100%;
		padding: 0 4px;
		margin-inline-start: -4px;
	}

	&__tile-mtime {
		width: var(--icon-preview-size);
		height: var(--mtime-height);
		font-size: var(--font-size-small);
		color: var(--color-text-maxcontrast);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	// List mode replicating the files app list (FilesListVirtual.vue):
	// 44px rows with a bottom border, 24px primary-colored icon/preview,
	// flexible name, right-aligned size and relative mtime columns whose
	// color fades via the same color-mix formulas.
	&__table {
		--row-height: 44px;
		--icon-preview-size: 24px;
		--cell-margin: 14px;
		--icon-margin: calc((var(--row-height) - var(--icon-preview-size)) / 2);
		flex: 1 1 auto;
		overflow-y: auto;
		display: flex;
		flex-direction: column;
	}

	&__table-header {
		flex: 0 0 auto;
		display: flex;
		align-items: center;
		height: var(--row-height);
		box-sizing: border-box;
		padding-inline: calc(2 * var(--default-grid-baseline));
		border-block-end: 1px solid var(--color-border);
		color: var(--color-text-maxcontrast);
		font-weight: normal;
		user-select: none;
	}

	&__rows {
		margin: 0;
		padding: 0;
		list-style: none;
	}

	&__row {
		display: flex;
		align-items: center;
		height: var(--row-height);
		box-sizing: border-box;
		padding-inline: calc(2 * var(--default-grid-baseline));
		border-block-end: 1px solid var(--color-border);
		color: var(--color-text-maxcontrast);
		text-decoration: none;

		&:hover,
		&:focus,
		&:active {
			background-color: var(--color-background-hover);
			// Same contrast adjustments the files app applies on hover
			--color-text-maxcontrast: var(--color-main-text);
			--color-border: var(--color-border-dark);
		}

		&:focus-visible {
			outline: 2px solid var(--color-main-text);
			outline-offset: -2px;
		}
	}

	&__row-icon {
		display: flex;
		align-items: center;
		justify-content: center;
		flex: 0 0 var(--icon-preview-size);
		width: var(--icon-preview-size);
		height: 100%;
		margin-inline-end: var(--icon-margin);
		color: var(--color-primary-element);
	}

	&__row-preview {
		width: var(--icon-preview-size);
		height: var(--icon-preview-size);
		object-fit: contain;
		object-position: center;
		border-radius: var(--border-radius);

		&:not([data-loaded]) {
			background: var(--color-loading-dark);
		}
	}

	&__row-name {
		flex: 1 1 auto;
		min-width: 0;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;

		&--header {
			padding-inline: calc(2 * var(--default-grid-baseline));
		}
	}

	&__row-name-text {
		display: inline-flex;
		max-width: 100%;
		color: var(--color-main-text);
		padding: var(--default-grid-baseline) calc(2 * var(--default-grid-baseline));
	}

	&__row-size {
		flex: 0 0 auto;
		display: flex;
		justify-content: flex-end;
		width: calc(var(--row-height) * 2);
		margin: 0 var(--cell-margin);
	}

	&__row-mtime {
		flex: 0 0 auto;
		width: calc(var(--row-height) * 2.5);
		margin: 0 var(--cell-margin);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
}
</style>
