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

class BaseCircle {

	const CIRCLES_SETTINGS_DEFAULT = [
		'allow_links'       => 'false',
		'allow_links_auto'  => 'false',
		'allow_links_files' => 'false'
	];

	const CIRCLES_PERSONAL = 1;
	const CIRCLES_SECRET = 2;
	const CIRCLES_CLOSED = 4;
	const CIRCLES_PUBLIC = 8;

	const CIRCLES_ALL = 15;

	const UNIQUEID_SHORT_LENGTH = 14;

	/** @var int */
	private $id;

	/** @var L10N */
	protected $l10n;

	/** @var string */
	private $uniqueId;

	/** @var string */
	private $name;

	/** @var Member */
	private $owner;

	/** @var Member */
	private $viewer;

	/** @var Member */
	private $viewerGroup;

	/** @var string */
	private $description = '';

	/** @var array */
	private $settings = [];

	/** @var int */
	private $type;

	/** @var string */
	private $creation;

	/** @var Member[] */
	private $members;

	/** @var Member[] */
	private $groups;

	/** @var FederatedLink[] */
	private $links;

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


	/**
	 * @param string $uniqueId
	 *
	 * @return $this
	 */
	public function setUniqueId($uniqueId) {
		$this->uniqueId = (string)$uniqueId;

		return $this;
	}

	/**
	 * @param bool $full
	 *
	 * @return string
	 */
	public function getUniqueId($full = false) {
		if ($full) {
			return $this->uniqueId;
		}

		return substr($this->uniqueId, 0, self::UNIQUEID_SHORT_LENGTH);
	}


	public function generateUniqueId() {
		$uniqueId = bin2hex(openssl_random_pseudo_bytes(24));
		$this->setUniqueId($uniqueId);
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


	/**
	 * @return Member
	 */
	public function getViewer() {
		return $this->viewer;
	}

	public function setViewer($user) {
		$this->viewer = $user;

		return $this;
	}


	/**
	 * @return Member
	 */
	public function getGroupViewer() {
		return $this->viewerGroup;
	}

	public function setGroupViewer($group) {
		$this->viewerGroup = $group;

		return $this;
	}


	/**
	 * @return Member
	 */
	public function getHigherViewer() {
		if ($this->getGroupViewer() === null) {
			return $this->getViewer();
		}

		if ($this->getViewer() === null) {
			return $this->getGroupViewer();
		}

		if ($this->getGroupViewer()
				 ->getLevel() > $this->getViewer()
									 ->getLevel()
		) {
			return $this->getGroupViewer();
		}

		return $this->getViewer();
	}


	public function setDescription($description) {
		$this->description = $description;

		return $this;
	}

	public function getDescription() {
		return $this->description;
	}


	public function setSettings($settings) {
		if (is_array($settings)) {
			$this->settings = $settings;
		} else if (is_string($settings)) {
			$this->settings = (array)json_decode($settings, true);
		}

		return $this;
	}

	public function getSettings($json = false) {

		if ($json) {
			return json_encode($this->settings);
		}

		$settings = $this->settings;
		if ($settings === null) {
			$settings = [];
		}

		$ak = array_keys(self::CIRCLES_SETTINGS_DEFAULT);
		foreach ($ak AS $k) {
			if (!key_exists($k, $settings)) {
				$settings[$k] = self::CIRCLES_SETTINGS_DEFAULT[$k];
			}
		}

		return $settings;
	}


	public function setSetting($k, $v) {
		switch ($k) {
			case 'circle_name':
				$this->setName($v);
				break;

			case 'circle_desc':
				$this->setDescription($v);
				break;

			default:
				$this->settings[$k] = $v;
				break;
		}
	}


	/**
	 * @param string $k
	 *
	 * @return string|null
	 */
	public function getSetting($k) {
		if (key_exists($k, $this->settings)) {
			return $this->settings[$k];
		}
		if (key_exists($k, (array)self::CIRCLES_SETTINGS_DEFAULT)) {
			return self::CIRCLES_SETTINGS_DEFAULT[$k];
		}

		return null;
	}


	public function setType($type) {
		$this->type = self::typeInt($type);

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


	public function setGroups($groups) {
		$this->groups = $groups;

		return $this;
	}

	public function getGroups() {
		return $this->groups;
	}


	public function setLinks($links) {
		$this->links = $links;

		return $this;
	}

	public function getLinks() {
		return $this->links;
	}


//	public function getRemote() {
//		return $this->remote;
//	}
//
//	public function addRemote($link) {
//		array_push($this->remote, $link);
//	}
//
//	public function getRemoteFromToken($token) {
//		foreach ($this->links AS $link) {
//			if ($link->getToken() === $token) {
//				return $link;
//			}
//		}
//
//		return null;
//	}
//
//	public function getRemoteFromAddressAndId($address, $id) {
//		foreach ($this->links AS $link) {
//			if ($link->getAddress() === $address && $link->getUniqueId() === $id) {
//				return $link;
//			}
//		}
//
//		return null;
//	}


	public static function typeInt($type) {

		if (is_numeric($type)) {
			return (int)$type;
		}

		switch ($type) {
			case 'Personal':
				return self::CIRCLES_PERSONAL;
			case 'Closed':
				return self::CIRCLES_CLOSED;
			case 'Secret':
				return self::CIRCLES_SECRET;
			case 'Public':
				return self::CIRCLES_PUBLIC;
		}

		return 0;
	}
}