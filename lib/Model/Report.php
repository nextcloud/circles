<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


use daita\MySmallPhpTools\IDeserializable;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;


/**
 * Class Report
 *
 * @package OCA\Circles\Model
 */
class Report implements IDeserializable, JsonSerializable {


	use TArrayTools;

	/** @var Circle[] */
	private $circles = [];

	/** @var Member[] */
	private $members = [];


	/**
	 * Report constructor.
	 */
	public function __construct() {
	}


	/**
	 * @param Circle[] $circles
	 */
	public function setCircles(array $circles): self {
		$this->circles = $circles;

		return $this;
	}

	/**
	 * @return Circle[]
	 */
	public function getCircles(): array {
		return $this->circles;
	}


	/**
	 * @param Member[] $members
	 */
	public function setMembers(array $members): self {
		$this->members = $members;

		return $this;
	}

	/**
	 * @return Member[]
	 */
	public function getMembers(): array {
		return $this->members;
	}


	/**
	 * @param array $data
	 *
	 * @return IDeserializable
	 */
	public function import(array $data): IDeserializable {
		$this->setCircles($this->getArray('circles', $data));
		$this->setMembers($this->getArray('members', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	function jsonSerialize(): array {
		return [
			'circles' => $this->getCircles(),
			'members' => $this->getMembers()
		];
	}

}

