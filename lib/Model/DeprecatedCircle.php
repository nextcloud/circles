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

use OCA\Circles\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;

class DeprecatedCircle extends BaseCircle implements JsonSerializable {
	use TArrayTools;


	/** @var bool */
	private $fullJson = false;

	/** @var bool */
	private $lightJson = false;


	public function getTypeString() {
		switch ($this->getType()) {
			case self::CIRCLES_PERSONAL:
				return 'Personal';
			case self::CIRCLES_SECRET:
				return 'Secret';
			case self::CIRCLES_CLOSED:
				return 'Closed';
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


	/**
	 * @param bool $fullJson
	 *
	 * @return $this
	 */
	public function setFullJson(bool $fullJson): self {
		$this->fullJson = $fullJson;

		return $this;
	}


	public function jsonSerialize() {
		$json = [
			'id' => $this->getId(),
			'name' => $this->getName(true),
			'alt_name' => $this->getAltName(),
			'owner' => $this->getOwner(),
			'user' => $this->getViewer(),
			'group' => $this->getGroupViewer(),
			'viewer' => $this->getHigherViewer(),
			'description' => $this->getDescription(),
			'settings' => $this->getSettings(),
			'type' => $this->getType(),
			'creation' => $this->getCreation(),
			'type_string' => $this->getTypeString(),
			'type_long_string' => $this->getTypeLongString(),
			'unique_id' => $this->getUniqueId($this->fullJson),
			'members' => $this->getMembers(),
			'groups' => $this->getGroups(),
			'links' => $this->getLinks()
		];

		if ($this->lightJson) {
			$json['members'] = [];
			$json['description'] = '';
			$json['links'] = [];
			$json['groups'] = [];
			$json['settings'] = [];
		}

		return $json;
	}


	public function getArray($full = false, $light = false) {
		$json = $this->getJson($full, $light);

		return json_decode($json, true);
	}


	public function getJson($full = false, $light = false) {
		$this->fullJson = $full;
		$this->lightJson = $light;
		$json = json_encode($this);
		$this->fullJson = false;
		$this->lightJson = false;

		return $json;
	}


	/**
	 * set all infos from an Array.
	 *
	 * @param $arr
	 * @param bool $allSettings
	 *
	 * @return $this
	 */
	public static function fromArray($arr, bool $allSettings = false) {
		if ($arr === null || empty($arr)) {
			return new DeprecatedCircle();
		}

		$circle = new DeprecatedCircle($arr['type'], $arr['name']);

		if (array_key_exists('alt_name', $arr)) {
			$circle->setAltName($arr['alt_name']);
		}
		$circle->setId($arr['id']);
		$circle->setUniqueId($arr['unique_id']);
		$circle->setDescription($arr['description']);

		$circle->setSettings(self::getSettingsFromArray($arr), $allSettings);
		$circle->setLinks(self::getLinksFromArray($arr));
		$circle->setCreation($arr['creation']);

		$circle->setViewer(self::getMemberFromArray($arr, 'user'));
		$circle->setOwner(self::getMemberFromArray($arr, 'owner'));

		if (array_key_exists('members', $arr) && is_array($arr['members'])) {
			$members = [];
			foreach ($arr['members'] as $item) {
				$members[] = DeprecatedMember::fromArray($item);
			}
			$circle->setMembers($members);
		}


		return $circle;
	}


	/**
	 * @param array $arr
	 * @param $key
	 * @param int $type
	 *
	 * @return null|DeprecatedMember
	 */
	private static function getMemberFromArray($arr, $key, $type = DeprecatedMember::TYPE_USER) {
		// TODO: 0.15.0 - remove condition is null
		if (key_exists($key, $arr) && $arr[$key] !== null) {
			$viewer = DeprecatedMember::fromArray($arr[$key]);
			$viewer->setType($type);

			return $viewer;
		}

		return null;
	}


	/**
	 * @param array $arr
	 *
	 * @return array
	 */
	private static function getLinksFromArray($arr) {
		$links = [];
		if (key_exists('links', $arr)) {
			$links = $arr['links'];
		}

		return $links;
	}


	/**
	 * @param array $arr
	 *
	 * @return array
	 */
	private static function getSettingsFromArray($arr) {
		$settings = [];
		if (key_exists('settings', $arr)) {
			$settings = $arr['settings'];
		}

		return $settings;
	}


	/**
	 * @param $json
	 *
	 * @return DeprecatedCircle
	 */
	public static function fromJSON($json) {
		return self::fromArray(json_decode($json, true));
	}


	/**
	 * @throws CircleTypeNotValidException
	 */
	public function cantBePersonal() {
		if ($this->getType() === self::CIRCLES_PERSONAL) {
			throw new CircleTypeNotValidException(
				$this->l10n->t("This feature is not available for personal circles")
			);
		}
	}


	/**
	 * @throws FederatedCircleNotAllowedException
	 */
	public function hasToBeFederated() {
		if ($this->getSetting('allow_links') !== 'true') {
			throw new FederatedCircleNotAllowedException(
				$this->l10n->t('The circle is not federated')
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
			case self::CIRCLES_SECRET:
				return 'Secret circle';
			case self::CIRCLES_CLOSED:
				return 'Closed circle';
			case self::CIRCLES_PUBLIC:
				return 'Public circle';
			case self::CIRCLES_ALL:
				return 'All circles';
		}

		return 'none';
	}


	/**
	 * convert old type to new config (nc22)
	 *
	 * @param int $type
	 *
	 * @return int
	 */
	public static function convertTypeToConfig(int $type): int {
		switch ($type) {
			case DeprecatedCircle::CIRCLES_PERSONAL:
				return 2;
			case DeprecatedCircle::CIRCLES_SECRET:
				return 16;
			case DeprecatedCircle::CIRCLES_CLOSED:
				return 120;
			case DeprecatedCircle::CIRCLES_PUBLIC:
				return 8;
		}

		return 0;
	}
}
