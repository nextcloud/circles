<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

class TeamSettingsService {
	// Bound user-supplied settings to prevent oversized IDs and lists.
	private const MAX_ID_LENGTH = 64;
	private const MAX_ENTRIES = 100;

	/** @return list<string> */
	public static function sanitizeIdList(array $ids): array {
		$ids = array_filter($ids, static fn ($id): bool => is_string($id)
			&& $id !== ''
			&& strlen($id) <= self::MAX_ID_LENGTH);

		return array_slice(array_values(array_unique($ids)), 0, self::MAX_ENTRIES);
	}
}
