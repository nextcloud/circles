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

class BaseMember {

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

	/** @var int */
	private $circleId;


	/** @var L10N */
	protected $l10n;

	/** @var string */
	private $userId;

	/** @var int */
	private $level;

	/** @var string */
	private $status;

	/** @var string */
	private $note;

	/** @var string */
	private $joined;

	public function __construct($l10n, $userId = '', $circleId = -1) {
		$this->l10n = $l10n;

		if ($userId !== '') {
			$this->setUserId($userId);
		}
		if ($circleId > -1) {
			$this->setCircleId($circleId);
		}
		$this->setLevel(Member::LEVEL_NONE);
		$this->setStatus(Member::STATUS_NONMEMBER);
	}


	public function setCircleId($circleId) {
		$this->circleId = (int)$circleId;

		return $this;
	}

	public function getCircleId() {
		return $this->circleId;
	}


	public function setUserId($userId) {
		$this->userId = $userId;

		return $this;
	}

	public function getUserId() {
		return $this->userId;
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
	 * @param array $arr
	 *
	 * @return BaseMember
	 */
	public function fromArray($arr) {
		$this->setCircleId($arr['circle_id']);
		$this->setUserId($arr['user_id']);
		$this->setLevel($arr['level']);
		$this->setStatus($arr['status']);
		if (key_exists('note', $arr)) {
			$this->setNote($arr['note']);
		}
		if (key_exists('joined', $arr)) {
			$this->setJoined($arr['joined']);
		}

		return $this;
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
