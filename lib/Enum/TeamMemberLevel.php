<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Enum;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

enum TeamMemberLevel: int {
	case INVITED = 0;
	case MEMBER = 1;
	case MODERATOR = 4;
	case ADMIN = 8;
	case OWNER = 9;
}
