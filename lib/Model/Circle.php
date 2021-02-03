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
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;


/**
 * Class Circle
 *
 * ** examples of use of bitwise flags for members management:
 *      CFG_OPEN, CFG_REQUEST, CFG_INVITE, CFG_FRIEND
 *
 * - CFG_OPEN                             => everyone can enter. moderator can add members.
 * - CFG_OPEN | CFG_REQUEST               => anyone can initiate a request to join the circle, moderator can
 *                                           add members
 * - CFG_OPEN | CFG_INVITE                => every one can enter, moderator must send invitation.
 * - CFG_OPEN | CFG_INVITE | CFG_REQUEST  => every one send a request, moderator must send invitation.
 * - CFG_OPEN | CFG_FRIEND                => useless
 * - CFG_OPEN | CFG_FRIEND | *            => useless
 *
 * - CFG_CIRCLE                           => no one can enter, moderator can add members.
 *                                           default config, this is only for code readability.
 * - CFG_INVITE                           => no one can enter, moderator must send invitation.
 * - CFG_FRIEND                           => no one can enter, but all members can add new member.
 * - CFG_REQUEST                          => useless (use CFG_OPEN | CFG_REQUEST)
 * - CFG_FRIEND | CFG_REQUEST             => no one can join the circle, but all members can request a
 *                                           moderator to accept new member
 * - CFG_FRIEND | CFG_INVITE              => no one can join the circle, but all members can add new member.
 *                                           An invitation will be generated
 * - CFG_FRIEND | CFG_INVITE | CFG_REQUEST  => no one can join the circle, but all members can request a
 *                                             moderator to accept new member. An invitation will be generated
 *
 * @package OCA\Circles\Model
 */
class Circle extends ManagedModel implements INC21Convert, INC21QueryRow, JsonSerializable {


	use TArrayTools;
	use TNC21Convert;


	// specific value
	const CFG_CIRCLE = 0;        // only for code readability. Circle is locked by default.
	const CFG_SINGLE = 1;        // Circle with only one single member.
	const CFG_PERSONAL = 2;      // Personal circle, only the owner can see it.

	// bitwise
	const CFG_VISIBLE = 8;        // Visible to everyone, if not visible, people have to know its name to be able to find it
	const CFG_OPEN = 16;          // Circle is open, people can join
	const CFG_INVITE = 32;        // Adding a member generate an invitation that needs to be accepted
	const CFG_REQUEST = 64;       // Request to join Circles needs to be confirmed by a moderator
	const CFG_FRIEND = 128;       // Members of the circle can invite their friends
	const CFG_PROTECTED = 256;    // Password protected to join/request
	const CFG_NO_OWNER = 512;     // no owner, only members
	const CFG_HIDDEN = 1024;      // Fully hidden, only backend Circles
	const CFG_ROOT = 2048;        // Circle cannot be inside another Circle
	const CFG_FEDERATED = 4096;   // Federated

	const ID_LENGTH = 15;

	static $DEF = [
		1 => 'S|Single',

		2    => 'P|Personal',
		8    => 'V|Visible',
		16   => 'O|Open',
		32   => 'I|Invite',
		64   => 'JR|Join Request',
		128  => 'F|Friends',
		256  => 'PP|Password Protected',
		512  => 'NO|No Owner',
		1024 => 'H|Hidden',
		2048 => 'T|Root',
		4096 => 'F|Federated'
	];


	/** @var string */
	private $id = '';

	/** @var int */
	private $config = 0;

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

	/** @var Member */
	private $initiator;

	/** @var array */
	private $settings = [];

	/** @var string */
	private $description = '';

	/** @var int */
	private $contactAddressBook = 0;

	/** @var string */
	private $contactGroupName = '';

	/** @var string */
	private $instance = '';

//	/** @var bool */
//	private $hidden = false;

	/** @var int */
	private $creation = 0;


	/** @var Circle[] */
	private $memberOf = null;

	private $completeJson = false;


	/**
	 * Circle constructor.
	 */
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
	 * @param int $config
	 *
	 * @return self
	 */
	public function setConfig(int $config): self {
		$this->config = $config;

//		$this->hidden = false;
//		foreach (array_keys(self::$DEF) as $def) {
//			if ($this->isType($def) && substr(self::$DEF[$def], 0, 1) === '*') {
//				$this->setHidden(true);
//				break;
//			}
//		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function getConfig(): int {
		return $this->config;
	}

	/**
	 * @param int $flag
	 *
	 * @return bool
	 */
	public function isConfig(int $flag): bool {
		return (($this->getConfig() & $flag) !== 0);
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
	 *
	 * @return self
	 */
	public function setOwner(Member $owner): self {
		$this->owner = $owner;

		return $this;
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
		if (empty($this->members)) {
			$this->getManager()->getMembers($this);
		}

		return $this->members;
	}


	/**
	 * @param Member $initiator
	 *
	 * @return Circle
	 */
	public function setInitiator(Member $initiator): self {
		$this->initiator = $initiator;

		return $this;
	}

	/**
	 * @return Member
	 */
	public function getInitiator(): Member {
		return $this->initiator;
	}

	/**
	 * @return bool
	 */
	public function hasInitiator(): bool {
		return ($this->initiator !== null);
	}

	/**
	 * @param string $instance
	 *
	 * @return Circle
	 */
	public function setInstance(string $instance): self {
		if ($this->isConfig(self::CFG_NO_OWNER)) {
			$this->instance = $instance;
		}

		return $this;
	}

	/**
	 * @return string
	 * @throws OwnerNotFoundException
	 */
	public function getInstance(): string {
		if ($this->isConfig(self::CFG_NO_OWNER)) {
			return $this->instance;
		}

		if (!$this->hasOwner()) {
			throw new OwnerNotFoundException('circle has no owner, or not set to have no owner');
		}

		return $this->getOwner()->getInstance();
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
	 * @param int $contactAddressBook
	 *
	 * @return self
	 */
	public function setContactAddressBook(int $contactAddressBook): self {
		$this->contactAddressBook = $contactAddressBook;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getContactAddressBook(): int {
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


//	/**
//	 * @param bool $hidden
//	 *
//	 * @return Circle
//	 */
//	public function setHidden(bool $hidden): self {
//		$this->hidden = $hidden;
//
//		return $this;
//	}
//
//	/**
//	 * @return bool
//	 */
//	public function isHidden(): bool {
//		return $this->hidden;
//	}


	/**
	 * @param array $memberOf
	 *
	 * @return $this
	 */
	public function setMemberOf(array $memberOf): self {
		$this->memberOf = $memberOf;

		return $this;
	}

	/**
	 * @return Circle[]
	 */
	public function memberOf(): array {
		if ($this->memberOf === null) {
			$this->getManager()->memberOf($this);
		}

		return $this->memberOf;
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
			 ->setConfig($this->getInt('config', $data))
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

		try {
			/** @var Member $owner */
			$initiator = $this->convert($this->getArray('initiator', $data), Member::class);
			$this->setInitiator($initiator);
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
			'config'      => $this->getConfig(),
			'description' => $this->getDescription(),
			'settings'    => $this->getSettings(),
			//			'hidden'      => $this->isHidden(),
			'creation'    => $this->getCreation()
		];

		if ($this->hasOwner()) {
			$arr['owner'] = $this->getOwner();
		}

		if ($this->hasInitiator()) {
			$arr['initiator'] = $this->getInitiator();
		}

		if ($this->getManager()->isFullDetails()) {
			$arr['memberOf'] = $this->memberOf();
		}

		return array_filter($arr);
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return INC21QueryRow
	 * @throws CircleNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): INC21QueryRow {
		if (!array_key_exists($prefix . 'unique_id', $data)) {
			throw new CircleNotFoundException();
		}

		$this->setId($this->get($prefix . 'unique_id', $data))
			 ->setName($this->get($prefix . 'name', $data))
			 ->setAltName($this->get($prefix . 'alt_name', $data))
			 ->setConfig($this->getInt($prefix . 'config', $data))
			 ->setInstance($this->get($prefix . 'instance', $data))
			 ->setSettings($this->getArray($prefix . 'settings', $data))
			 ->setContactAddressBook($this->getInt($prefix . 'contact_addressbook', $data))
			 ->setContactGroupName($this->get($prefix . 'contact_groupname', $data))
			 ->setDescription($this->get($prefix . 'description', $data));

		$creation = $this->get($prefix . 'creation', $data);
		$this->setCreation(DateTime::createFromFormat('Y-m-d H:i:s', $creation)->getTimestamp());

		$this->getManager()->importOwnerFromDatabase($this, $data);
		$this->getManager()->importInitiatorFromDatabase($this, $data);

		return $this;
	}


	/**
	 * @param Circle $circle
	 *
	 * @return bool
	 * @throws OwnerNotFoundException
	 */
	public function compareWith(Circle $circle): bool {
		if ($this->getId() !== $circle->getId()
			|| $this->getInstance() !== $circle->getInstance()
			|| $this->getConfig() !== $circle->getConfig()) {
			return false;
		}

		if ($this->hasOwner()
			&& (!$circle->hasOwner()
				|| !$this->getOwner()->compareWith($circle->getOwner()))) {
			return false;
		}

		if ($this->hasInitiator()
			&& (!$circle->hasInitiator()
				|| !$this->getInitiator()->compareWith($circle->getInitiator()))) {
			return false;
		}

		return true;
	}

}

