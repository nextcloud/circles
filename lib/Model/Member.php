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
use daita\MySmallPhpTools\IDeserializable;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Deserialize;
use daita\MySmallPhpTools\Traits\TArrayTools;
use DateTime;
use JsonSerializable;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\IFederatedUser;


/**
 * Class Member
 *
 * @package OCA\Circles\Model
 */
class Member extends ManagedModel implements IFederatedUser, IDeserializable, INC21QueryRow, JsonSerializable {


	use TArrayTools;
	use TNC21Deserialize;


	const LEVEL_NONE = 0;
	const LEVEL_MEMBER = 1;
	const LEVEL_MODERATOR = 4;
	const LEVEL_ADMIN = 8;
	const LEVEL_OWNER = 9;

	const TYPE_CIRCLE = 16;
	const TYPE_USER = 1;
	const TYPE_GROUP = 2;
	const TYPE_MAIL = 3;
	const TYPE_CONTACT = 4;

	const STATUS_NONMEMBER = 'Unknown';
	const STATUS_INVITED = 'Invited';
	const STATUS_REQUEST = 'Requesting';
	const STATUS_MEMBER = 'Member';
	const STATUS_BLOCKED = 'Blocked';
	const STATUS_KICKED = 'Kicked';

	public static $DEF_LEVEL = [
		1 => 'Member',
		4 => 'Moderator',
		8 => 'Admin',
		9 => 'Owner'
	];

	public static $DEF_TYPE = [
		1  => 'local',
		16 => 'circle',
		3  => 'mail',
		4  => 'contact',
	];

	/** @var string */
	private $id = '';

	/** @var string */
	private $circleId = '';

	/** @var string */
	private $singleId = '';

	/** @var string */
	private $userId = '';

	/** @var int */
	private $userType = self::TYPE_USER;

	/** @var string */
	private $instance = '';

	/** @var int */
	private $level = 0;

	/** @var string */
	private $status = 'Unknown';

	/** @var string */
	private $note = '';

	/** @var string */
	private $cachedName = '';

	/** @var int */
	private $cachedUpdate = 0;

	/** @var string */
	private $contactId = '';

	/** @var string */
	private $contactMeta = '';

	/** @var Circle */
	private $circle;


	/** @var int */
	private $joined = 0;


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
	 * @param string $note
	 *
	 * @return Member
	 */
	public function setNote(string $note): self {
		$this->note = $note;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getNote(): string {
		return $this->note;
	}


	/**
	 * @param string $cachedName
	 *
	 * @return Member
	 */
	public function setCachedName(string $cachedName): self {
		$this->cachedName = $cachedName;

		return $this;
	}


	/**
	 * @param int $cachedUpdate
	 *
	 * @return Member
	 */
	public function setCachedUpdate(int $cachedUpdate): self {
		$this->cachedUpdate = $cachedUpdate;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCachedUpdate(): int {
		return $this->cachedUpdate;
	}


	/**
	 * @return string
	 */
	public function getCachedName(): string {
		return $this->cachedName;
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
	public function isMember(): bool {
		return ($this->level > 0);
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
			//			|| $this->getSingleId() !== $member->getSingleId()
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
		if ($this->get('user_id', $data) === '') {
			throw new InvalidItemException();
		}

		$this->setId($this->get('id', $data));
		$this->setCircleId($this->get('circle_id', $data));
		$this->setSingleId($this->get('single_id', $data));
		$this->setUserId($this->get('user_id', $data));
		$this->setUserType($this->getInt('user_type', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setLevel($this->getInt('level', $data));
		$this->setStatus($this->get('status', $data));
		$this->setCachedName($this->get('cached_name', $data));
		$this->setCachedUpdate($this->getInt('cached_update', $data));
		$this->setNote($this->get('note', $data));
		$this->setContactId($this->get('contact_id', $data));
		$this->setContactMeta($this->get('contact_meta', $data));
		$this->setJoined($this->getInt('joined', $data));

		try {
			/** @var Circle $circle */
			$circle = $this->deserialize($this->getArray('circle', $data), Circle::class);
			$this->setCircle($circle);
		} catch (InvalidItemException $e) {
		}

		return $this;
	}


	/**
	 * @return string[]
	 */
	public function jsonSerialize(): array {
		$arr = array_filter(
			[
				'id'            => $this->getId(),
				'circle_id'     => $this->getCircleId(),
				'single_id'     => $this->getSingleId(),
				'user_id'       => $this->getUserId(),
				'user_type'     => $this->getUserType(),
				'instance'      => $this->getInstance(),
				'level'         => $this->getLevel(),
				'status'        => $this->getStatus(),
				'cached_name'   => $this->getCachedName(),
				'cached_update' => $this->getCachedUpdate(),
				'note'          => $this->getNote(),
				'contact_id'    => $this->getContactId(),
				'contact_meta'  => $this->getContactMeta(),
				'joined'        => $this->getJoined()
			]
		);

		if ($this->hasCircle()) {
			$arr['circle'] = $this->getCircle();
		}

		return $arr;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return INC21QueryRow
	 * @throws MemberNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): INC21QueryRow {
		if (!array_key_exists($prefix . 'member_id', $data)) {
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
		$this->setCachedName($this->get($prefix . 'cached_name', $data));
		$this->setNote($this->get($prefix . 'note', $data));
		$this->setContactId($this->get($prefix . 'contact_id', $data));
		$this->setContactMeta($this->get($prefix . 'contact_meta', $data));

		$cachedUpdate = $this->get($prefix . 'cached_update', $data);
		if ($cachedUpdate !== '') {
			$this->setCachedUpdate(DateTime::createFromFormat('Y-m-d H:i:s', $cachedUpdate)->getTimestamp());
		}

		$joined = $this->get($prefix . 'joined', $data);
		if ($joined !== '') {
			$this->setJoined(DateTime::createFromFormat('Y-m-d H:i:s', $joined)->getTimestamp());
		}

		if ($this->getInstance() === '') {
			$this->setInstance($this->get('_params.local', $data));
		}

		if ($prefix === '') {
			$this->getManager()->importCircleFromDatabase($this, $data);
		}

		return $this;
	}


	/**
	 * @param string $levelString
	 *
	 * @return int
	 * @throws MemberLevelException
	 */
	public static function parseLevelString(string $levelString): int {
		$levelString = ucfirst(strtolower($levelString));
		$level = array_search($levelString, Member::$DEF_LEVEL);

		if (!$level) {
			$all = implode(', ', array_values(self::$DEF_LEVEL));
			throw new MemberLevelException('Available levels: ' . $all);
		}

		return (int)$level;
	}

}

