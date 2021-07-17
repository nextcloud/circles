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


namespace OCA\Circles\Model\Probes;


use OCA\Circles\Model\Circle;


/**
 * Class CircleProbe
 *
 * @package OCA\Circles\Model\Probes
 */
class CircleProbe extends MemberProbe {


	/** @var array */
	static $filters = [
		Circle::CFG_SINGLE,
		Circle::CFG_HIDDEN,
		Circle::CFG_BACKEND,
	];

	/** @var int */
	private $include = 0;


	/**
	 * @param bool $include
	 *
	 * @return $this
	 */
	public function includePersonalCircles(bool $include = true): self {
		$this->include |= Circle::CFG_PERSONAL;
		if (!$include) {
			$this->include -= Circle::CFG_PERSONAL;
		}

		return $this;
	}

	/**
	 * @param bool $include
	 *
	 * @return $this
	 */
	public function includeSingleCircles(bool $include = true): self {
		$this->include |= Circle::CFG_SINGLE;
		if (!$include) {
			$this->include -= Circle::CFG_SINGLE;
		}

		return $this;
	}

	/**
	 * @param bool $include
	 *
	 * @return $this
	 */
	public function includeSystemCircles(bool $include = true): self {
		$this->include |= Circle::CFG_SYSTEM;
		if (!$include) {
			$this->include -= Circle::CFG_SYSTEM;
		}

		return $this;
	}

	/**
	 * @param bool $include
	 *
	 * @return $this
	 */
	public function includeHiddenCircles(bool $include = true): self {
		$this->include |= Circle::CFG_HIDDEN;
		if (!$include) {
			$this->include -= Circle::CFG_HIDDEN;
		}

		return $this;
	}

	/**
	 * @param bool $include
	 *
	 * @return $this
	 */
	public function includeBackendCircles(bool $include = true): self {
		$this->include |= Circle::CFG_BACKEND;
		if (!$include) {
			$this->include -= Circle::CFG_BACKEND;
		}

		return $this;
	}


	/**
	 * @return int
	 */
	public function included(): int {
		return $this->include;
	}

	/**
	 * @param int $config
	 *
	 * @return bool
	 */
	public function isIncluded(int $config): bool {
		return (($this->included() & $config) !== 0);
	}

	/**
	 * @return int
	 */
	public function filtered(): int {
		$filtered = 0;
		foreach (self::$filters as $filter) {
			if ($this->isIncluded($filter)) {
				continue;
			}
			$filtered += $filter;
		}

		return $filtered;
	}

	/**
	 * @return array
	 */
	public function getAsOptions(): array {
		return array_merge(
			[
				'included' => $this->included(),
				'includeHiddenCircles' => $this->isIncluded(Circle::CFG_HIDDEN),
				'includeBackendCircles' => $this->isIncluded(Circle::CFG_BACKEND),
				'includeSystemCircles' => $this->isIncluded(Circle::CFG_SYSTEM),
				'includePersonalCircles' => $this->isIncluded(Circle::CFG_PERSONAL),
			],
			parent::getAsOptions()
		);
	}


	/**
	 * @return array
	 */
	public function JsonSerialize(): array {
		return $this->getAsOptions();
	}
}
