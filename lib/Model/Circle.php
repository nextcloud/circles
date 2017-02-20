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

class Circle implements \JsonSerializable {

	const CIRCLES_PERSONAL = 1;
	const CIRCLES_HIDDEN = 2;
	const CIRCLES_PRIVATE = 4;
	const CIRCLES_PUBLIC = 8;

	const CIRCLES_ALL = 15;

	private $id;
	private $name;
	private $owner;
	private $user;
	private $description;
	private $type;
	private $typeString;
	private $creation;
	private $count;
	private $members;

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
		$this->setTypeString(self::TypeSring($type));

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
		$circle->setCount($arr['count']);

		$owner = new Member();
		$owner->setUserId($arr['owner']);
		$circle->setOwner($owner);

		$user = new Member();
		$user->setStatus($arr['status']);
		$user->setLevel($arr['level']);
		$user->setJoined($arr['joined']);
		$circle->setUser($user);

		return $circle;
	}

	public static function TypeSring($type) {
		switch ($type) {
			case 1:
				return 'Personal';
			case 2:
				return 'Hidden';
			case 4:
				return 'Private';
			case 8:
				return 'Public';
		}
	}
}


