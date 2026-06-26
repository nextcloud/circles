/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
export default {
	computed: {
		// router variables
		selectedContact() {
			return this.$route.params.selectedContact
		},
		selectedGroup() {
			return this.$route.params.selectedGroup
		},
		selectedCircle() {
			// Teams app uses `teamId` as the route param; fall back to it.
			return this.$route.params.selectedCircle ?? this.$route.params.teamId
		},
		selectedUserGroup() {
			return this.$route.params.selectedUserGroup
		},
		selectedChart() {
			return this.$route.params.selectedChart
		},
	},
}
