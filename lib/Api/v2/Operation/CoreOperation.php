<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use OCA\Circles\Api\v2\TeamSession;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Model\TeamEntity;

class CoreOperation {
//	private ?TeamEntity $entity = null;
	private ?TeamSession $session = null;

	final public function fromSession(TeamSession $session): void {
		if ($this->session !== null) {
			throw new \Exception('cannot overwrite session'); // TODO specific Exception
		}
		if (!$session->hasEntity()) {
			throw new \Exception('entity must be set'); // TODO: specific exception
		}
		$this->session = $session;
	}

	final public function getSession(): TeamSession {
		return $this->session;
	}

	final public function hasEntity(): bool {
		return $this->session->hasEntity();
	}

	final public function getEntity(): TeamEntity {
		return $this->session->getEntity();
	}
//
//
//	final public function asEntity(TeamEntity $entity): void {
//		if ($this->entity !== null) {
//			throw new \Exception('cannot overwrite initiator'); // TODO specific Exception
//		}
//		$this->entity = $entity;
//	}

	/**
	 * @param TeamEntityType|TeamEntityType[] $limitToEntities
	 */
	final public function confirmSessionInitialized(TeamEntityType|array $limitToEntities = []): void {
		if ($this->session === null) {
			throw new \Exception('session/entity not initialized'); // TODO specific Exception
		}

		if (!empty($limitToEntities)
			&& !in_array($this->getEntity()->getTeamEntityType(), $limitToEntities)) {
			throw new \Exception('entity cannot execute this operation'); // TODO specific Exception
		}

	}

	/**
	 * @param TeamEntityType|TeamEntityType[] $limitToEntities
	 */
	final protected function lowPriorityProcess(TeamEntityType|array $limitToEntities = []): void {
		$this->confirmSessionInitialized($limitToEntities);
		// TODO: detect type of process and filters it
//		if (!in_array(
//			$this->entity->getTeamEntityType(),
//			TeamEntityType::OCC,
//			TeamEntityType::CRON,
//			TeamEntityType::ASYNC,
//		)) {
//			throw new \Exception('cannot be executed on main process');
//		}
	}

}
