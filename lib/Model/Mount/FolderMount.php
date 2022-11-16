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

namespace OCA\Circles\Model\Mount;

use OC\Files\Cache\CacheEntry;

class FolderMount extends Mount {
	private int $permissions = 0;
	private ?CacheEntry $cacheEntry = null;

	public function __construct(string $circleId = '') {
		parent::__construct($circleId);
	}

	public function setPermissions(int $permissions): self {
		$this->permissions = $permissions;

		return $this;
	}

	public function getPermissions(): int {
		return $this->permissions;
	}

	public function setCacheEntry(?CacheEntry $cacheEntry): self {
		$this->cacheEntry = $cacheEntry;

		return $this;
	}

	public function getCacheEntry(): ?CacheEntry {
		return $this->cacheEntry;
	}

	public function hasCacheEntry(): bool {
		return !is_null($this->cacheEntry);
	}

	public function toMount(): array {
		return array_merge(
			[],
			parent::toMount()
		);
	}
}
