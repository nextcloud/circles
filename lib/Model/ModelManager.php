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


use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Exceptions\MemberNotFoundException;

/**
 * Class ModelManager
 *
 * @package OCA\Circles\Model
 */
class ModelManager {


	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;


	public function __construct(DeprecatedCirclesRequest $circlesRequest) {
		$this->circlesRequest = $circlesRequest;
	}


	public function getMembers(Circle $circle): void {
		if (empty($circle->getMembers())) {
			$circle->setMembers(['oui' => 1]);
		}
	}


	/**
	 * @param Circle $circle
	 * @param array $data
	 */
	public function importOwnerFromDatabase(Circle $circle, array $data) {
		try {
			$owner = new Member();
			$owner->importFromDatabase($data, 'owner_');
			$circle->setOwner($owner);
		} catch (MemberNotFoundException $e) {
		}
	}

}

