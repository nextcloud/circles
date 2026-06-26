/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// vuex 4 ships types that don't resolve under "bundler" module resolution;
// the ported team page only needs them loosely.
declare module 'vuex'
