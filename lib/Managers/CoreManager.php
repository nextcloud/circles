<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Managers;

use OCA\Circles\Api\v2\TeamSession;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Model\TeamEntity;

class CoreManager {
	public function __construct(
	) {
	}

	protected function filterInitiator(TeamSession|TeamEntity $initiator): ?TeamEntity {
		if ($initiator instanceof TeamSession) {
			$initiator = $initiator->getSessionEntity();
		}

		if (in_array($initiator->getTeamEntityType(), [
			TeamEntityType::OCC,
			TeamEntityType::SUPER_ADMIN,
		], true)) {
			return null;
		}

		return $initiator;
	}

}
