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


use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberCantJoinCircleException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsBlockedException;
use OCA\Circles\Exceptions\MemberIsNotAdminException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MemberIsNotOwnerException;
use OCA\Circles\Exceptions\MemberIsOwnerException;
use OCA\Circles\Exceptions\MemberTypeCantEditLevelException;
use OCA\Circles\Exceptions\ModeratorIsNotHighEnoughException;

class SearchResult implements \JsonSerializable {

	/** @var string */
	private $ident;

	/** @var int */
	private $type;

	/** @var array */
	private $data = [];


	/**
	 * SearchResult constructor.
	 *
	 * @param string $ident
	 * @param int $type
	 * @param array $data
	 */
	function __construct($ident = '', $type = 0, $data = []) {
		$this->setIdent($ident);
		$this->setType($type);
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
	function jsonSerialize() {

		return [
			'ident' => $this->getIdent(),
			'type'  => $this->getType(),
			'data'  => $this->getData()
		];

	}
}