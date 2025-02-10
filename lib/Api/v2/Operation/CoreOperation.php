<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use OCA\Circles\Api\v2\ITeamEntityOperation;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Service\TeamEntityService;
use OCP\IUser;

class CoreOperation {
	protected ?TeamEntity $entity = null;

	final public function asEntity(TeamEntity $entity): void {
		if ($this->entity !== null) {
			throw new \Exception('cannot overwrite initiator'); // TODO specific Exception
		}
		$this->entity = $entity;
	}

	final public function confirmEntityInitialized(): void {
		if (null === $this->entity) {
			throw new \Exception('entity not initialized'); // TODO specific Exception
		}
	}

}
