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
use OCA\Circles\AppInfo\Capabilities;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Exceptions\ParseMemberLevelException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\IEntity;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TDeserialize;

/**
 * Class Member
 *
 * @package OCA\Circles\Model
 */
class Member extends ManagedModel implements
	IEntity,
	IFederatedUser,
	IDeserializable,
	IQueryRow,
	JsonSerializable {
	use TArrayTools;
	use TDeserialize;


	public const LEVEL_NONE = 0;
	public const LEVEL_MEMBER = 1;
	public const LEVEL_MODERATOR = 4;
	public const LEVEL_ADMIN = 8;
	public const LEVEL_OWNER = 9;

	public const TYPE_SINGLE = 0;
	public const TYPE_USER = 1;
	public const TYPE_GROUP = 2;
	public const TYPE_MAIL = 4;
	public const TYPE_CONTACT = 8;
	public const TYPE_CIRCLE = 16;
	public const TYPE_APP = 10000;

	public const ALLOWING_ALL_TYPES = 31;

	public const APP_CIRCLES = 10001;
	public const APP_OCC = 10002;
	public const APP_DEFAULT = 11000;


	public static $TYPE = [
		0 => 'single',
		1 => 'user',
		2 => 'group',
		4 => 'mail',
		8 => 'contact',
		16 => 'circle',
		10000 => 'app'
	];

	/**
	 * Note: When editing those values, update lib/Application/Capabilities.php
	 *
	 * @see Capabilities::generateConstantsMember()
	 */
	public const STATUS_INVITED = 'Invited';
	public const STATUS_REQUEST = 'Requesting';
	public const STATUS_MEMBER = 'Member';
	public const STATUS_BLOCKED = 'Blocked';


	/**
	 * Note: When editing those values, update lib/Application/Capabilities.php
	 *
	 * @see Capabilities::generateConstantsMember()
	 * @var array
	 */
	public static $DEF_LEVEL = [
		1 => 'Member',
		4 => 'Moderator',
		8 => 'Admin',
		9 => 'Owner'
	];


	public static $DEF_TYPE_MAX = 31;


	/** @var string */
	private $id = '';

	/** @var string */
	private $circleId = '';

	/** @var string */
	private $singleId = '';

	/** @var string */
	private $userId = '';

	/** @var int */
	private $userType = 0;

	/** @var Circle */
	private $basedOn;

	/** @var Member */
	private $inheritanceFrom;

	/** @var FederatedUser */
	private $inheritedBy;

	/** @var string */
	private $instance = '';

	/** @var FederatedUser */
	private $invitedBy;

	/** @var RemoteInstance */
	private $remoteInstance;

	/** @var bool */
	private $local = false;

	/** @var int */
	private $level = 0;

	/** @var string */
	private $status = 'Unknown';

	/** @var array */
	private $notes = [];

	/** @var string */
	private $displayName = '';

	/** @var int */
	private $displayUpdate = 0;

	/** @var string */
	private $contactId = '';

	/** @var string */
	private $contactMeta = '';

	/** @var Circle */
	private $circle;

	/** @var int */
	private $joined = 0;

	/** @var Membership[] */
	private $memberships = null;


	/**
	 * Member constructor.
	 */
	public function __construct() {
	}


	/**
	 * @param string $id
	 *
	 * @return $this
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
	 * @param string $circleId
	 *
	 * @return Member
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
	 * This should replace user_id, user_type and instance; and will use the data from Circle with
	 * Config=CFG_SINGLE
	 *
	 * @param string $singleId
	 *
	 * @return $this
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
	 * @param string $userId
	 *
	 * @return Member
	 */
	public function setUserId(string $userId): self {
		$this->userId = $userId;
		if ($this->displayName === '') {
			$this->displayName = $userId;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->userId;
	}


	/**
	 * @param int $userType
	 *
	 * @return Member
	 */
	public function setUserType(int $userType): self {
		$this->userType = $userType;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserType(): int {
		return $this->userType;
	}

	/**
	 * @return int
	 * @deprecated 22.0.0 Use `getUserType()` instead
	 */
	public function getType(): int {
		return $this->getUserType();
	}


	/**
	 * @param string $instance
	 *
	 * @return Member
	 */
	public function setInstance(string $instance): self {
		$this->instance = $instance;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInstance(): string {
		return $this->instance;
	}


	/**
	 * @return bool
	 */
	public function isLocal(): bool {
		return $this->getManager()->isLocalInstance($this->getInstance());
	}


	/**
	 * @param FederatedUser $invitedBy
	 *
	 * @return Member
	 */
	public function setInvitedBy(FederatedUser $invitedBy): Member {
		$this->invitedBy = $invitedBy;

		return $this;
	}

	/**
	 * @return FederatedUser
	 */
	public function getInvitedBy(): FederatedUser {
		return $this->invitedBy;
	}

	/**
	 * @return bool
	 */
	public function hasInvitedBy(): bool {
		return !is_null($this->invitedBy);
	}


	/**
	 * @return bool
	 */
	public function hasRemoteInstance(): bool {
		return !is_null($this->remoteInstance);
	}

	/**
	 * @param RemoteInstance $remoteInstance
	 *
	 * @return Member
	 */
	public function setRemoteInstance(RemoteInstance $remoteInstance): self {
		$this->remoteInstance = $remoteInstance;

		return $this;
	}

	/**
	 * @return RemoteInstance
	 */
	public function getRemoteInstance(): RemoteInstance {
		return $this->remoteInstance;
	}


	/**
	 * @return bool
	 */
	public function hasBasedOn(): bool {
		return !is_null($this->basedOn);
	}

	/**
	 * @param Circle $basedOn
	 *
	 * @return $this
	 */
	public function setBasedOn(Circle $basedOn): self {
		$this->basedOn = $basedOn;

		return $this;
	}

	/**
	 * @return Circle
	 */
	public function getBasedOn(): Circle {
		return $this->basedOn;
	}


	/**
	 * @return bool
	 */
	public function hasInheritedBy(): bool {
		return !is_null($this->inheritedBy);
	}

	/**
	 * @param FederatedUser $inheritedBy
	 *
	 * @return $this
	 */
	public function setInheritedBy(FederatedUser $inheritedBy): self {
		$this->inheritedBy = $inheritedBy;

		return $this;
	}

	/**
	 * @return FederatedUser
	 */
	public function getInheritedBy(): FederatedUser {
		return $this->inheritedBy;
	}


	/**
	 * @return bool
	 */
	public function hasInheritanceFrom(): bool {
		return !is_null($this->inheritanceFrom);
	}

	/**
	 * @param Member $inheritanceFrom
	 *
	 * @return $this
	 */
	public function setInheritanceFrom(Member $inheritanceFrom): self {
		$this->inheritanceFrom = $inheritanceFrom;

		return $this;
	}

	/**
	 * @return Member|null
	 */
	public function getInheritanceFrom(): ?Member {
		return $this->inheritanceFrom;
	}


	/**
	 * @param int $level
	 *
	 * @return Member
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
	 * @param string $status
	 *
	 * @return Member
	 */
	public function setStatus(string $status): self {
		$this->status = $status;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getStatus(): string {
		return $this->status;
	}


	/**
	 * @param array $notes
	 *
	 * @return Member
	 */
	public function setNotes(array $notes): self {
		$this->notes = $notes;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getNotes(): array {
		return $this->notes;
	}


	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function getNote(string $key): string {
		return $this->get($key, $this->notes);
	}

	/**
	 * @param string $key
	 *
	 * @return array
	 */
	public function getNoteArray(string $key): array {
		return $this->getArray($key, $this->notes);
	}

	/**
	 * @param string $key
	 * @param string $note
	 *
	 * @return $this
	 */
	public function setNote(string $key, string $note): self {
		$this->notes[$key] = $note;

		return $this;
	}

	/**
	 * @param string $key
	 * @param array $note
	 *
	 * @return $this
	 */
	public function setNoteArray(string $key, array $note): self {
		$this->notes[$key] = $note;

		return $this;
	}

	/**
	 * @param string $key
	 * @param JsonSerializable $obj
	 *
	 * @return $this
	 */
	public function setNoteObj(string $key, JsonSerializable $obj): self {
		$this->notes[$key] = $obj;

		return $this;
	}


	/**
	 * @param string $displayName
	 *
	 * @return Member
	 */
	public function setDisplayName(string $displayName): self {
		if ($displayName !== '') {
			$this->displayName = $displayName;
		}

		return $this;
	}


	/**
	 * @param int $displayUpdate
	 *
	 * @return Member
	 */
	public function setDisplayUpdate(int $displayUpdate): self {
		$this->displayUpdate = $displayUpdate;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getDisplayUpdate(): int {
		return $this->displayUpdate;
	}


	/**
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->displayName;
	}


	/**
	 * @param string $contactId
	 *
	 * @return Member
	 */
	public function setContactId(string $contactId): self {
		$this->contactId = $contactId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContactId(): string {
		return $this->contactId;
	}


	/**
	 * @param string $contactMeta
	 *
	 * @return Member
	 */
	public function setContactMeta(string $contactMeta): self {
		$this->contactMeta = $contactMeta;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContactMeta(): string {
		return $this->contactMeta;
	}


	/**
	 * @param Circle $circle
	 *
	 * @return self
	 */
	public function setCircle(Circle $circle): self {
		$this->circle = $circle;

		return $this;
	}

	/**
	 * @return Circle
	 */
	public function getCircle(): Circle {
		return $this->circle;
	}

	/**
	 * @return bool
	 */
	public function hasCircle(): bool {
		return (!is_null($this->circle));
	}


	/**
	 * @param int $joined
	 *
	 * @return Member
	 */
	public function setJoined(int $joined): self {
		$this->joined = $joined;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getJoined(): int {
		return $this->joined;
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
		if (is_null($this->memberships)) {
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
		if ($singleId !== '') {
			$this->getManager()->getLink($this, $singleId, $detailed);
		}
		
		throw new MembershipNotFoundException();
	}

	/**
	 * @param string $circleId
	 * @param bool $detailed
	 *
	 * @return Membership
	 * @throws MembershipNotFoundException
	 * @throws RequestBuilderException
	 * @deprecated - use getLink();
	 */
	public function getMembership(string $circleId, bool $detailed = false): Membership {
		return $this->getLink($circleId, $detailed);
	}


	/**
	 * @param Member $member
	 * @param bool $full
	 *
	 * @return bool
	 */
	public function compareWith(Member $member, bool $full = true): bool {
		if ($this->getId() !== $member->getId()
			|| $this->getCircleId() !== $member->getCircleId()
			|| $this->getSingleId() !== $member->getSingleId()
			|| $this->getUserId() !== $member->getUserId()
			|| $this->getUserType() <> $member->getUserType()
			|| $this->getInstance() !== $member->getInstance()) {
			return false;
		}

		if ($full
			&& ($this->getLevel() <> $member->getLevel()
				|| $this->getStatus() !== $member->getStatus())) {
			return false;
		}

		return true;
	}


	/**
	 * @param array $data
	 *
	 * @return $this
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		if ($this->get('userId', $data) === '') {
			throw new InvalidItemException();
		}

		$this->setId($this->get('id', $data));
		$this->setCircleId($this->get('circleId', $data));
		$this->setSingleId($this->get('singleId', $data));
		$this->setUserId($this->get('userId', $data));
		$this->setUserType($this->getInt('userType', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setLevel($this->getInt('level', $data));
		$this->setStatus($this->get('status', $data));
		$this->setDisplayName($this->get('displayName', $data));
		$this->setDisplayUpdate($this->getInt('displayUpdate', $data));
		$this->setNotes($this->getArray('notes', $data));
		$this->setContactId($this->get('contactId', $data));
		$this->setContactMeta($this->get('contactMeta', $data));
		$this->setJoined($this->getInt('joined', $data));

		try {
			/** @var Circle $circle */
			$circle = $this->deserialize($this->getArray('circle', $data), Circle::class);
			$this->setCircle($circle);
		} catch (InvalidItemException $e) {
		}

		try {
			/** @var Circle $circle */
			$circle = $this->deserialize($this->getArray('basedOn', $data), Circle::class);
			$this->setBasedOn($circle);
		} catch (InvalidItemException $e) {
		}

		try {
			/** @var FederatedUser $invitedBy */
			$invitedBy = $this->deserialize($this->getArray('invitedBy', $data), FederatedUser::class);
			$this->setInvitedBy($invitedBy);
		} catch (InvalidItemException $e) {
		}

		try {
			/** @var FederatedUSer $inheritedBy */
			$inheritedBy = $this->deserialize($this->getArray('inheritedBy', $data), Membership::class);
			$this->setInheritedBy($inheritedBy);
		} catch (InvalidItemException $e) {
		}

		return $this;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IQueryRow
	 * @throws MemberNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if ($this->get($prefix . 'single_id', $data) === '') {
			throw new MemberNotFoundException();
		}

		$this->setId($this->get($prefix . 'member_id', $data));
		$this->setCircleId($this->get($prefix . 'circle_id', $data));
		$this->setSingleId($this->get($prefix . 'single_id', $data));
		$this->setUserId($this->get($prefix . 'user_id', $data));
		$this->setUserType($this->getInt($prefix . 'user_type', $data));
		$this->setInstance($this->get($prefix . 'instance', $data));
		$this->setLevel($this->getInt($prefix . 'level', $data));
		$this->setStatus($this->get($prefix . 'status', $data));
		$this->setDisplayName($this->get($prefix . 'cached_name', $data));
		$this->setNotes($this->getArray($prefix . 'note', $data));
		$this->setContactId($this->get($prefix . 'contact_id', $data));
		$this->setContactMeta($this->get($prefix . 'contact_meta', $data));

		$cachedUpdate = $this->get($prefix . 'cached_update', $data);
		if ($cachedUpdate !== '') {
			$this->setDisplayUpdate(DateTime::createFromFormat('Y-m-d H:i:s', $cachedUpdate)->getTimestamp());
		}

		$joined = $this->get($prefix . 'joined', $data);
		if ($joined !== '') {
			$this->setJoined(DateTime::createFromFormat('Y-m-d H:i:s', $joined)->getTimestamp());
		}

		if ($this->getInstance() === '') {
			$this->setInstance($this->getManager()->getLocalInstance());
		}

		$this->getManager()->manageImportFromDatabase($this, $data, $prefix);

		// in case invitedBy is not obtainable from 'invited_by', we reach data from 'note'
		if (!$this->hasInvitedBy()) {
			$invitedByArray = $this->getNoteArray('invitedBy');
			if (!empty($invitedByArray)) {
				try {
					$invitedBy = new FederatedUser();
					$this->setInvitedBy($invitedBy->import($invitedByArray));
				} catch (InvalidItemException $e) {
				}
			}
		}

		return $this;
	}


	/**
	 * @return string[]
	 * @throws UnknownInterfaceException
	 */
	public function jsonSerialize(): array {
		$arr = [
			'id' => $this->getId(),
			'circleId' => $this->getCircleId(),
			'singleId' => $this->getSingleId(),
			'userId' => $this->getUserId(),
			'userType' => $this->getUserType(),
			'instance' => $this->getManager()->fixInstance($this->getInstance()),
			'local' => $this->isLocal(),
			'level' => $this->getLevel(),
			'status' => $this->getStatus(),
			'displayName' => $this->getDisplayName(),
			'displayUpdate' => $this->getDisplayUpdate(),
			'notes' => $this->getNotes(),
			'contactId' => $this->getContactId(),
			'contactMeta' => $this->getContactMeta(),
			'joined' => $this->getJoined()
		];

		if ($this->hasInvitedBy()) {
			$arr['invitedBy'] = $this->getInvitedBy();
		}

		if ($this->hasBasedOn()) {
			$arr['basedOn'] = $this->getBasedOn();
		}

		if ($this->hasInheritedBy()) {
			$arr['inheritedBy'] = $this->getInheritedBy();
		}

		if ($this->hasInheritanceFrom()) {
			$arr['inheritanceFrom'] = $this->getInheritanceFrom();
		}

		if ($this->hasCircle()) {
			$arr['circle'] = $this->getCircle();
		}

		if ($this->hasMemberships()) {
			$arr['memberships'] = $this->getMemberships();
		}

		if ($this->hasRemoteInstance()) {
			$arr['remoteInstance'] = $this->getRemoteInstance();
		}

		return $arr;
	}


	/**
	 * @param int $level
	 *
	 * @return int
	 * @throws ParseMemberLevelException
	 */
	public static function parseLevelInt(int $level): int {
		if (!array_key_exists($level, self::$DEF_LEVEL)) {
			$all = implode(', ', array_keys(self::$DEF_LEVEL));
			throw new ParseMemberLevelException('Available levels: ' . $all, 121);
		}

		return $level;
	}


	/**
	 * @param string $levelString
	 *
	 * @return int
	 * @throws ParseMemberLevelException
	 */
	public static function parseLevelString(string $levelString): int {
		$levelString = ucfirst(strtolower($levelString));
		$level = array_search($levelString, Member::$DEF_LEVEL);

		if (!$level) {
			$all = implode(', ', array_values(self::$DEF_LEVEL));
			throw new ParseMemberLevelException('Available levels: ' . $all, 121);
		}

		return (int)$level;
	}

	/**
	 * @param string $typeString
	 *
	 * @return int
	 * @throws UserTypeNotFoundException
	 */
	public static function parseTypeString(string $typeString): int {
		$typeString = strtolower($typeString);
		if (array_key_exists($typeString, Member::$TYPE)) {
			return (int)$typeString;
		}

		$type = array_search($typeString, Member::$TYPE);
		if ($type === false) {
			$all = implode(', ', array_values(self::$TYPE));
			throw new UserTypeNotFoundException('Available types: ' . $all);
		}

		return (int)$type;
	}
}
