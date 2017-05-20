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

class SharingFrame implements \JsonSerializable {

	/** @var string */
	private $source;

	/** @var string */
	private $type;

	/** @var int */
	private $circleId;

	/** @var string */
	private $circleName;

	/** @var string */
	private $author;

	/** @var string */
	private $sharer;

	/** @var array */
	private $payload;

	/** @var int */
	private $creation;

	/** @var string */
	private $uniqueId;

	public function __construct(string $source, string $type) {
		$this->source = $source;
		$this->type = $type;
	}


	/**
	 * @return string
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param int $circleId
	 */
	public function setCircleId(int $circleId) {
		$this->circleId = $circleId;
	}

	/**
	 * @return int
	 */
	public function getCircleId() {
		return $this->circleId;
	}


	/**
	 * @param string $circleName
	 */
	public function setCircleName(string $circleName) {
		$this->circleName = $circleName;
	}

	/**
	 * @return mixed
	 */
	public function getCircleName() {
		return $this->circleName;
	}


	/**
	 * @param string $author
	 */
	public function setAuthor(string $author) {
		$this->author = $author;

		if ($this->getSharer() === null) {
			$this->setSharer($author);
		}
	}

	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}


	/**
	 * @param string $sharer
	 */
	public function setSharer(string $sharer) {
		$this->sharer = $sharer;
	}

	/**
	 * @return string
	 */
	public function getSharer() {
		return $this->sharer;
	}


	/**
	 * @param string $uniqueId
	 *
	 * @return SharingFrame
	 */
	public function setUniqueId(string $uniqueId) {
		$this->uniqueId = $uniqueId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUniqueId() {
		return $this->uniqueId;
	}

	/**
	 * @return SharingFrame
	 */
	public function generateUniqueId() {
		$uniqueId = bin2hex(openssl_random_pseudo_bytes(16));
		$this->setUniqueId($uniqueId);

		return $this;
	}

	/**
	 * @param array $payload
	 */
	public function setPayload(array $payload) {
		$this->payload = $payload;
	}

	/**
	 * @param bool $asJson
	 *
	 * @return array|string
	 */
	public function getPayload(bool $asJson = false) {
		if ($asJson) {
			return json_encode($this->payload);
		}

		return $this->payload;
	}


	/**
	 * @param int $creation
	 */
	public function setCreation($creation) {
		if ($creation === null) {
			return;
		}

		$this->creation = $creation;
	}

	/**
	 * @return int
	 */
	public function getCreation() {
		return $this->creation;
	}


	public function jsonSerialize() {
		return array(
			'circle_id'   => $this->getCircleId(),
			'circle_name' => $this->getCircleName(),
			'source'      => $this->getSource(),
			'type'        => $this->getType(),
			'author'      => $this->getAuthor(),
			'sharer'      => $this->getSharer(),
			'payload'     => $this->getPayload(),
			'creation'    => $this->getCreation(),
		);
	}

	public static function fromJSON($json) {

		$arr = json_decode($json, true);
		if (!key_exists('source', $arr)) {
			return null;
		}

		$share = new SharingFrame($arr['source'], $arr['type']);
		$share->setCircleId($arr['circle_id']);
		if (key_exists('circle_name', $arr)) {
			$share->setCircleName($arr['circle_name']);
		}

		$share->setAuthor($arr['author']);
		$share->setSharer($arr['sharer']);
		$share->setPayload($arr['payload']);
		$share->setCreation($arr['creation']);

		return $share;
	}

}