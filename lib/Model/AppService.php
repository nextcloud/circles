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


use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Signatory;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;


/**
 * Class AppService
 *
 * @package OCA\Circles\Model
 */
class AppService extends NC21Signatory implements JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $test = '';

	/** @var string */
	private $incoming = '';

	/** @var string */
	private $circles = '';

	/** @var string */
	private $members = '';


	/**
	 * @param string $test
	 *
	 * @return AppService
	 */
	public function setTest(string $test): self {
		$this->test = $test;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTest(): string {
		return $this->test;
	}


	/**
	 * @return string
	 */
	public function getIncoming(): string {
		return $this->incoming;
	}

	/**
	 * @param string $incoming
	 *
	 * @return self
	 */
	public function setIncoming(string $incoming): self {
		$this->incoming = $incoming;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getCircles(): string {
		return $this->circles;
	}

	/**
	 * @param string $circles
	 *
	 * @return self
	 */
	public function setCircles(string $circles): self {
		$this->circles = $circles;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getMembers(): string {
		return $this->members;
	}

	/**
	 * @param string $members
	 *
	 * @return self
	 */
	public function setMembers(string $members): self {
		$this->members = $members;

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return array_filter(
			array_merge(
				[
					'test'     => $this->getTest(),
					'incoming' => $this->getIncoming(),
					'circles'  => $this->getCircles(),
					'members'  => $this->getMembers()
				],
				parent::jsonSerialize()
			)
		);
	}

}
