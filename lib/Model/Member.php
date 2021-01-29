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
use daita\MySmallPhpTools\Model\Nextcloud\nc21\INC21Convert;
use daita\MySmallPhpTools\Traits\TArrayTools;
use DateTime;
use JsonSerializable;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\IMember;


/**
 * Class Member
 *
 * @package OCA\Circles\Model
 */
class Member extends ManagedModel implements IMember, INC21Convert, INC21QueryRow, JsonSerializable {


	use TArrayTools;


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

	const ID_LENGTH = 14;

	static $DEF_LEVEL = [
		1 => 'Member',
		4 => 'Moderator',
		8 => 'Admin',
		9 => 'Owner'
	];


	/** @var string */
	private $id = '';

	/** @var string */
	private $circleId = '';

	/** @var string */
	private $userId;

	/** @var int */
	private $userType;

	/** @var string */
	private $instance;

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

	/** @var int */
	private $joined = 0;


	/**
	 * Member constructor.
	 *
	 * @param string $userId
	 * @param int $type
	 * @param string $instance
	 */
	public function __construct(string $userId = '', int $type = self::TYPE_USER, $instance = '') {
		$this->userId = $userId;
		$this->userType = $type;
		$this->instance = $instance;
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
	 *
	 * @return bool
	 */
	public function compareWith(Member $member): bool {
		if ($this->getId() !== $member->getId()
			|| $this->getCircleId() !== $member->getCircleId()
			|| $this->getUserId() !== $member->getUserId()
			|| $this->getUserType() <> $member->getUserType()
			|| $this->getLevel() <> $member->getLevel()
			|| $this->getStatus() !== $member->getStatus()
			|| $this->getInstance() !== $member->getInstance()) {
			return false;
		}

		return true;
	}


	/**
	 * @param IMember $viewer
	 *
	 * @return self
	 */
	public function importFromCurrentUser(IMember $viewer): self {
		$this->setUserId($viewer->getUserId());
		$this->setUserType($viewer->getUserType());
		$this->setInstance($viewer->getInstance());

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function import(array $data): INC21Convert {
		$this->setId($this->get('id', $data));
		$this->setCircleId($this->get('circle_id', $data));
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

		return $this;
	}


	/**
	 * @return string[]
	 */
	public function jsonSerialize(): array {
		return array_filter(
			[
				'id'            => $this->getId(),
				'circle_id'     => $this->getCircleId(),
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
		};

		$this->setId($this->get($prefix . 'member_id', $data));
		$this->setCircleId($this->get($prefix . 'circle_id', $data));
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

		return $this;
	}

}

