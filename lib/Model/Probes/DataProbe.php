<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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


namespace OCA\Circles\Model\Probes;

use OCA\Circles\Db\CoreQueryBuilder;

/**
 * Class CircleProbe
 *
 * @package OCA\Circles\Model\Probes
 */
class DataProbe extends BasicProbe {
	public const OWNER = CoreQueryBuilder::OWNER;
	public const MEMBER = CoreQueryBuilder::MEMBER;
	public const BASED_ON = CoreQueryBuilder::BASED_ON;
	public const MEMBERSHIPS = CoreQueryBuilder::MEMBERSHIPS;
	public const CONFIG = CoreQueryBuilder::CONFIG;
	public const INITIATOR = CoreQueryBuilder::INITIATOR;
	public const INHERITED_BY = CoreQueryBuilder::INHERITED_BY;


	private array $path = [];


	public function __construct() {
	}


	/**
	 * @param string $key
	 * @param array $path
	 *
	 * @return $this
	 */
	public function add(string $key, array $path = []): self {
		$this->path[$key] = $path;

		return $this;
	}


	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has(string $key): bool {
		return (array_key_exists($key, $this->path));
	}


	/**
	 * @return array
	 */
	public function getPath(): array {
		return $this->path;
	}


	/**
	 * Return an array with includes as options
	 *
	 * @return array
	 */
	public function getAsOptions(): array {
		return array_merge(
			[
				'path' => $this->getPath()
			],
			parent::getAsOptions()
		);
	}

	/**
	 * Return a JSON object with includes as options
	 *
	 * @return array
	 */
	public function JsonSerialize(): array {
		return $this->getAsOptions();
	}
}
