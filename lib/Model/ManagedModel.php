<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCA\Circles\Model;

use OC;
use OCA\Circles\IFederatedUser;

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
			$this->modelManager = OC::$server->get(ModelManager::class);
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
