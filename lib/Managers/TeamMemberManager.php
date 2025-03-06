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

use OCA\Circles\Api\v2\TeamSession;
use OCA\Circles\Db\TeamEntityMapper;
use OCA\Circles\Db\TeamMemberMapper;
use OCA\Circles\Db\TeamMembershipMapper;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Enum\TeamMemberLevel;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Exceptions\TeamEntityOverwritePermissionException;
use OCA\Circles\Exceptions\TeamMembershipNotFoundException;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;
use OCA\Circles\Model\TeamMembership;
use OCA\Circles\Service\ToolsService;

class TeamMemberManager extends CoreManager {
	public function __construct(
		private readonly ToolsService $toolsService,
		private readonly TeamMemberMapper $teamMemberMapper,
		private readonly TeamMembershipMapper $teamMembershipMapper,
	) {
	}

	public function getTeamMember(TeamSession|TeamEntity $initiator, string $teamSingleId, string $memberSingleId): TeamMember {
	//	return $this->teamMemberMapper->getByTeamAndMember($teamSingleId, $memberSingleId);
	}


	public function addMember(TeamSession|TeamEntity $initiator, string $teamSingleId, string $singleId): TeamMember {
		try {
			$initiatorMembership = $this->getInitiatorMembership($initiator, $teamSingleId);
			if ($initiatorMembership->getLevel() < TeamMemberLevel::MODERATOR->value) {
				throw new \Exception('not enough permissions'); // TODO: real exception
			}
		} catch (TeamEntityOverwritePermissionException) {
		}

		$teamMember = new TeamMember();
		$teamMember->setTeamSingleId($teamSingleId);
		$teamMember->setMemberSingleId($singleId);
		$teamMember->setTeamMemberLevel(TeamMemberLevel::MEMBER);
		$teamMember->setInvitedBy($this->extractTeamEntity($initiator));
		$this->teamMemberMapper->insert($teamMember);

		return $teamMember;
	}

	/**
	 * @return TeamMember[]
	 */
	public function getMembersFromTeam(TeamSession|TeamEntity $initiator, string $teamSingleId): array {
		return $this->teamMemberMapper->getTeamMembers(
			$this->filterInitiator($initiator),
			$teamSingleId
		);
	}

	/**
	 * @return TeamMember[]
	 */
	public function getTeamsContainingEntity(TeamSession|TeamEntity $initiator, $singleId) {
		return $this->teamMemberMapper->getEntityTeams(
			$this->filterInitiator($initiator),
			$singleId
		);
	}

	public function dropAll() {
		$this->teamMemberMapper->emptyTable();
	}
}
