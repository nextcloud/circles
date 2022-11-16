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

namespace OCA\Circles\Mount;

use OC\Files\Cache\Wrapper\CacheWrapper;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;

class RootEntryCache extends CacheWrapper {
	private ?ICacheEntry $rootEntry;

	public function __construct(
		ICache $cache,
		?ICacheEntry $rootEntry = null
	) {
		parent::__construct($cache);
		$this->rootEntry = $rootEntry;
	}

	/**
	 * @inheritdoc
	 */
	public function get($file) {
		if ($file === '' && $this->rootEntry) {
			return $this->rootEntry;
		}

		return parent::get($file);
	}

	/**
	 * @inheritdoc
	 */
	public function insert($file, array $data): int {
		$this->rootEntry = null;

		return parent::insert($file, $data);
	}

	/**
	 * @inheritdoc
	 */
	public function update($id, array $data): void {
		$this->rootEntry = null;
		parent::update($id, $data);
	}

	/**
	 * @inheritdoc
	 */
	public function getId($file): int {
		if ($file === '' && $this->rootEntry) {
			return $this->rootEntry->getId();
		}

		return parent::getId($file);
	}
}
