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

use OC\L10N\L10N;
use OCA\Circles\Service\MiscService;

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

	/** @var string */
	private $circleUniqueId;

	/** @var L10N */
	protected $l10n;

	/** @var string */
	private $userId = '';

	/** @var int */
	private $type = self::TYPE_USER;

	/** @var string */
	private $displayName;

	/** @var int */
	private $level;

	/** @var string */
	private $status;

	/** @var string */
	private $note;

	/** @var string */
	private $joined;

	/**
	 * BaseMember constructor.
	 *
	 * @param string $circleUniqueId
	 * @param string $userId
	 * @param int $type
	 */
	public function __construct($userId = '', $type = 0, $circleUniqueId = '') {
		$this->l10n = \OC::$server->getL10N('circles');

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

		if ($this->getType() === Member::TYPE_USER) {
			$this->setDisplayName(MiscService::staticGetDisplayName($userId, true));
		} else {
			$this->setDisplayName($userId);
		}

		return $this;
	}

	public function getUserId() {
		return $this->userId;
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
		$member = new Member();
		$member->setCircleId($arr['circle_id']);
		$member->setLevel($arr['level']);
		$member->setType($arr['type']);
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
		return array(
			'circle_id'    => $this->getCircleId(),
			'user_id'      => $this->getUserId(),
			'type'         => $this->getType(),
			'display_name' => $this->getDisplayName(),
			'level'        => $this->getLevel(),
			'level_string' => $this->getLevelString(),
			'status'       => $this->getStatus(),
			'note'         => $this->getNote(),
			'joined'       => $this->getJoined()
		);
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
}
