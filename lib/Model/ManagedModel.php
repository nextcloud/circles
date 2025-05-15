<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Model;

use OCA\Circles\IFederatedUser;
use OCP\Server;

/**
 * Class ManagedModel
 *
 * @package OCA\Circles\Model
 */
class ManagedModel {
	public const ID_LENGTH = 31;


	/** @var ModelManager */
	private $modelManager;


	/**
	 * @return ModelManager
	 */
	protected function getManager(): ModelManager {
		if ($this->modelManager === null) {
			$this->modelManager = Server::get(ModelManager::class);
		}

		return $this->modelManager;
	}


	/** @noinspection PhpPossiblePolymorphicInvocationInspection */
	public function importFromIFederatedUser(IFederatedUser $orig): void {
		if (!($this instanceof IFederatedUser)) {
			return;
		}

		// TODO : move those methods to this class ?
		$this->setSingleId($orig->getSingleId());
		$this->setUserId($orig->getUserId());
		$this->setUserType($orig->getUserType());
		$this->setDisplayName($orig->getDisplayName());

		if ($orig->hasBasedOn()) {
			$this->setBasedOn($orig->getBasedOn());
		}

		$this->setInstance($orig->getInstance());
	}
}
