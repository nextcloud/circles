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
use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\INC21Convert;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Convert;
use daita\MySmallPhpTools\Traits\TArrayTools;
use DateTime;
use JsonSerializable;


/**
 * Class Circle
 *
 * @package OCA\Circles\Model
 */
class Circle extends ManagedModel implements INC21Convert, INC21QueryRow, JsonSerializable {


	use TArrayTools;
	use TNC21Convert;


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
		1   => '*S|Single',
		2   => 'PU|Personal Use',
		4   => 'V|Visible',
		8   => 'IO|Invite Only',
		16  => 'FI|Fast Invite',
		32  => 'JR|Join Request',
		64  => 'PP|Password Protected',
		128 => '*H|Hidden',
		256 => 'F|Federated'
	];


	/** @var string */
	private $id = '';

	/** @var int */
	private $type = 0;

	/** @var string */
	private $name = '';

	/** @var string */
	private $altName = '';

	/** @var Member */
	private $owner;

	/** @var array */
	private $members = [];

	/** @var array */
	private $settings = [];

	/** @var string */
	private $description = '';

	/** @var string */
	private $contactAddressBook = '';

	/** @var string */
	private $contactGroupName = '';

	/** @var bool */
	private $hidden = false;

	/** @var int */
	private $creation = 0;


	public function __construct() {
	}

	/**
	 * @param string $id
	 *
	 * @return self
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
	 * @return self
	 */
	public function setType(int $type): self {
		$this->type = $type;

		$this->hidden = false;
		foreach (array_keys(self::$DEF) as $def) {
			if ($this->isType($def) && substr(self::$DEF[$def], 0, 1) === '*') {
				$this->hidden = true;
				break;
			}
		}

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
		return (($this->getType() & $type) !== 0);
	}


	/**
	 * @param string $name
	 *
	 * @return self
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
	 * @return self
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
	 * @return self
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
	 * @param array $settings
	 *
	 * @return self
	 */
	public function setSettings(array $settings): self {
		$this->settings = $settings;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getSettings(): array {
		return $this->settings;
	}


	/**
	 * @param string $description
	 *
	 * @return self
	 */
	public function setDescription(string $description): self {
		$this->description = $description;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string {
		return $this->description;
	}


	/**
	 * @param string $contactAddressBook
	 *
	 * @return self
	 */
	public function setContactAddressBook(string $contactAddressBook): self {
		$this->contactAddressBook = $contactAddressBook;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContactAddressBook(): string {
		return $this->contactAddressBook;
	}


	/**
	 * @param string $contactGroupName
	 *
	 * @return self
	 */
	public function setContactGroupName(string $contactGroupName): self {
		$this->contactGroupName = $contactGroupName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContactGroupName(): string {
		return $this->contactGroupName;
	}


	/**
	 * @param bool $hidden
	 *
	 * @return Circle
	 */
	public function setHidden(bool $hidden): self {
		$this->hidden = $hidden;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isHidden(): bool {
		return $this->hidden;
	}


	/**
	 * @param int $creation
	 *
	 * @return self
	 */
	public function setCreation(int $creation): self {
		$this->creation = $creation;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCreation(): int {
		return $this->creation;
	}


	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function import(array $data): INC21Convert {
		$this->setId($this->get('id', $data))
			 ->setName($this->get('name', $data))
			 ->setAltName($this->get('alt_name', $data))
			 ->setType($this->getInt('type', $data))
			 ->setSettings($this->getArray('settings', $data))
//			 ->setContactAddressBook($this->get('contact_addressbook', $data))
//			 ->setContactGroupName($this->get('contact_groupname', $data))
			 ->setDescription($this->get('description', $data))
			 ->setCreation($this->getInt('creation', $data));

		try {
			/** @var Member $owner */
			$owner = $this->convert($this->getArray('owner', $data), Member::class);
			$this->setOwner($owner);
		} catch (InvalidItemException $e) {
		}

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$arr = [
			'id'          => $this->getId(),
			'name'        => $this->getName(),
			'alt_name'    => $this->getAltName(),
			'type'        => $this->getType(),
			'description' => $this->getDescription(),
			'settings'    => $this->getSettings(),
			'hidden'      => $this->isHidden(),
			'creation'    => $this->getCreation()
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
			 ->setAltName($this->get('alt_name', $data))
			 ->setType($this->getInt('type', $data))
			 ->setSettings($this->getArray('settings', $data))
			 ->setContactAddressBook($this->get('contact_addressbook', $data))
			 ->setContactGroupName($this->get('contact_groupname', $data))
			 ->setDescription($this->get('description', $data));

		$creation = $this->get('creation', $data);
		$this->setCreation(DateTime::createFromFormat('Y-m-d H:i:s', $creation)->getTimestamp());

		$this->getManager()->importOwnerFromDatabase($this, $data);

		return $this;
	}

}
