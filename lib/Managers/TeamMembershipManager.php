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

use OCA\Circles\Db\TeamEntityMapper;
use OCA\Circles\Db\TeamMemberMapper;
use OCA\Circles\Db\TeamMembershipMapper;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;
use OCA\Circles\Model\TeamMembership;
use OCA\Circles\Service\ToolsService;

class TeamMembershipManager {
	public function __construct(
		private readonly ToolsService $toolsService,
		private readonly TeamMembershipMapper $teamMembershipMapper,
	) {
	}

	/**
	 * @return TeamMembership[]
	 */
	public function getMemberships(string $singleId): array {
		return $this->teamMembershipMapper->getMembershipsRelatedToEntity($singleId);
	}

	public function insertMembership(TeamMembership $teamMembership): void {
		$this->teamMembershipMapper->insert($teamMembership);
	}

	public function overwriteMembership(int $id, TeamMembership $teamMembership): void {
		$teamMembership->setId($id);
		$this->teamMembershipMapper->update($teamMembership);
	}

	public function removeMembership(TeamMembership $teamMembership): void {
		$this->teamMembershipMapper->removeMembership($teamMembership->getSingleId(), $teamMembership->getTeamSingleId());
	}

	public function removeSingleId(string $singleId): void {
		//$this->teamMembershipMapper->remove
	}

	public function dropAll() {
		$this->teamMembershipMapper->emptyTable();
	}


}
