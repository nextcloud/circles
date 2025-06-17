<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Managers;

use OCA\Circles\Api\v2\TeamSession;
use OCA\Circles\Db\TeamMembershipMapper;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityOverwritePermissionException;
use OCA\Circles\Exceptions\TeamMembershipNotFoundException;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMembership;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CoreManager {
	public function __construct(
	) {
	}

	final protected function extractTeamEntity(TeamSession|TeamEntity $initiator): TeamEntity {
		if ($initiator instanceof TeamSession) {
			return $initiator->getEntity();
		}

		return $initiator;
	}

	/**
	 * returns NULL if initiator have super permissions (occ, super_admin)
	 */
	final protected function filterInitiator(TeamSession|TeamEntity $initiator): ?TeamEntity {
		$initiator = $this->extractTeamEntity($initiator);

		if (in_array($initiator->getTeamEntityType(), [
			TeamEntityType::OCC,
			TeamEntityType::SUPER_ADMIN,
		], true)) {
			return null;
		}

		return $initiator;
	}

	/**
	 * @throws TeamEntityOverwritePermissionException
	 * @throws TeamMembershipNotFoundException
	 */
	final protected function getInitiatorMembership(TeamEntity|TeamSession $initiator, string $teamSingleId): TeamMembership {
		$initiatorEntity = $this->filterInitiator($initiator);
		if ($initiatorEntity === null) {
			throw new TeamEntityOverwritePermissionException('initiator is above everyone, bypassing membership check');
		}

		return Server::get(TeamMembershipMapper::class)->getByTeamAndEntity($teamSingleId, $initiatorEntity->getSingleId());
	}

}
