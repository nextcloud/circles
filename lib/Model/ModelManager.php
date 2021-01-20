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


use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Service\ConfigService;

/**
 * Class ModelManager
 *
 * @package OCA\Circles\Model
 */
class ModelManager {


	const TYPES_SHORT = 1;
	const TYPES_LONG = 2;


	/** @var ConfigService */
	private $configService;


	public function __construct(ConfigService $configService) {
		$this->configService = $configService;
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
	public function importOwnerFromDatabase(Circle $circle, array $data): void {
		try {
			$owner = new Member();
			$owner->importFromDatabase($data, 'owner_');
			if ($owner->getInstance() === '') {
				$owner->setInstance($this->configService->getLocalInstance());
			}
			$circle->setOwner($owner);
		} catch (MemberNotFoundException $e) {
		}
	}


	/**
	 * @param Circle $circle
	 * @param int $display
	 *
	 * @return array
	 */
	public function getCircleTypes(Circle $circle, int $display = self::TYPES_LONG): array {
		$types = [];
		foreach (array_keys(Circle::$DEF) as $def) {
			if ($circle->isConfig($def)) {
				list($short, $long) = explode('|', Circle::$DEF[$def]);
				switch ($display) {

					case self::TYPES_SHORT:
						$types[] = $short;
						break;

					case self::TYPES_LONG:
						$types[] = $long;
						break;
				}
			}
		}

		return $types;
	}


}

