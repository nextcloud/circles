<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\Db\TeamMapper;
use OCA\Circles\Db\TeamMemberMapper;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Enum\TeamMemberLevel;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Managers\TeamEntityManager;
use OCA\Circles\Model\Team;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;

class TeamService {
	public function __construct(
		private readonly ToolsService $toolsService,
		private TeamMapper $teamMapper,
		private TeamMemberMapper $teamMemberMapper,
		private TeamEntityManager $teamEntityManager,
	) {
	}

	/**
	 * @throws TeamEntityNotFoundException
	 */
	public function create(TeamEntity $owner, string $name, ?TeamEntity $realOwner = null): Team {
		$teamSingleId = $this->toolsService->generateSingleId();

		// todo: check team_single_id does not exist in oc_teams not oc_teams_members

//		$owner = new TeamMember();
//		$owner->setTeamSingleId($teamSingleId);
//		$owner->setMemberSingleId($ownerSingleId);
//		$owner->setTeamMemberLevel(TeamMemberLevel::OWNER);
//		$owner->setCreation(time());
//		$this->teamMemberMapper->insert($owner);

//		$owner = $this->teamEntityManager->getTeamEntity($ownerSingleId);
//
//		$owner = new TeamEntity();
//		$owner->setTeamEntityType(TeamEntityType::LOCAL_USER);
//		$owner->setDisplayName('test_test_owner');
//		$owner->setSingleId('asde');
//		$owner->setOrigId('admin');
//		$owner->enableLazyLoading();
//		$realOwner?->enableLazyLoading();

		$team = new Team();
		$team->enableLazyloading($owner);
		$team->setSingleId($teamSingleId);
		$team->setDisplayName($name);
		$team->setSanitizedName($this->toolsService->generateSingleId(6)); // TODO
		$team->setOwner($realOwner ?? $owner);
		$this->teamMapper->insert($team);

		$member = new TeamMember();
		$member->setInvitedBy($owner);
		$member->setTeamMemberLevel(TeamMemberLevel::OWNER);
		$member->setTeam($team);
		$member->setEntity($team->getOwner());
		$this->teamMemberMapper->insert($member);

		$this->teamEntityManager->createTeamEntity(
			TeamEntityType::TEAM,
			$team->getSingleId(),
			$team->getDisplayName(),
			$team->getSingleId()
		);

//		$team->setMembers([$member]);

//		$mem

		return $team;
	}

	public function getTeam(TeamEntity $initiator, string $teamSingleId): Team {
		$this->teamMapper->getBySingleId($teamSingleId);
	}

	private function generateMetadata(Team $team): void {
	}
}
