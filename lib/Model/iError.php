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

class iError {

	const CIRCLE_CREATION_TYPE_DISABLED = 181;
	const CIRCLE_CREATION_FIRST_CHAR = 182;

	const CIRCLE_CREATION_DUPLICATE_NAME = 304;
	const CIRCLE_CREATION_MULTIPLE_NAME = 309;

	const CIRCLE_INSERT_CIRCLE_DATABASE = 329;
	const CIRCLE_NOT_EXISTS = 341;

	const MEMBER_DOES_NOT_EXIST = 421;
	const MEMBER_CIRCLE_MULTIPLE_ENTRY = 432;
	const MEMBER_NOT_EXIST = 435;
	const MEMBER_NEEDS_MODERATOR_RIGHTS = 438;
	const MEMBER_ALREADY_IN_CIRCLE = 442;
	const MEMBER_NOT_IN_CIRCLE = 443;
	const MEMBER_CANT_REMOVE_OWNER = 483;

	const MEMBER_IS_NOT_INVITED = 491;
	const MEMBER_IS_BLOCKED = 492;
	const MEMBER_IS_OWNER = 493;

	private $message;
	private $code;

	function __construct() {
	}

	public function setMessage($message) {
		$this->message = $message;

		return $this;
	}

	public function getMessage() {
		return $this->message;
	}


	public function setCode($code) {
		$this->code = $code;

		return $this;
	}

	public function getCode() {
		return $this->code;
	}


	public function toArray() {
		return array(
			'code'    => $this->getCode(),
			'message' => $this->getMessage()
		);
	}


}