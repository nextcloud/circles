<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use Closure;
use NCU\Config\IUserConfig;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Managers\TeamEntityManager;
use OCA\Circles\Managers\TeamManager;
use OCA\Circles\Managers\TeamMemberManager;
use OCA\Circles\Model\Team;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;

class TeamMemberService {
	public function __construct(
		private readonly TeamMemberManager $teamMemberManager,
		private readonly TeamEntityManager $teamEntityManager,
	) {
	}

	public function addMember(TeamEntity $initiator, Team $team, TeamEntity $entity): TeamMember {
		return $this->teamMemberManager->addMember($initiator, $team->getSingleId(), $entity->getSingleId());
	}

}
