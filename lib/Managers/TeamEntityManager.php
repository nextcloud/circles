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
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Service\ToolsService;

class TeamEntityManager {
	/** @var TeamEntity[] */
	private array $entities = [];

	public function __construct(
		private readonly ToolsService $toolsService,
		private readonly TeamEntityMapper $teamEntityMapper,
	) {
	}

	/**
	 * @throws TeamEntityNotFoundException
	 */
	public function getTeamEntity(string $singleId): TeamEntity {
		if (!array_key_exists($singleId, $this->entities)) {
			$this->entities[$singleId] = $this->teamEntityMapper->getBySingleId($singleId);
		}

		return $this->entities[$singleId];
	}

	/**
	 * @throws TeamEntityNotFoundException
	 */
	public function searchTeamEntity(TeamEntityType $type, string $origId): TeamEntity {
		return $this->teamEntityMapper->getByOrigId($type, $origId);
	}

	public function createTeamEntity(
		TeamEntityType $type,
		string $origId,
		string $displayName,
		?string $singleId = null
	): TeamEntity {
		$teamEntity = new TeamEntity();
		$teamEntity->setSingleId($singleId ?? $this->toolsService->generateSingleId());
		$teamEntity->setTeamEntityType($type);
		$teamEntity->setOrigId($origId);
		$teamEntity->setDisplayName($displayName);
		$this->teamEntityMapper->insert($teamEntity);

		return $teamEntity;
	}

	public function save(TeamEntity $teamEntity): void {
		// TODO: do not store teamentity if type === TEAM
	}

	public function dropAll() {
		$this->teamEntityMapper->emptyTable();
	}
}
