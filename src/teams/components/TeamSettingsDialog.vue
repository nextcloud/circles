<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Component } from 'vue'
import type Circle from '../team-page/models/circle.ts'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { FilePickerClosed, FilePickerType, getFilePickerBuilder, showError, showSuccess } from '@nextcloud/dialogs'
import { emit as emitEvent } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { encodePath } from '@nextcloud/paths'
import { generateOcsUrl, generateRemoteUrl } from '@nextcloud/router'
import { computed, markRaw, nextTick, onBeforeUnmount, ref, watch } from 'vue'
// @ts-expect-error vue-cropperjs ships no type declarations
import VueCropper from 'vue-cropperjs'
import { useStore } from 'vuex'
import NcAppSettingsDialog from '@nextcloud/vue/components/NcAppSettingsDialog'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import IconFolder from 'vue-material-design-icons/Folder.vue'
import IconLogout from 'vue-material-design-icons/Logout.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import IconUpload from 'vue-material-design-icons/TrayArrowUp.vue'
import CirclePasswordSettings from '../team-page/components/CircleDetails/CirclePasswordSettings.vue'
import ContentHeading from '../team-page/components/CircleDetails/ContentHeading.vue'
import TeamAvatar, { getAvatarUrl } from './TeamAvatar.vue'
import { logger } from '../../logger.ts'
import { useTeamActions } from '../composables/useTeamActions.ts'
import { useTeamsStore } from '../store.ts'
import { PUBLIC_CIRCLE_CONFIG } from '../team-page/models/constants.ts'
import { CircleEdit, editCircle } from '../team-page/services/circles.ts'

import 'cropperjs/dist/cropper.css'

type ConfigSection = {
	component: Component
	props: Record<string, unknown>
}

const open = defineModel<boolean>('open', { required: true })

const props = defineProps<{
	circle: Circle
}>()

const configSections = markRaw(PUBLIC_CIRCLE_CONFIG) as Record<string, ConfigSection>

const store = useStore()
const teamsStore = useTeamsStore()
const team = computed(() => teamsStore.getTeam(props.circle.id))
const { onLeave, onDelete } = useTeamActions(() => team.value)

// Editable copies of the team details, reset whenever the dialog opens.
const displayName = ref(props.circle.displayName)
const description = ref(props.circle.description)
const saving = ref(false)

// --- Team picture ---------------------------------------------------------

const VALID_MIME_TYPES = ['image/png', 'image/jpeg']
const AVATAR_ACTIONS = Object.freeze({
	SET: 'set',
	DELETE: 'delete',
})

const picker = getFilePickerBuilder(t('circles', 'Choose a team picture'))
	.setMultiSelect(false)
	.setMimeTypeFilter(VALID_MIME_TYPES)
	.setType(FilePickerType.Choose)
	.allowDirectories(false)
	.build()

const nextcloudMajorVersion = parseInt((window as unknown as { OC: { config: { version: string } } }).OC.config.version.split('.')[0])
const avatarSupported = nextcloudMajorVersion >= 34
const avatarAccept = VALID_MIME_TYPES.join(',')

const avatarInput = ref<HTMLInputElement | null>(null)
const cropper = ref<InstanceType<typeof VueCropper> | null>(null)
const showCropper = ref(false)
const avatarUrl = ref<string | undefined>(undefined)
const pendingAvatarBlob = ref<Blob | null>(null)
const pendingAvatarAction = ref<string | null>(null)
const pendingAvatarPreviewUrl = ref<string | undefined>(undefined)

const cropperOptions = {
	aspectRatio: 1 / 1,
	viewMode: 1,
	guides: false,
	center: false,
	highlight: false,
	autoCropArea: 1,
	minContainerWidth: 300,
	minContainerHeight: 300,
}

const displayAvatarUrl = computed(() => {
	if (!avatarSupported || pendingAvatarAction.value === AVATAR_ACTIONS.DELETE) {
		return undefined
	}
	return pendingAvatarPreviewUrl.value ?? avatarUrl.value
})

/**
 * Probe whether the team has a picture; the dialog needs to know to offer
 * its deletion. The probe also primes the browser cache for the preview.
 */
async function loadAvatarUrl(): Promise<void> {
	if (!avatarSupported || !props.circle.isMember) {
		return
	}
	const url = getAvatarUrl(props.circle.id)
	try {
		await axios.get(url)
		avatarUrl.value = url
	} catch {
		avatarUrl.value = undefined
	}
}

/** Open the local file picker for a new team picture. */
function openLocalFilePicker(): void {
	if (avatarInput.value) {
		avatarInput.value.value = ''
		avatarInput.value.click()
	}
}

/**
 * Load the picked local file into the cropper.
 *
 * @param event - The file input change event
 */
function onAvatarInputChange(event: Event): void {
	try {
		const file = (event.target as HTMLInputElement).files?.[0]
		if (!file || !VALID_MIME_TYPES.includes(file.type)) {
			showError(t('circles', 'Please select a valid png or jpg file'))
			return
		}
		loadFileIntoCropper(file)
	} catch (error) {
		logger.error('Error picking avatar file', { error })
		showError(t('circles', 'Error picking team picture'))
	}
}

/** Pick a team picture from Nextcloud Files and load it into the cropper. */
async function openFilePicker(): Promise<void> {
	try {
		const path = await picker.pick()
		if (!path) {
			return
		}
		const fileResponse = await axios.get(
			generateRemoteUrl(`dav/files/${getCurrentUser()!.uid}`) + encodePath(path),
			{ responseType: 'blob' },
		)
		loadFileIntoCropper(fileResponse.data)
	} catch (error) {
		if (error instanceof FilePickerClosed) {
			return
		}
		logger.error('Error picking avatar file', { error })
		showError(t('circles', 'Error picking team picture'))
	}
}

/**
 * Show the cropper dialog with the given image.
 *
 * @param file - The picked image blob
 */
function loadFileIntoCropper(file: Blob): void {
	const reader = new FileReader()
	reader.onload = async (event) => {
		showCropper.value = true
		await nextTick()
		cropper.value?.replace(event.target?.result)
	}
	reader.readAsDataURL(file)
}

/** Stage the cropped picture; it is uploaded on save. */
function setAvatar(): void {
	showCropper.value = false
	cropper.value?.getCroppedCanvas({
		minWidth: 16,
		minHeight: 16,
		maxWidth: 512,
		maxHeight: 512,
	}).toBlob((blob: Blob | null) => {
		if (blob === null) {
			showError(t('circles', 'Error cropping avatar picture'))
			cancelSetAvatar()
			return
		}
		if (pendingAvatarPreviewUrl.value) {
			URL.revokeObjectURL(pendingAvatarPreviewUrl.value)
		}
		pendingAvatarBlob.value = blob
		pendingAvatarAction.value = AVATAR_ACTIONS.SET
		pendingAvatarPreviewUrl.value = URL.createObjectURL(blob)
	})
}

/** Close the cropper dialog without staging a picture. */
function cancelSetAvatar(): void {
	showCropper.value = false
	if (avatarInput.value) {
		avatarInput.value.value = ''
	}
}

/** Stage the removal of the team picture; it is applied on save. */
function removeAvatar(): void {
	if (pendingAvatarPreviewUrl.value) {
		URL.revokeObjectURL(pendingAvatarPreviewUrl.value)
	}
	pendingAvatarBlob.value = null
	pendingAvatarAction.value = AVATAR_ACTIONS.DELETE
	pendingAvatarPreviewUrl.value = undefined
}

/** Drop any staged picture change. */
function clearPendingAvatar(): void {
	if (pendingAvatarPreviewUrl.value) {
		URL.revokeObjectURL(pendingAvatarPreviewUrl.value)
	}
	pendingAvatarBlob.value = null
	pendingAvatarAction.value = null
	pendingAvatarPreviewUrl.value = undefined
}

// Reset the editable details whenever the dialog opens. Registered after the
// avatar state above — the immediate run happens during setup, where the
// callback must not touch a not-yet-initialized const.
watch([open, () => props.circle.id], () => {
	if (!open.value) {
		return
	}
	displayName.value = props.circle.displayName
	description.value = props.circle.description
	clearPendingAvatar()
	loadAvatarUrl()
}, { immediate: true })

// avatarUrl points at the endpoint; only the pending preview is an object URL.
onBeforeUnmount(() => {
	if (pendingAvatarPreviewUrl.value !== undefined) {
		URL.revokeObjectURL(pendingAvatarPreviewUrl.value)
	}
})

// --- Saving ----------------------------------------------------------------

const detailsChanged = computed(() => displayName.value !== props.circle.displayName
	|| description.value !== props.circle.description
	|| pendingAvatarAction.value !== null)

/** Save name, description and picture sequentially to avoid race conditions. */
async function saveDetails(): Promise<void> {
	const errors: string[] = []
	saving.value = true
	cancelSetAvatar()

	let detailsSaved = false

	if (displayName.value !== props.circle.displayName) {
		try {
			await editCircle(props.circle.id, CircleEdit.Name, displayName.value)
			detailsSaved = true
		} catch (error) {
			logger.error('Unable to edit name', { displayName: displayName.value, error })
			errors.push(t('circles', 'name'))
			displayName.value = props.circle.displayName
		}
	}

	if (description.value !== props.circle.description) {
		try {
			await editCircle(props.circle.id, CircleEdit.Description, description.value)
			detailsSaved = true
		} catch (error) {
			logger.error('Unable to edit team description', { description: description.value, error })
			errors.push(t('circles', 'description'))
			description.value = props.circle.description
		}
	}

	if (detailsSaved) {
		// Refresh the circle so every consumer shows the saved details.
		try {
			await store.dispatch('getCircle', props.circle.id)
		} catch (error) {
			logger.error('Could not refresh the team', { error, circleId: props.circle.id })
		}
	}

	if (avatarSupported && pendingAvatarAction.value === AVATAR_ACTIONS.SET && pendingAvatarBlob.value) {
		const formData = new FormData()
		formData.append('file', pendingAvatarBlob.value)
		try {
			await axios.post(generateOcsUrl(`/apps/circles/circles/${props.circle.id}/avatar`), formData)
			// The event bumps the cache-busting version for every consumer,
			// so the URL points at the fresh picture.
			clearPendingAvatar()
			emitEvent('circles:avatar:updated', props.circle.id)
			avatarUrl.value = getAvatarUrl(props.circle.id)
		} catch {
			logger.error('Unable to save avatar picture')
			errors.push(t('circles', 'picture'))
		}
	}

	if (avatarSupported && pendingAvatarAction.value === AVATAR_ACTIONS.DELETE) {
		try {
			await axios.delete(generateOcsUrl(`/apps/circles/circles/${props.circle.id}/avatar`))
			clearPendingAvatar()
			avatarUrl.value = undefined
			emitEvent('circles:avatar:updated', props.circle.id)
		} catch {
			logger.error('Unable to remove avatar')
			errors.push(t('circles', 'picture'))
		}
	}

	saving.value = false
	if (errors.length > 0) {
		showError(t('circles', 'An error happened while saving {fields}', { fields: errors.join(', ') }))
		return
	}
	showSuccess(t('circles', 'Team details saved'))
}

// --- Danger zone -----------------------------------------------------------

/** Leave the team; the composable asks for confirmation first. */
function onLeaveTeam(): void {
	open.value = false
	onLeave()
}

/** Delete the team; the composable asks for confirmation first. */
function onDeleteTeam(): void {
	open.value = false
	onDelete()
}
</script>

<template>
	<NcAppSettingsDialog
		v-model:open="open"
		:name="t('circles', 'Team settings')">
		<NcAppSettingsSection id="details" :name="t('circles', 'Team details')">
			<div class="team-settings-details">
				<div class="team-settings-details__avatar">
					<img
						v-if="displayAvatarUrl"
						class="team-settings-details__avatar-preview"
						:src="displayAvatarUrl"
						:alt="t('circles', 'Team picture')">
					<TeamAvatar
						v-else
						:displayName="circle.displayName"
						:circleId="circle.id"
						:size="64" />

					<div v-if="avatarSupported" class="team-settings-details__avatar-buttons">
						<NcButton :disabled="saving" @click="openLocalFilePicker">
							<template #icon>
								<IconUpload :size="20" />
							</template>
							{{ t('circles', 'Upload team picture') }}
						</NcButton>
						<NcButton :disabled="saving" @click="openFilePicker">
							<template #icon>
								<IconFolder :size="20" />
							</template>
							{{ t('circles', 'Choose from Nextcloud Files') }}
						</NcButton>
						<NcButton
							v-if="displayAvatarUrl !== undefined"
							:disabled="saving"
							@click="removeAvatar">
							<template #icon>
								<IconDelete :size="20" />
							</template>
							{{ t('circles', 'Delete picture') }}
						</NcButton>
						<input
							ref="avatarInput"
							type="file"
							:accept="avatarAccept"
							class="hidden-visually"
							@change="onAvatarInputChange">
					</div>
				</div>

				<NcTextField
					v-model="displayName"
					:label="t('circles', 'Team name')"
					:disabled="saving" />

				<NcTextArea
					v-model="description"
					:label="t('circles', 'Description')"
					:placeholder="t('circles', 'Enter a description for the team')"
					:maxlength="1024"
					:disabled="saving" />

				<div class="team-settings-details__save">
					<NcButton
						variant="primary"
						:disabled="saving || !detailsChanged"
						@click="saveDetails">
						{{ t('circles', 'Save') }}
					</NcButton>
				</div>
			</div>
		</NcAppSettingsSection>

		<NcAppSettingsSection id="settings" :name="t('circles', 'Team settings')">
			<div class="team-settings-groups">
				<div
					v-for="(config, title) in configSections"
					:key="title"
					class="team-settings-groups__group">
					<ContentHeading>{{ title }}</ContentHeading>
					<component
						:is="config.component"
						v-bind="config.props"
						:circle="circle" />
				</div>

				<div class="team-settings-groups__group">
					<ContentHeading>{{ t('circles', 'Password protection') }}</ContentHeading>
					<CirclePasswordSettings :circle="circle" />
				</div>

				<div class="team-settings-danger">
					<NcButton
						v-if="circle.canLeave"
						variant="warning"
						@click="onLeaveTeam">
						<template #icon>
							<IconLogout :size="16" />
						</template>
						{{ t('circles', 'Leave team') }}
					</NcButton>

					<NcButton
						v-if="circle.canDelete"
						variant="error"
						@click="onDeleteTeam">
						<template #icon>
							<IconDelete :size="20" />
						</template>
						{{ t('circles', 'Delete team') }}
					</NcButton>
				</div>
			</div>
		</NcAppSettingsSection>

		<NcDialog
			v-if="showCropper"
			class="team-settings-cropper-dialog"
			:name="t('circles', 'Edit team picture')"
			:open="showCropper"
			size="normal"
			@closing="cancelSetAvatar">
			<VueCropper
				ref="cropper"
				class="team-settings-cropper"
				v-bind="cropperOptions" />
			<template #actions>
				<NcButton @click="cancelSetAvatar">
					{{ t('circles', 'Cancel') }}
				</NcButton>
				<NcButton variant="primary" @click="setAvatar">
					{{ t('circles', 'Apply') }}
				</NcButton>
			</template>
		</NcDialog>
	</NcAppSettingsDialog>
</template>

<style lang="scss" scoped>
.team-settings-details {
	display: flex;
	flex-direction: column;
	gap: calc(3 * var(--default-grid-baseline));

	&__avatar {
		display: flex;
		align-items: center;
		gap: calc(3 * var(--default-grid-baseline));
	}

	&__avatar-preview {
		width: 64px;
		height: 64px;
		border-radius: 50%;
		object-fit: cover;
	}

	&__avatar-buttons {
		display: flex;
		flex-wrap: wrap;
		gap: var(--default-grid-baseline);
	}

	&__save {
		display: flex;
		justify-content: flex-end;
	}
}

.team-settings-groups {
	display: flex;
	flex-direction: column;
	gap: calc(4 * var(--default-grid-baseline));

	&__group {
		display: flex;
		flex-direction: column;
		gap: var(--default-grid-baseline);
	}
}

.team-settings-danger {
	display: flex;
	flex-wrap: wrap;
	gap: calc(2 * var(--default-grid-baseline));
}

</style>

<style lang="scss">
// NcAppSettingsDialog hardcodes size="large" (a 900px modal) to fit its
// section navigation; without the navigation, the single column reads
// better at the normal dialog width. The doubled wrapper class outweighs
// the library's equally specific width rule, which loads later.
.app-settings .modal-wrapper.modal-wrapper--large > .modal-container {
	width: 600px;
}

// The dialog title already names the settings and the groups carry their
// own headings, so the big section headings only repeat them. The names
// stay on the sections for their registration and aria labels.
.app-settings .app-settings-section__name {
	display: none;
}
</style>
