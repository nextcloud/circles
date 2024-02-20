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

use DateTime;
use JsonSerializable;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\MemberHelperException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\IEntity;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCP\Security\IHasher;

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
class Circle extends ManagedModel implements IEntity, IDeserializable, IQueryRow, JsonSerializable {
	use TArrayTools;
	use TDeserialize;

	public const FLAGS_SHORT = 1;
	public const FLAGS_LONG = 2;

	// specific value
	public const CFG_CIRCLE = 0;        // only for code readability. Circle is locked by default.
	public const CFG_SINGLE = 1;        // Circle with only one single member.
	public const CFG_PERSONAL = 2;      // Personal circle, only the owner can see it.

	// bitwise
	public const CFG_SYSTEM = 4;            // System Circle (not managed by the official front-end). Meaning some config are limited
	public const CFG_VISIBLE = 8;           // Visible to everyone, if not visible, people have to know its name to be able to find it
	public const CFG_OPEN = 16;             // Circle is open, people can join
	public const CFG_INVITE = 32;           // Adding a member generate an invitation that needs to be accepted
	public const CFG_REQUEST = 64;          // Request to join Circles needs to be confirmed by a moderator
	public const CFG_FRIEND = 128;          // Members of the circle can invite their friends
	public const CFG_PROTECTED = 256;       // Password protected to join/request
	public const CFG_NO_OWNER = 512;        // no owner, only members
	public const CFG_HIDDEN = 1024;         // hidden from listing, but available as a share entity
	public const CFG_BACKEND = 2048;            // Fully hidden, only backend Circles
	public const CFG_LOCAL = 4096;              // Local even on GlobalScale
	public const CFG_ROOT = 8192;               // Circle cannot be inside another Circle
	public const CFG_CIRCLE_INVITE = 16384;     // Circle must confirm when invited in another circle
	public const CFG_FEDERATED = 32768;         // Federated
	public const CFG_MOUNTPOINT = 65536;        // Generate a Files folder for this Circle
	public const CFG_APP = 131072;          // Some features are not available to the OCS API (ie. destroying Circle)
	public static $DEF_CFG_MAX = 262143;


	/**
	 * Note: When editing those values, update lib/Application/Capabilities.php
	 *
	 * @see Capabilities::getCapabilitiesCircleConstants()
	 * @var array
	 */
	public static $DEF_CFG = [
		1 => 'S|Single',
		2 => 'P|Personal',
		4 => 'Y|System',
		8 => 'V|Visible',
		16 => 'O|Open',
		32 => 'I|Invite',
		64 => 'JR|Join Request',
		128 => 'F|Friends',
		256 => 'PP|Password Protected',
		512 => 'NO|No Owner',
		1024 => 'H|Hidden',
		2048 => 'T|Backend',
		4096 => 'L|Local',
		8192 => 'T|Root',
		16384 => 'CI|Circle Invite',
		32768 => 'F|Federated',
		65536 => 'M|Nountpoint',
		131072 => 'A|App'
	];


	/**
	 * Note: When editing those values, update lib/Application/Capabilities.php
	 *
	 * @see Capabilities::getCapabilitiesCircleConstants()
	 * @var array
	 */
	public static $DEF_SOURCE = [
		1 => 'Nextcloud Account',
		2 => 'Nextcloud Group',
		4 => 'Email Address',
		8 => 'Contact',
		16 => 'Circle',
		10000 => 'Nextcloud App',
		10001 => 'Circles App',
		10002 => 'Admin Command Line',
		11000 => '3rd party app',
		11010 => 'Collectives App'
	];


	public static $DEF_CFG_CORE_FILTER = [
		1,
		2,
		4
	];

	public static $DEF_CFG_SYSTEM_FILTER = [
		512,
		1024,
		2048
	];


	/** @var string */
	private $singleId = '';

	/** @var int */
	private $config = 0;

	/** @var string */
	private $name = '';

	/** @var string */
	private $displayName = '';

	/** @var string */
	private $sanitizedName = '';

	/** @var int */
	private $source = 0;

	/** @var Member */
	private $owner;

	/** @var Member */
	private $initiator;

	/** @var Member */
	private $directInitiator;

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

	/** @var int */
	private $population = 0;

	/** @var int */
	private $populationInherited = 0;

//	/** @var bool */
//	private $hidden = false;

	/** @var int */
	private $creation = 0;


	/** @var Member[] */
	private $members = null;

	/** @var Member[] */
	private $inheritedMembers = null;

	/** @var bool */
	private $detailedInheritedMember = false;

	/** @var Membership[] */
	private $memberships = null;


	/**
	 * Circle constructor.
	 */
	public function __construct() {
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
	 * @return string
	 * @deprecated - removed in NC23
	 */
	public function getUniqueId(): string {
		return $this->getSingleId();
	}


	/**
	 * @param int $config
	 *
	 * @return self
	 */
	public function setConfig(int $config): self {
		$this->config = $config;

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
	 * @param int $test
	 *
	 * @return bool
	 */
	public function isConfig(int $flag, int $test = 0): bool {
		if ($test === 0) {
			$test = $this->getConfig();
		}

		return (($test & $flag) !== 0);
	}

	/**
	 * @param int $flag
	 */
	public function addConfig(int $flag): void {
		if (!$this->isConfig($flag)) {
			$this->config += $flag;
		}
	}

	/**
	 * @param int $flag
	 */
	public function remConfig(int $flag): void {
		if ($this->isConfig($flag)) {
			$this->config -= $flag;
		}
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
	 * @param string $displayName
	 *
	 * @return self
	 */
	public function setDisplayName(string $displayName): self {
		$this->displayName = $displayName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->displayName;
	}


	/**
	 * @param string $sanitizedName
	 *
	 * @return Circle
	 */
	public function setSanitizedName(string $sanitizedName): self {
		$this->sanitizedName = $sanitizedName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSanitizedName(): string {
		return $this->sanitizedName;
	}


	/**
	 * @param int $source
	 *
	 * @return Circle
	 */
	public function setSource(int $source): self {
		$this->source = $source;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getSource(): int {
		return $this->source;
	}


	/**
	 * @param ?Member $owner
	 *
	 * @return self
	 */
	public function setOwner(?Member $owner): self {
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
		return !is_null($this->owner);
	}


	/**
	 * @return bool
	 */
	public function hasMembers(): bool {
		return !is_null($this->members);
	}

	/**
	 * @param Member[] $members
	 *
	 * @return self
	 */
	public function setMembers(array $members): self {
		$this->members = $members;

		return $this;
	}

	/**
	 * @return Member[]
	 */
	public function getMembers(): array {
		if (!$this->hasMembers()) {
			$this->getManager()->getMembers($this);
		}

		return $this->members;
	}


	/**
	 * @param array $members
	 * @param bool $detailed
	 *
	 * @return self
	 */
	public function setInheritedMembers(array $members, bool $detailed): self {
		$this->inheritedMembers = $members;
		$this->detailedInheritedMember = $detailed;

		return $this;
	}

	/**
	 * @param Member[] $members
	 *
	 * @return Circle
	 */
	public function addInheritedMembers(array $members): self {
		$knownIds = array_map(
			function (Member $member): string {
				return $member->getId();
			}, $this->inheritedMembers
		);

		foreach ($members as $member) {
			if (!array_key_exists($member->getId(), $knownIds)) {
				$this->inheritedMembers[] = $member;
				$knownIds[] = $member->getId();
			}
		}

		return $this;
	}


	/**
	 * if $remote is true, it will returns also details on inherited members from remote+locals Circles.
	 * This should be used only if extra details are required (mail address ?) as it will send a request to
	 * the remote instance if the circleId is not locally known.
	 * because of the resource needed to retrieve this data, $remote=true should not be used on main process !
	 *
	 * @param bool $detailed
	 * @param bool $remote
	 *
	 * @return Member[]
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function getInheritedMembers(bool $detailed = false, bool $remote = false): array {
		if (is_null($this->inheritedMembers)
			|| ($detailed && !$this->detailedInheritedMember)) {
			$this->getManager()->getInheritedMembers($this, $detailed);
		}

		if ($remote) {
			$this->getManager()->getRemoteInheritedMembers($this, $detailed);
		}

		return $this->inheritedMembers;
	}


	/**
	 * @return bool
	 */
	public function hasMemberships(): bool {
		return !is_null($this->memberships);
	}

	/**
	 * @param array $memberships
	 *
	 * @return self
	 */
	public function setMemberships(array $memberships): IEntity {
		$this->memberships = $memberships;

		return $this;
	}

	/**
	 * @return Membership[]
	 */
	public function getMemberships(): array {
		if (!$this->hasMemberships()) {
			$this->getManager()->getMemberships($this);
		}

		return $this->memberships;
	}


	/**
	 * @param string $singleId
	 * @param bool $detailed
	 *
	 * @return Membership
	 * @throws MembershipNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getLink(string $singleId, bool $detailed = false): Membership {
		return $this->getManager()->getLink($this, $singleId, $detailed);
	}


	/**
	 * @param Member|null $initiator
	 *
	 * @return Circle
	 */
	public function setInitiator(?Member $initiator): self {
		$this->initiator = $initiator;

		return $this;
	}

	/**
	 * @return Member
	 */
	public function getInitiator(): Member {
		if (is_null($this->initiator)
			|| ($this->initiator->getId() === ''
				&& !is_null($this->directInitiator)
				&& $this->directInitiator->getId() !== '')) {
			return $this->directInitiator;
		}

		return $this->initiator;
	}

	/**
	 * @return bool
	 */
	public function hasInitiator(): bool {
		return (!is_null($this->initiator) || !is_null($this->directInitiator));
	}

	/**
	 * @param Member|null $directInitiator
	 *
	 * @return $this
	 */
	public function setDirectInitiator(?Member $directInitiator): self {
		$this->directInitiator = $directInitiator;

		return $this;
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
		if (!$this->hasOwner()) {
			throw new OwnerNotFoundException('circle has no owner');
		}

		return $this->getOwner()->getInstance();
	}


	/**
	 * @return bool
	 * @throws OwnerNotFoundException
	 */
	public function isLocal(): bool {
		return $this->getManager()->isLocalInstance($this->getInstance());
	}


	/**
	 * @param int $population
	 *
	 * @return Circle
	 */
	public function setPopulation(int $population): self {
		$this->population = $population;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPopulation(): int {
		return $this->population;
	}


	/**
	 * @param int $population
	 *
	 * @return Circle
	 */
	public function setPopulationInherited(int $population): self {
		$this->populationInherited = $population;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPopulationInherited(): int {
		return $this->populationInherited;
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
	 * @return string
	 */
	public function getUrl(): string {
		return $this->getManager()->generateLinkToCircle($this->getSingleId());
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
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		if ($this->get('id', $data) === '') {
			throw new InvalidItemException();
		}

		$this->setSingleId($this->get('id', $data))
			 ->setName($this->get('name', $data))
			 ->setDisplayName($this->get('displayName', $data))
			 ->setSanitizedName($this->get('sanitizedName', $data))
			 ->setSource($this->getInt('source', $data))
			 ->setConfig($this->getInt('config', $data))
			 ->setPopulation($this->getInt('population', $data))
			 ->setSettings($this->getArray('settings', $data))
//			 ->setContactAddressBook($this->get('contact_addressbook', $data))
//			 ->setContactGroupName($this->get('contact_groupname', $data))
			 ->setDescription($this->get('description', $data))
			 ->setCreation($this->getInt('creation', $data));

		try {
			/** @var Member $owner */
			$owner = $this->deserialize($this->getArray('owner', $data), Member::class);
			$this->setOwner($owner);
		} catch (InvalidItemException $e) {
		}

		try {
			/** @var Member $initiator */
			$initiator = $this->deserialize($this->getArray('initiator', $data), Member::class);
			$this->setInitiator($initiator);
		} catch (InvalidItemException $e) {
		}

		return $this;
	}


	/**
	 * @return array
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function jsonSerialize(): array {
		$arr = [
			'id' => $this->getSingleId(),
			'name' => $this->getName(),
			'displayName' => $this->getDisplayName(),
			'sanitizedName' => $this->getSanitizedName(),
			'source' => $this->getSource(),
			'population' => $this->getPopulation(),
			'config' => $this->getConfig(),
			'description' => $this->getDescription(),
			'url' => $this->getUrl(),
			'creation' => $this->getCreation(),
			'initiator' => ($this->hasInitiator()) ? $this->getInitiator() : null
		];

		if ($this->hasOwner()) {
			$arr['owner'] = $this->getOwner();
		}

		if ($this->hasMembers()) {
			$arr['members'] = $this->getMembers();
		}

		if (!is_null($this->inheritedMembers)) {
			$arr['inheritedMembers'] = $this->getInheritedMembers();
		}

		if ($this->hasMemberships()) {
			$arr['memberships'] = $this->getMemberships();
		}

		// settings should only be available to admin
		if ($this->hasInitiator()) {
			$initiatorHelper = new MemberHelper($this->getInitiator());
			try {
				$initiatorHelper->mustBeAdmin();
				$arr['settings'] = $this->getSettings();
			} catch (MemberHelperException | MemberLevelException $e) {
			}
		}

		return $arr;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IQueryRow
	 * @throws CircleNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if ($this->get($prefix . 'unique_id', $data) === '') {
			throw new CircleNotFoundException();
		}

		$this->setSingleId($this->get($prefix . 'unique_id', $data))
			 ->setName($this->get($prefix . 'name', $data))
			 ->setDisplayName($this->get($prefix . 'display_name', $data))
			 ->setSanitizedName($this->get($prefix . 'sanitized_name', $data))
			 ->setConfig($this->getInt($prefix . 'config', $data))
			 ->setSource($this->getInt($prefix . 'source', $data))
			 ->setInstance($this->get($prefix . 'instance', $data))
			 ->setSettings($this->getArray($prefix . 'settings', $data))
			 ->setContactAddressBook($this->getInt($prefix . 'contact_addressbook', $data))
			 ->setContactGroupName($this->get($prefix . 'contact_groupname', $data))
			 ->setDescription($this->get($prefix . 'description', $data));

		$creation = $this->get($prefix . 'creation', $data);
		$dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $creation);
		$timestamp = $dateTime ? $dateTime->getTimestamp() : (int) strtotime($creation);
		$this->setCreation($timestamp);

		$this->setPopulation($this->getInt('population', $this->getSettings()));
		$this->setPopulationInherited($this->getInt('populationInherited', $this->getSettings()));

		$this->getManager()->manageImportFromDatabase($this, $data, $prefix);


		// TODO: deprecated in NC27, remove those (17) lines that was needed to finalise migration to 24
		// if password is not hashed (pre-22), hash it and update new settings in DB
		$curr = $this->get('password_single', $this->getSettings());
		if (strlen($curr) >= 1 && strlen($curr) < 64) {
			/** @var IHasher $hasher */
			$hasher = \OC::$server->get(IHasher::class);
			/** @var CircleRequest $circleRequest */
			$circleRequest = \OC::$server->get(CircleRequest::class);

			$new = $hasher->hash($curr);
			$settings = $this->getSettings();
			$settings['password_single'] = $new;
			$this->setSettings($settings);

			$circleRequest->updateSettings($this);
		}

		// END deprecated NC27

		return $this;
	}


	/**
	 * @param Circle $circle
	 *
	 * @return bool
	 * @throws OwnerNotFoundException
	 */
	public function compareWith(Circle $circle): bool {
		if ($this->getSingleId() !== $circle->getSingleId()
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


	/**
	 * @param Circle $circle
	 * @param int $display
	 *
	 * @return array
	 */
	public static function getCircleFlags(Circle $circle, int $display = self::FLAGS_LONG): array {
		$config = [];
		foreach (array_keys(Circle::$DEF_CFG) as $def) {
			if ($circle->isConfig($def)) {
				[$short, $long] = explode('|', Circle::$DEF_CFG[$def]);
				switch ($display) {
					case self::FLAGS_SHORT:
						$config[] = $short;
						break;

					case self::FLAGS_LONG:
						$config[] = $long;
						break;
				}
			}
		}

		return $config;
	}
}
