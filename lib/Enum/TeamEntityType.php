<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Enum;

enum TeamEntityType: int {
	case LOCAL_USER = 1;
	case LOCAL_GROUP = 2;
	case MAIL_ADDRESS = 3;
	case TEAM = 4;
	case APP = 90;
	case OCC = 91;
//	case CRON = 92;
//	case ASYNC = 93;
	case SUPER_ADMIN = 99;
}
