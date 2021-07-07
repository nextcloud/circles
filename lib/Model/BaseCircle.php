<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

use OC;
use OCA\Circles\AppInfo\Application;
use OCP\IL10N;

class BaseCircle {
	public const CIRCLES_SETTINGS_DEFAULT = [
		'password_enforcement' => 'false',
		'password_single' => '',
		'allow_links' => 'false',
		'allow_links_auto' => 'false',
		'allow_links_files' => 'false'
	];

	public const CIRCLES_PERSONAL = 1;
	public const CIRCLES_SECRET = 2;
	public const CIRCLES_CLOSED = 4;
	public const CIRCLES_PUBLIC = 8;

	public const CIRCLES_ALL = 15;

	public const SHORT_UNIQUE_ID_LENGTH = 14;

	/** @var int */
	private $id;

	/** @var IL10N */
	protected $l10n;

	/** @var string */
	private $uniqueId = '';

	/** @var string */
	private $name;

	/** @var string */
	private $altName = '';

	/** @var DeprecatedMember */
	private $owner;

	/** @var DeprecatedMember */
	private $viewer = null;

	/** @var DeprecatedMember */
	private $viewerGroup;

	/** @var string */
	private $description = '';

	/** @var array */
	private $settings = [];

	/** @var string */
	private $passwordSingle = '';

	/** @var int */
	private $type;

	/** @var string */
	private $contactGroupName = '';

	/** @var int */
	private $contactAddressBook = 0;


	/** @var string */
	private $creation;

	/** @var DeprecatedMember[] */
	private $members;

	/** @var DeprecatedMember[] */
	private $groups;

	/** @var FederatedLink[] */
	private $links;

	public function __construct($type = -1, $name = '') {
		$this->l10n = OC::$server->getL10N(Application::APP_ID);

		if ($type > -1) {
			$this->type = $type;
		}
		if ($name !== '') {
			$this->name = $name;
		}
	}

	/**
	 * @param integer $id
	 *
	 * @return BaseCircle
	 */
	public function setId($id) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return integer
	 */
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

		return substr($this->uniqueId, 0, self::SHORT_UNIQUE_ID_LENGTH);
	}

	public function generateUniqueId() {
		$uniqueId = bin2hex(openssl_random_pseudo_bytes(24));
		$this->setUniqueId($uniqueId);
		$this->setId($this->getUniqueId());
	}

	/**
	 * @param string $name
	 *
	 * @return BaseCircle
	 */
	public function setName($name) {
		$this->name = $name;

		return $this;
	}

	/**
	 * @param bool $real
	 *
	 * @return string
	 */
	public function getName(bool $real = false) {
		if (!$real && $this->altName !== '') {
			return $this->altName;
		}

		return $this->name;
	}

	/**
	 * @param string $name
	 *
	 * @return BaseCircle
	 */
	public function setAltName($name) {
		$this->altName = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAltName() {
		return $this->altName;
	}

	/**
	 * @return DeprecatedMember
	 */
	public function getOwner() {
		return $this->owner;
	}

	/**
	 * @param DeprecatedMember $owner
	 *
	 * @return BaseCircle
	 */
	public function setOwner($owner) {
		$this->owner = $owner;

		return $this;
	}


	/**
	 * @return DeprecatedMember
	 */
	public function getViewer() {
		return $this->viewer;
	}

	/**
	 * @param DeprecatedMember $user
	 *
	 * @return BaseCircle
	 */
	public function setViewer($user) {
		$this->viewer = $user;

		return $this;
	}


	public function hasViewer(): bool {
		return ($this->viewer !== null);
	}


	/**
	 * @return DeprecatedMember
	 */
	public function getGroupViewer() {
		return $this->viewerGroup;
	}

	/**
	 * @param DeprecatedMember $group
	 *
	 * @return BaseCircle
	 */
	public function setGroupViewer($group) {
		$this->viewerGroup = $group;

		return $this;
	}

	/**
	 * @return DeprecatedMember
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


	/**
	 * @param string $description
	 *
	 * @return BaseCircle
	 */
	public function setDescription($description) {
		$this->description = $description;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}


	/**
	 * @param int $contactAddressBook
	 *
	 * @return BaseCircle
	 */
	public function setContactAddressBook(int $contactAddressBook) {
		$this->contactAddressBook = $contactAddressBook;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getContactAddressBook() {
		return $this->contactAddressBook;
	}


	/**
	 * @param string $contactGroupName
	 *
	 * @return BaseCircle
	 */
	public function setContactGroupName($contactGroupName) {
		$this->contactGroupName = $contactGroupName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContactGroupName() {
		return $this->contactGroupName;
	}


	/**
	 * @param string|array $settings
	 * @param bool $all
	 *
	 * @return $this
	 */
	public function setSettings($settings, bool $all = false) {
		if (is_array($settings)) {
			$this->settings = $settings;
		} elseif (is_string($settings)) {
			$this->settings = (array)json_decode($settings, true);
		}

		if (array_key_exists('password_single', $this->settings)) {
			$this->setPasswordSingle($this->settings['password_single']);
			if (!$all) {
				$this->settings['password_single'] = '';
			}
		}

		return $this;
	}

	/**
	 * @param bool $json
	 *
	 * @return array|string
	 */
	public function getSettings($json = false) {
		if ($json) {
			return json_encode($this->settings);
		}

		$settings = $this->settings;
		if ($settings === null) {
			$settings = [];
		}

		$ak = array_keys(self::CIRCLES_SETTINGS_DEFAULT);
		foreach ($ak as $k) {
			if (!key_exists($k, $settings)) {
				$settings[$k] = self::CIRCLES_SETTINGS_DEFAULT[$k];
			}
		}

		return $settings;
	}


	/**
	 * @param string $k
	 * @param mixed $v
	 */
	public function setSetting($k, $v) {
		switch ($k) {
			case 'circle_name':
				$this->setName($v);
				break;

			case 'circle_alt_name':
				$this->setAltName($v);
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


	/**
	 * @return string
	 */
	public function getPasswordSingle(): string {
		return $this->passwordSingle;
	}

	/**
	 * @param string $passwordSingle
	 */
	public function setPasswordSingle(string $passwordSingle): void {
		$this->passwordSingle = $passwordSingle;
	}


	/**
	 *
	 * @param string $type
	 *
	 * @return \OCA\Circles\Model\BaseCircle
	 */
	public function setType($type) {
		$this->type = self::typeInt($type);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $creation
	 *
	 * @return \OCA\Circles\Model\BaseCircle
	 */
	public function setCreation($creation) {
		$this->creation = $creation;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCreation() {
		return $this->creation;
	}

	/**
	 * @param array $members
	 *
	 * @return BaseCircle
	 */
	public function setMembers($members) {
		$this->members = $members;

		return $this;
	}

	/**
	 * @return DeprecatedMember[]
	 */
	public function getMembers() {
		return $this->members;
	}

	/**
	 * @param array $groups
	 *
	 * @return BaseCircle
	 */
	public function setGroups($groups) {
		$this->groups = $groups;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getGroups() {
		return $this->groups;
	}

	/**
	 * @param array $links
	 *
	 * @return BaseCircle
	 */
	public function setLinks($links) {
		$this->links = $links;

		return $this;
	}

	/**
	 * @return array
	 */
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

	/**
	 * @param integer|string $type
	 *
	 * @return integer
	 */
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
