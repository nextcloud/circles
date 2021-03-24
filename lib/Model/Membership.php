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

use daita\MySmallPhpTools\Db\Nextcloud\nc22\INC22QueryRow;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;


/**
 * Class Membership
 *
 * @package OCA\Circles\Model
 */
class Membership extends ManagedModel implements INC22QueryRow, JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $singleId = '';

	/** @var string */
	private $circleId = '';

	/** @var string */
	private $memberId = '';

	/** @var string */
	private $parent = '';

	/** @var int */
	private $level = 0;


	/**
	 * Membership constructor.
	 *
	 * @param Member|null $member
	 * @param string $singleId
	 */
	public function __construct(?Member $member = null, string $singleId = '') {
		if (is_null($member)) {
			return;
		}

		$this->setSingleId(($singleId === '') ? $member->getSingleId() : $singleId);
		$this->setCircleId($member->getCircleId());
		$this->setMemberId($member->getId());
		$this->setParent($member->getSingleId());
		$this->setLevel($member->getLevel());
	}


	/**
	 * @param string $id
	 * @param string $circleId
	 * @param string $memberId
	 * @param int $level
	 *
	 * @param string $parent
	 *
	 * @return $this
	 */
	public function set(
		string $id = '',
		string $circleId = '',
		string $memberId = '',
		int $level = 0,
		string $parent = ''
	): self {
		$this->singleId = $id;
		$this->circleId = $circleId;
		$this->memberId = $memberId;
		$this->level = $level;
		$this->parent = $parent;
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
	 * @param string $memberId
	 *
	 * @return Membership
	 */
	public function setMemberId(string $memberId): self {
		$this->memberId = $memberId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMemberId(): string {
		return $this->memberId;
	}


	/**
	 * @param string $parent
	 *
	 * @return Membership
	 */
	public function setParent(string $parent): self {
		$this->parent = $parent;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getParent(): string {
		return $this->parent;
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
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'singleId' => $this->getSingleId(),
			'circleId' => $this->getCircleId(),
			'memberId' => $this->getMemberId(),
			'level'    => $this->getLevel(),
			'parent'   => $this->getParent(),
		];
	}


	/**
	 * @param array $data
	 *
	 * @return INC22QueryRow
	 */
	public function importFromDatabase(array $data): INC22QueryRow {
		$this->setSingleId($this->get('single_id', $data));
		$this->setCircleId($this->get('circle_id', $data));
		$this->setMemberId($this->get('member_id', $data));
		$this->setLevel($this->getInt('level', $data));
		$this->setParent($this->get('parent', $data));

		return $this;
	}

}

