<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Enum;

enum TeamApi: int {
	case NOT_API = 0;
	case V1 = 1;
	case V2 = 2;
}
