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

class SearchResult implements \JsonSerializable {
	/** @var string */
	private $ident;

	/** @var int */
	private $type;

	/** @var string */
	private $instance = '';

	/** @var array */
	private $data = [];


	/**
	 * SearchResult constructor.
	 *
	 * @param string $ident
	 * @param int $type
	 * @param string $instance
	 * @param array $data
	 */
	public function __construct($ident = '', $type = 0, $instance = '', $data = []) {
		$this->setIdent($ident);
		$this->setType($type);
		$this->setInstance($instance);
		$this->setData($data);
	}


	/**
	 * @param string $ident
	 */
	public function setIdent($ident) {
		$this->ident = $ident;
	}

	/**
	 * @return string
	 */
	public function getIdent() {
		return $this->ident;
	}


	/**
	 * @param string $instance
	 */
	public function setInstance($instance) {
		$this->instance = $instance;
	}

	/**
	 * @return string
	 */
	public function getInstance() {
		return $this->instance;
	}


	/**
	 * @param int $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @return int
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * @param array $data
	 */
	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function getData() {
		if (!key_exists('display', $this->data)) {
			return ['display' => $this->getIdent()];
		}

		return $this->data;
	}


	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize() {
		return [
			'ident' => $this->getIdent(),
			'instance' => $this->getInstance(),
			'type' => $this->getType(),
			'data' => $this->getData()
		];
	}
}
