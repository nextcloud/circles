/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { ShareType } from '@nextcloud/sharing'
import { CircleConfigs, SHARES_TYPES_MEMBER_MAP } from '../models/constants.ts'
import { logger } from '../../../logger.ts'

// generate allowed shareType from SHARES_TYPES_MEMBER_MAP
const shareType = Object.keys(SHARES_TYPES_MEMBER_MAP)
const maxAutocompleteResults = parseInt(window.OC.config['sharing.maxAutocompleteResults'], 10) || 25

/**
 * Get suggestions
 *
 * @param {string} search the search query
 * @param {object|null} circle - the circle object
 */
export async function getSuggestions(search, circle = null) {
	const request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees'), {
		params: {
			format: 'json',
			itemType: 'teams',
			search,
			perPage: maxAutocompleteResults,
			shareType,
			lookup: false,
		},
	})

	const data = request.data.ocs.data
	const exact = request.data.ocs.data.exact
	data.exact = [] // removing exact from general results

	// flatten array of arrays
	let rawExactSuggestions = Object.values(exact).reduce((arr, elem) => arr.concat(elem), [])
	let rawSuggestions = Object.values(data).reduce((arr, elem) => arr.concat(elem), [])

	// check if federation flag is enabled using bitwise AND on circle config
	const isCircleFederated = circle ? (circle.config & CircleConfigs.FEDERATED) !== 0 : false
	if (isCircleFederated) {
		// remove results from untrusted remote servers
		rawExactSuggestions = rawExactSuggestions
			.filter((result) => !(result.value?.shareType === ShareType.Remote && result.value?.isTrustedServer === false))
		rawSuggestions = rawSuggestions
			.filter((result) => !(result.value?.shareType === ShareType.Remote && result.value?.isTrustedServer === false))
	} else {
		// remove all results from remote servers
		rawExactSuggestions = rawExactSuggestions
			.filter((result) => result.value?.shareType !== ShareType.Remote)
		rawSuggestions = rawSuggestions
			.filter((result) => result.value?.shareType !== ShareType.Remote)
	}

	// remove invalid data and format to user-select layout
	const exactSuggestions = rawExactSuggestions
		.filter((result) => typeof result === 'object')
		.map((share) => formatResults(share))
		// sort by type so we can get user&groups first...
		.sort((a, b) => a.shareType - b.shareType)
	const suggestions = rawSuggestions
		.filter((result) => typeof result === 'object')
		.map((share) => formatResults(share))
		// sort by type so we can get user&groups first...
		.sort((a, b) => a.shareType - b.shareType)

	const finalResults = exactSuggestions.concat(suggestions)

	logger.info('suggestions', { finalResults })

	return finalResults
}

/**
 * Get the sharing recommendations
 */
export async function getRecommendations() {
	const request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees_recommended'), {
		params: {
			format: 'json',
			itemType: 'contacts',
			shareType,
		},
	})

	// flatten array of arrays
	const exact = request.data.ocs.data.exact
	const recommendations = Object.values(exact).reduce((arr, elem) => arr.concat(elem), [])

	// remove invalid data and format to user-select layout
	const finalResults = recommendations
		.map((share) => formatResults(share))

	logger.info('recommendations', finalResults)

	return finalResults
}

/**
 * Secondary line telling apart entries with the same name,
 * same rules as the files_sharing sharing input.
 *
 * @param {object} result raw sharee result
 * @return {string}
 */
function getSubname(result) {
	switch (result.value.shareType) {
		case ShareType.User:
			// Admins can hide the email/unique name for privacy
			return getCapabilities().files_sharing?.sharee?.always_show_unique === true
				? result.shareWithDisplayNameUnique ?? ''
				: ''
		case ShareType.Email:
			return result.value.shareWith
		case ShareType.Remote:
		case ShareType.RemoteGroup:
			return result.value.server
				? t('circles', 'on {server}', { server: result.value.server })
				: ''
		default:
			return result.shareWithDescription ?? ''
	}
}

function formatResults(result) {
	const type = `picker-${result.value.shareType}`
	return {
		label: result.label,
		id: `${type}-${result.value.shareWith}`,
		// If this is a user, set as user for avatar display by UserBubble
		user: [ShareType.User, ShareType.Remote].indexOf(result.value.shareType) > -1
			? result.value.shareWith
			: null,
		type,
		subname: getSubname(result),
		...result.value,
	}
}
