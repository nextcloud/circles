<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright 2017
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

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Service\MiscService;
use OCP\IL10N;

class BaseMember implements \JsonSerializable {

	const LEVEL_NONE = 0;
	const LEVEL_MEMBER = 1;
	const LEVEL_MODERATOR = 4;
	const LEVEL_ADMIN = 8;
	const LEVEL_OWNER = 9;

	const STATUS_NONMEMBER = 'Unknown';
	const STATUS_INVITED = 'Invited';
	const STATUS_REQUEST = 'Requesting';
	const STATUS_MEMBER = 'Member';
	const STATUS_BLOCKED = 'Blocked';
	const STATUS_KICKED = 'Kicked';

	const TYPE_USER = 1;
	const TYPE_GROUP = 2;
	const TYPE_MAIL = 3;
	const TYPE_CONTACT = 4;

	/** @var string */
	private $circleUniqueId;

	/** @var string */
	private $circleContactGroupName;

	/** @var IL10N */
	protected $l10n;

	/** @var string */
	private $userId = '';

	/** @var string */
	private $memberId = '';

	/** @var int */
	private $type = self::TYPE_USER;

	/** @var string */
	private $displayName;

	/** @var int */
	private $level;

	/** @var string */
	private $status;

	/** @var string */
	private $contactId = '';

	/** @var array */
	private $contactMeta = [];

	/** @var string */
	private $note;

	/** @var string */
	private $joined;

	/** @var int */
	private $joinedSince;

	/** @var bool */
	protected $broadcasting = true;

	/**
	 * BaseMember constructor.
	 *
	 * @param string $circleUniqueId
	 * @param string $userId
	 * @param int $type
	 */
	public function __construct($userId = '', $type = 0, $circleUniqueId = '') {
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);

		$this->setType($type);
		$this->setUserId($userId);
		$this->setCircleId($circleUniqueId);
		$this->setLevel(Member::LEVEL_NONE);
		$this->setStatus(Member::STATUS_NONMEMBER);
	}


	/**
	 * @param string $circleUniqueId
	 *
	 * @return $this
	 */
	public function setCircleId($circleUniqueId) {
		$this->circleUniqueId = $circleUniqueId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCircleId() {
		return $this->circleUniqueId;
	}


	/**
	 * @param string $circleContactGroupName
	 *
	 * @return $this
	 */
	public function setCircleContactGroupName($circleContactGroupName): self {
		$this->circleContactGroupName = $circleContactGroupName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCircleContactGroupName(): string {
		return $this->circleUniqueId;
	}


	/**
	 * @return int
	 */
	public function getType() {
		return $this->type;
	}

	public function setType($type) {
		$this->type = (int)$type;
	}


	public function getViewerType() {
		if ($this->getType() === 2) {
			return 'group';
		} else {
			return 'user';
		}
	}


	public function setUserId($userId) {
		$this->userId = $userId;
		$this->setDisplayName(MiscService::getDisplay($userId, $this->getType()));

		return $this;
	}

	public function getUserId() {
		return $this->userId;
	}


	public function setMemberId($memberId) {
		$this->memberId = $memberId;

		return $this;
	}

	public function getMemberId() {
		return $this->memberId;
	}


	public function setDisplayName($display) {
		$this->displayName = $display;

		return $this;
	}

	public function getDisplayName() {
		return $this->displayName;
	}


	public function setLevel($level) {
		$this->level = (int)$level;

		return $this;
	}

	public function getLevel() {
		return $this->level;
	}


	public function setNote($note) {
		$this->note = $note;

		return $this;
	}

	public function getNote() {
		return $this->note;
	}


	public function setContactId($contactId) {
		$this->contactId = $contactId;

		return $this;
	}

	public function getContactId() {
		return $this->contactId;
	}


	/**
	 * @param array $contactMeta
	 *
	 * @return $this
	 */
	public function setContactMeta(array $contactMeta): self {
		$this->contactMeta = $contactMeta;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getContactMeta(): array {
		return $this->contactMeta;
	}

	/**
	 * @param string $k
	 * @param string $v
	 *
	 * @return $this
	 */
	public function addContactMeta(string $k, string $v): self {
		$this->contactMeta[$k] = $v;

		return $this;
	}

	/**
	 * @param string $k
	 * @param string $v
	 *
	 * @return $this
	 */
	public function addContactMetaArray(string $k, string $v): self {
		if (!array_key_exists($k, $this->contactMeta)) {
			$this->contactMeta[$k] = [];
		}

		$this->contactMeta[$k][] = $v;

		return $this;
	}

	/**
	 * @param string $k
	 * @param array $v
	 *
	 * @return $this
	 */
	public function setContactMetaArray(string $k, array $v): self {
		$this->contactMeta[$k] = $v;

		return $this;
	}


	/**
	 * @param $status
	 *
	 * @return $this
	 */
	public function setStatus($status) {
		if (is_null($status)) {
			$this->status = self::STATUS_NONMEMBER;
		} else {
			$this->status = $status;
		}

		return $this;
	}

	public function getStatus() {
		return $this->status;
	}


	public function setJoined($joined) {
		$this->joined = $joined;

		return $this;
	}

	public function getJoined() {
		return $this->joined;
	}


	public function getJoinedSince(): int {
		return $this->joinedSince;
	}

	public function setJoinedSince(int $since) {
		$this->joinedSince = $since;
	}


	public function isLevel($level) {
		return ($this->getLevel() >= $level);
	}


	public function isAlmostMember() {
		return ($this->getStatus() === Member::STATUS_INVITED
				|| $this->getStatus() === Member::STATUS_REQUEST);
	}


	protected function setAsAMember($level = 1) {
		$this->setStatus(Member::STATUS_MEMBER);
		$this->setLevel($level);
	}


	/**
	 * @param $arr
	 *
	 * @return null|Member
	 */
	public static function fromArray($arr) {
		if ($arr === null) {
			return null;
		}

		$member = new Member();
		$member->setCircleId($arr['circle_id']);
		$member->setLevel($arr['level']);

		$member->setType(MiscService::get($arr, 'user_type'));
		$member->setType(MiscService::get($arr, 'type', $member->getType()));

		$member->setUserId($arr['user_id']);
		$member->setStatus($arr['status']);
		$member->setNote($arr['note']);
		$member->setJoined($arr['joined']);

		return $member;
	}


	/**
	 * @param $json
	 *
	 * @return Member
	 */
	public static function fromJSON($json) {
		return self::fromArray(json_decode($json, true));
	}


	public function jsonSerialize() {
		return [
			'circle_id'    => $this->getCircleId(),
			'member_id'    => $this->getMemberId(),
			'user_id'      => $this->getUserId(),
			'user_type'    => $this->getType(),
			'display_name' => $this->getDisplayName(),
			'contact_id'   => $this->getContactId(),
			'level'        => $this->getLevel(),
			'level_string' => $this->getLevelString(),
			'status'       => $this->getStatus(),
			'note'         => $this->getNote(),
			'joined'       => $this->getJoined()
		];
	}

	public function getLevelString() {
		switch ($this->getLevel()) {
			case self::LEVEL_NONE:
				return 'Not a member';
			case self::LEVEL_MEMBER:
				return 'Member';
			case self::LEVEL_MODERATOR:
				return 'Moderator';
			case self::LEVEL_ADMIN:
				return 'Admin';
			case self::LEVEL_OWNER:
				return 'Owner';
		}

		return 'none';
	}


	public function getTypeString() {
		switch ($this->getType()) {
			case self::TYPE_USER:
				return 'Local Member';
			case self::TYPE_GROUP:
				return 'Group';
			case self::TYPE_MAIL:
				return 'Mail address';
			case self::TYPE_CONTACT:
				return 'Contact';
		}

		return 'none';
	}

	public function getTypeName() {
		switch ($this->getType()) {
			case self::TYPE_USER:
			case self::TYPE_MAIL:
			case self::TYPE_CONTACT:
				return 'user';
			case self::TYPE_GROUP:
				return 'user-group';
		}

		return 'none';
	}
}
