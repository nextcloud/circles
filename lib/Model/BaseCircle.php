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

class BaseCircle {

	/** @var int */
	private $id;


	/** @var L10N */
	protected $l10n;

	/** @var string */
	private $name;

	/** @var Member */
	private $owner;

	/** @var Member */
	private $user;

	/** @var string */
	private $description;

	/** @var int */
	private $type;

	/** @var string */
	private $creation;

	/** @var Member[] */
	private $members;


	public function __construct($l10n, $type = -1, $name = '') {
		$this->l10n = $l10n;

		if ($type > -1) {
			$this->type = $type;
		}
		if ($name !== '') {
			$this->name = $name;
		}
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

		return $this;
	}


	public function getUser() {
		return $this->user;
	}

	public function setUser($user) {
		$this->user = $user;

		return $this;
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

		return $this;
	}

	public function getType() {
		return $this->type;
	}


	public function setCreation($creation) {
		$this->creation = $creation;

		return $this;
	}

	public function getCreation() {
		return $this->creation;
	}


	public function setMembers($members) {
		$this->members = $members;

		return $this;
	}

	public function getMembers() {
		return $this->members;
	}


}