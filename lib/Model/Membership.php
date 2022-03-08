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

use JsonSerializable;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;

/**
 * Class Membership
 *
 * @package OCA\Circles\Model
 */
class Membership extends ManagedModel implements IDeserializable, IQueryRow, JsonSerializable {
	use TArrayTools;


	/** @var string */
	private $singleId = '';

	/** @var string */
	private $circleId = '';

	/** @var int */
	private $circleConfig = 0;

	/** @var int */
	private $level = 0;

	/** @var string */
	private $inheritanceFirst = '';

	/** @var string */
	private $inheritanceLast = '';

	/** @var array */
	private $inheritancePath = [];

	/** @var int */
	private $inheritanceDepth = 0;

	/** @var array */
	private $inheritanceDetails = [];


	/**
	 * Membership constructor.
	 *
	 * @param string $singleId
	 * @param Member|null $member
	 * @param string $inheritanceLast
	 */
	public function __construct(
		string $singleId = '',
		string $inheritanceLast = '',
		?Member $member = null
	) {
		if (is_null($member)) {
			return;
		}

		$circle = $member->getCircle();
		$this->setSingleId($singleId);
		$this->setCircleId($circle->getSingleId());
		$this->setInheritanceFirst($member->getSingleId());
		$this->setInheritanceLast($inheritanceLast ?: $member->getCircleId());
		$this->setLevel($member->getLevel());
	}


	/**
	 * @param string $singleId
	 *
	 * @return self
	 */
	public function setSingleId(string $singleId): self {
		$this->singleId = $singleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSingleId(): string {
		return $this->singleId;
	}


	/**
	 * @param string $circleId
	 *
	 * @return Membership
	 */
	public function setCircleId(string $circleId): self {
		$this->circleId = $circleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCircleId(): string {
		return $this->circleId;
	}


	/**
	 * @param int $circleConfig
	 *
	 * @return Membership
	 */
	public function setCircleConfig(int $circleConfig): self {
		$this->circleConfig = $circleConfig;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCircleConfig(): int {
		return $this->circleConfig;
	}


	/**
	 * @param int $level
	 *
	 * @return Membership
	 */
	public function setLevel(int $level): self {
		$this->level = $level;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLevel(): int {
		return $this->level;
	}


	/**
	 * @param string $inheritanceFirst
	 *
	 * @return Membership
	 */
	public function setInheritanceFirst(string $inheritanceFirst): self {
		$this->inheritanceFirst = $inheritanceFirst;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInheritanceFirst(): string {
		return $this->inheritanceFirst;
	}


	/**
	 * @param string $inheritanceLast
	 *
	 * @return Membership
	 */
	public function setInheritanceLast(string $inheritanceLast): self {
		$this->inheritanceLast = $inheritanceLast;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInheritanceLast(): string {
		return $this->inheritanceLast;
	}


	/**
	 * @param array $inheritancePath
	 *
	 * @return Membership
	 */
	public function setInheritancePath(array $inheritancePath): self {
		$this->inheritancePath = $inheritancePath;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getInheritancePath(): array {
		return $this->inheritancePath;
	}


	/**
	 * @param int $inheritanceDepth
	 *
	 * @return Membership
	 */
	public function setInheritanceDepth(int $inheritanceDepth): self {
		$this->inheritanceDepth = $inheritanceDepth;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getInheritanceDepth(): int {
		return $this->inheritanceDepth;
	}


	/**
	 * @param array $inheritanceDetails
	 *
	 * @return Membership
	 */
	public function setInheritanceDetails(array $inheritanceDetails): self {
		$this->inheritanceDetails = $inheritanceDetails;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getInheritanceDetails(): array {
		return $this->inheritanceDetails;
	}


	/**
	 * @param array $data
	 *
	 * @return IDeserializable
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		if ($this->get('singleId', $data) === '') {
			throw new InvalidItemException();
		}

		$this->setSingleId($this->get('singleId', $data));
		$this->setCircleId($this->get('circleId', $data));
		$this->setCircleConfig($this->getInt('circleConfig', $data));
		$this->setLevel($this->getInt('level', $data));
		$this->setInheritanceFirst($this->get('inheritanceFirst', $data));
		$this->setInheritanceLast($this->get('inheritanceLast', $data));
		$this->setInheritancePath($this->getArray('inheritancePath', $data));
		$this->setInheritanceDepth($this->getInt('inheritanceDepth', $data));

		return $this;
	}

	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IQueryRow
	 * @throws MembershipNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if ($this->get($prefix . 'single_id', $data) === '') {
			throw new MembershipNotFoundException();
		}

		$this->setSingleId($this->get($prefix . 'single_id', $data));
		$this->setCircleId($this->get($prefix . 'circle_id', $data));
		$this->setLevel($this->getInt($prefix . 'level', $data));
		$this->setCircleConfig($this->getInt($prefix . 'circle_config', $data));
		$this->setInheritanceFirst($this->get($prefix . 'inheritance_first', $data));
		$this->setInheritanceLast($this->get($prefix . 'inheritance_last', $data));
		$this->setInheritancePath($this->getArray($prefix . 'inheritance_path', $data));
		$this->setInheritanceDepth($this->getInt($prefix . 'inheritance_depth', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$result = [
			'singleId' => $this->getSingleId(),
			'circleId' => $this->getCircleId(),
			'circleConfig' => $this->getCircleConfig(),
			'level' => $this->getLevel(),
			'inheritanceFirst' => $this->getInheritanceFirst(),
			'inheritanceLast' => $this->getInheritanceLast(),
			'inheritancePath' => $this->getInheritancePath(),
			'inheritanceDepth' => $this->getInheritanceDepth()
		];

		if (!empty($this->getInheritanceDetails())) {
			$result['inheritanceDetails'] = $this->getInheritanceDetails();
		}

		return $result;
	}
}
