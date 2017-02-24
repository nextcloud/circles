<?php
/**
 * Circles - bring cloud-users closer
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

class Member implements \JsonSerializable {

	const LEVEL_NONE = 0;
	const LEVEL_MEMBER = 1;
	const LEVEL_MODERATOR = 6;
	const LEVEL_ADMIN = 8;
	const LEVEL_OWNER = 9;

	const STATUS_INVITED = 'invite';
	const STATUS_REQUEST = 'request';
	const STATUS_MEMBER = 'member';
	const STATUS_BLOCKED = 'blocked';
	const STATUS_KICKED = 'kicked';

	private $circleid;
	private $userid;
	private $level;
	private $levelString;
	private $status;
	private $note;
	private $joined;

	public function __construct() {
	}


	public function setCircleId($circleid) {
		$this->circleid = $circleid;

		return $this;
	}

	public function getCircleId() {
		return $this->circleid;
	}


	public function setUserId($userid) {
		$this->userid = $userid;

		return $this;
	}

	public function getUserId() {
		return $this->userid;
	}


	public function setLevel($level) {
		$this->level = $level;
		$this->setLevelString(self::LevelSring($level));

		return $this;
	}

	public function getLevel() {
		return $this->level;
	}


	public function setLevelString($str) {
		$this->levelString = $str;

		return $this;
	}

	public function getLevelString() {
		return $this->levelString;
	}


	public function setNote($note) {
		$this->note = $note;

		return $this;
	}

	public function getNote() {
		return $this->note;
	}


	public function setStatus($status) {
		$this->status = $status;

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

	public function jsonSerialize() {
		return array(
			'circleid'     => $this->getCircleId(),
			'userid'       => $this->getUserId(),
			'level'        => $this->getLevel(),
			'level_string' => $this->getLevelString(),
			'status'       => $this->getStatus(),
			'joined'       => $this->getJoined()
		);
	}


	public static function fromArray($arr) {
		$member = new Member();

		$member->setCircleId($arr['circle_id']);
		$member->setUserId($arr['user_id']);
		$member->setLevel($arr['level']);
		$member->setStatus($arr['status']);
		$member->setNote($arr['note']);
		$member->setJoined($arr['joined']);

		return $member;
	}


	public static function LevelSring($level) {
		switch ($level) {
			case 0:
				return 'Non-member';
			case 1:
				return 'Member';
			case 6:
				return 'Moderator';
			case 8:
				return 'Admin';
			case 9:
				return 'Owner';
		}
	}


	public function toString() {
		return "toString ?";
	}
}


