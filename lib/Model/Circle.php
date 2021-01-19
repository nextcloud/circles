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

use daita\MySmallPhpTools\Db\Nextcloud\nc21\INC21QueryRow;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;


/**
 * Class Circle
 *
 * @package OCA\Circles\Model
 */
class Circle extends ManagedModel implements INC21QueryRow, JsonSerializable {


	use TArrayTools;


	const TYPE_SINGLE = 1;        // Single member
	const TYPE_PERSONAL = 2;      // Personal circle, only owner can see it
	const TYPE_VISIBLE = 4;       // Visible to everyone, if not visible, people have to know its name to be able to join it
	const TYPE_LOCKED = 8;        // Invite only
	const TYPE_FAST_INVITE = 16;  // Fast invite, no need confirmation
	const TYPE_REQUEST = 32;      // Request need to be confirmed by moderator
	const TYPE_PROTECTED = 64;    // Password protected to join/request
	const TYPE_HIDDEN = 128;      // Fully hidden, only backend Circles
	const TYPE_FEDERATED = 256;   // Federated

	static $DEF = [
		1   => 'Single',
		2   => 'Personal',
		4   => 'Visible',
		8   => 'Locked',
		16  => 'Fast Invite',
		32  => 'Request',
		64  => 'Protected',
		128 => '',
		256 => 'Federated'
	];


	/** @var string */
	private $id = '';

	/** @var int */
	private $type = 0;

	/** @var string */
	private $name = '';

	/** @var string */
	private $altName = '';

	/** @var array */
	private $members = [];

	/** @var Member */
	private $owner;


	public function __construct() {
	}

	/**
	 * @param string $id
	 *
	 * @return Circle
	 */
	public function setId(string $id): self {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}


	/**
	 * @param int $type
	 *
	 * @return Circle
	 */
	public function setType(int $type): self {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getType(): int {
		return $this->type;
	}

	/**
	 * @param int $type
	 *
	 * @return bool
	 */
	public function isType(int $type): bool {
		return (($this->getType() & $type) === 1);
	}


	/**
	 * @param string $name
	 *
	 * @return Circle
	 */
	public function setName(string $name): self {
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}


	/**
	 * @param string $altName
	 *
	 * @return Circle
	 */
	public function setAltName(string $altName): self {
		$this->altName = $altName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAltName(): string {
		return $this->altName;
	}


	/**
	 * @param Member $owner
	 */
	public function setOwner(Member $owner): void {
		$this->owner = $owner;
	}

	/**
	 * @return Member
	 */
	public function getOwner(): Member {
		return $this->owner;
	}

	/**
	 * @return bool
	 */
	public function hasOwner(): bool {
		return ($this->owner !== null);
	}


	/**
	 * @param array $members
	 *
	 * @return Circle
	 */
	public function setMembers(array $members): self {
		$this->members = $members;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getMembers(): array {
		$this->getManager()->getMembers($this);

		return $this->members;
	}


	/**
	 * @return $this
	 */
	public function import(): self {
		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$arr = [
			'id'       => $this->getId(),
			'name'     => $this->getName(),
			'alt_name' => $this->getAltName(),

		];

		if ($this->hasOwner()) {
			$arr['owner'] = $this->getOwner();
		}

		return $arr;
	}


	/**
	 * @param array $data
	 *
	 * @return INC21QueryRow
	 */
	public function importFromDatabase(array $data): INC21QueryRow {
		$this->setId($this->get('unique_id', $data))
			 ->setName($this->get('name', $data))
			 ->setAltName($this->get('alt_name', $data));

		$this->getManager()->importOwnerFromDatabase($this, $data);

		return $this;
	}

}
