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

class Circle implements \JsonSerializable {

	const CIRCLES_PERSONAL = 1;
	const CIRCLES_HIDDEN = 2;
	const CIRCLES_PRIVATE = 4;
	const CIRCLES_PUBLIC = 8;

	const CIRCLES_ALL = 15;

	private $id;
	private $name;

	/** @var Member */
	private $owner;

	/** @var Member */
	private $user;
	private $description;
	private $type;
	private $typeString;
	private $typeLongString;
	private $creation;
	private $count;
	private $members;
	private $info;

	public function __construct() {

	}


	public function setId($id) {
		$this->id = $id;

		return $this;
	}

	public function getId() {
		return $this->id;
	}


	public function setName($name) {
		$this->name = $name;

		return $this;
	}

	public function getName() {
		return $this->name;
	}


	public function getOwner() {
		return $this->owner;
	}

	public function setOwner($owner) {
		$this->owner = $owner;
	}


	public function getUser() {
		return $this->user;
	}

	public function setUser($user) {
		$this->user = $user;
	}


	public function setDescription($description) {
		$this->description = $description;

		return $this;
	}

	public function getDescription() {
		return $this->description;
	}


	public function setType($type) {
		$this->type = (int)$type;
		$this->setTypeString(self::TypeString($type));
		$this->setTypeLongString(self::TypeLongString($type));
		$this->setInfo($this->getTypeLongString());

		return $this;
	}

	public function getType() {
		return $this->type;
	}

	public function setTypeString($str) {
		$this->typeString = $str;

		return $this;
	}

	public function getTypeString() {
		return $this->typeString;
	}

	public function setTypeLongString($str) {
		$this->typeLongString = $str;
	}

	public function getTypeLongString() {
		return $this->typeLongString;
	}


	public function setInfo($str) {
		$this->info = $str;
	}

	public function getInfo() {
		return $this->info;
	}


	public function setCreation($creation) {
		$this->creation = $creation;

		return $this;
	}

	public function getCreation() {
		return $this->creation;
	}


	public function setCount($count) {
		$this->count = $count;

		return $this;
	}

	public function getCount() {
		return $this->count;
	}

	public function setMembers($members) {
		$this->members = $members;

		return $this;
	}

	public function getMembers() {
		return $this->members;
	}


	public function toString() {
		return "toString ?";
	}

	public function jsonSerialize() {
		return array(
			'id'          => $this->getId(),
			'name'        => $this->getName(),
			'owner'       => $this->getOwner(),
			'user'        => $this->getUser(),
			'description' => $this->getDescription(),
			'type'        => $this->getTypeString(),
			'creation'    => $this->getCreation(),
			'count'       => $this->getCount(),
			'members'     => $this->getMembers()
		);
	}

	public static function fromArray($arr) {
		$circle = new Circle();
		$circle->setId($arr['id']);

		$circle->setName($arr['name']);
		$circle->setDescription($arr['description']);
		$circle->setType($arr['type']);
		$circle->setCreation($arr['creation']);
		if (key_exists('count', $arr)) {
			$circle->setCount($arr['count']);
		}

		if (key_exists('owner', $arr)) {
			$owner = new Member();
			$owner->setUserId($arr['owner']);
			$circle->setOwner($owner);
		}

		if (key_exists('status', $arr)
			&& key_exists('level', $arr)
			&& key_exists('joined', $arr)
		) {
			$user = new Member();
			$user->setStatus($arr['status']);
			$user->setLevel($arr['level']);
			$user->setJoined($arr['joined']);
			$circle->setUser($user);
		}

		return $circle;
	}

	public static function TypeString($type) {
		switch ($type) {
			case self::CIRCLES_PERSONAL:
				return 'Personal';
			case self::CIRCLES_HIDDEN:
				return 'Hidden';
			case self::CIRCLES_PRIVATE:
				return 'Private';
			case self::CIRCLES_PUBLIC:
				return 'Public';
			case self::CIRCLES_ALL:
				return 'All';
		}

		return 'none';
	}

	/**
	 * @param $type
	 *
	 * @return string
	 */
	public static function TypeLongString($type) {
		switch ($type) {
			case self::CIRCLES_PERSONAL:
				return 'Personal Circle';
			case self::CIRCLES_HIDDEN:
				return 'Hidden Circle';
			case self::CIRCLES_PRIVATE:
				return 'Private Circle';
			case self::CIRCLES_PUBLIC:
				return 'Public Circle';
			case self::CIRCLES_ALL:
				return 'All Circles';
		}

		return 'none';
	}

}


