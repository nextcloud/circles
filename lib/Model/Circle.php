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

use OCA\Circles\Exceptions\CircleTypeNotValid;

class Circle extends BaseCircle implements \JsonSerializable {

	const CIRCLES_PERSONAL = 1;
	const CIRCLES_HIDDEN = 2;
	const CIRCLES_PRIVATE = 4;
	const CIRCLES_PUBLIC = 8;

	const CIRCLES_ALL = 15;

	public function getTypeString() {
		switch ($this->getType()) {
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

	public function getTypeLongString() {
		return self::typeLongString($this->getType());
	}


	public function getInfo() {
		return $this->getTypeLongString();
	}


	public function jsonSerialize() {
		return array(
			'id'             => $this->getId(),
			'name'           => $this->getName(),
			'owner'          => $this->getOwner(),
			'user'           => $this->getUser(),
			'description'    => $this->getDescription(),
			'type'           => $this->getTypeString(),
			'creation'       => $this->getCreation(),
			'typeString'     => $this->getTypeString(),
			'typeLongString' => $this->getTypeLongString(),
			'members'        => $this->getMembers(),
			'links'          => $this->getRemote()
		);
	}

	/**
	 * set all infos from an Array.
	 *
	 * @param $arr
	 *
	 * @return $this
	 */
	public function fromArray($arr) {
		$this->setId($arr['id']);
		$this->setName($arr['name']);
		$this->setUniqueId($arr['unique_id']);
		$this->setDescription($arr['description']);
		$this->setType($arr['type']);
		$this->setCreation($arr['creation']);

		$this->setOwnerMemberFromArray($arr);
		$this->setUserMemberFromArray($arr);

		return $this;
	}


	/**
	 * set Owner Infos from Array
	 *
	 * @param $array
	 */
	private function setOwnerMemberFromArray(& $array) {
		if (key_exists('owner', $array)) {
			$owner = new Member($this->l10n);
			$owner->setUserId($array['owner']);
			$this->setOwner($owner);
		}
	}


	/**
	 * set User Infos from Array
	 *
	 * @param $array
	 */
	private function setUserMemberFromArray(& $array) {
		if (key_exists('status', $array)
			&& key_exists('level', $array)
			&& key_exists('joined', $array)
		) {
			$user = new Member($this->l10n);
			$user->setStatus($array['status']);
			$user->setLevel($array['level']);
			$user->setJoined($array['joined']);
			$this->setUser($user);
		}
	}


	/**
	 * @throws CircleTypeNotValid
	 */
	public function cantBePersonal() {
		if ($this->getType() === self::CIRCLES_PERSONAL) {
			throw new CircleTypeNotValid(
				$this->l10n->t("This option is not available to personal circles")
			);
		}
	}


	/**
	 * @param $type
	 *
	 * @return string
	 */
	public static function typeLongString($type) {
		switch ($type) {
			case self::CIRCLES_PERSONAL:
				return 'Personal circle';
			case self::CIRCLES_HIDDEN:
				return 'Hidden circle';
			case self::CIRCLES_PRIVATE:
				return 'Private circle';
			case self::CIRCLES_PUBLIC:
				return 'Public circle';
			case self::CIRCLES_ALL:
				return 'All circles';
		}

		return 'none';
	}

}


