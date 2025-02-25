<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

class ToolsService {
	public function __construct(
	) {
	}

	public function generateSingleId(int $length = 15): string {
		$availableChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$availableCharsLength = strlen($availableChars);
		$result = '';

		for ($i = 0; $i < $length; $i++) {
			$result .= $availableChars[random_int(0, $availableCharsLength - 1)];
		}

		return $result;
	}
}
