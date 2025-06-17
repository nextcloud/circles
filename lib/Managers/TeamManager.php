<?php
/*
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Managers;


use OCA\Circles\Db\TeamMapper;
use OCA\Circles\Exceptions\TeamNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Team;

class TeamManager {
	public function __construct(
		private TeamMapper $teamMapper,
	) {
	}

	/**
	 * @throws TeamNotFoundException
	 */
	public function getTeam(string $singleId): Team {
		return $this->teamMapper->getBySingleId($singleId);
	}

	public function confirmNaming(Team $team): void {
		if ($team->isConfig(Circle::CFG_SYSTEM)
			|| $team->isConfig(Circle::CFG_SINGLE)) {
			return;
		}

//		$this->confirmDisplayName($circle);
//		$this->generateSanitizedName($circle);
	}

//	public function createTeam(
//		TeamEntityType $type,
//		string $origId,
//		string $displayName,
//	): TeamEntity {
//		$teamEntity = new TeamEntity();
//		$teamEntity->setTeamEntityType($type);
//		$teamEntity->setOrigId($origId);
//		$teamEntity->setDisplayName($displayName);
//		$this->teamEntityMapper->insert($teamEntity);
//
//		return $teamEntity;
//	}

	public function dropAll() {
		$this->teamMapper->emptyTable();
	}
}
