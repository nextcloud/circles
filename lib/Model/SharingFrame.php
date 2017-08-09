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

use OCA\Circles\Exceptions\SharingFrameSourceCannotBeAppCirclesException;

class SharingFrame implements \JsonSerializable {

	/** @var string */
	private $source;

	/** @var string */
	private $type;

	/** @var int */
	private $circleUniqueId;

	/** @var string */
	private $circleName;

	/** @var int */
	private $circleType;

	/** @var string */
	private $author;

	/** @var string */
	private $cloudId;

	/** @var array */
	private $payload;

	/** @var array */
	private $headers;

	/** @var int */
	private $creation;

	/** @var string */
	private $uniqueId;

	public function __construct($source, $type) {
		$this->source = (string)$source;
		$this->type = (string)$type;
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
	 * @param string $circleUniqueId
	 */
	public function setCircleId($circleUniqueId) {
		$this->circleUniqueId = $circleUniqueId;
	}

	/**
	 * @return string
	 */
	public function getCircleId() {
		return $this->circleUniqueId;
	}


	/**
	 * @param string $circleName
	 */
	public function setCircleName($circleName) {
		$this->circleName = $circleName;
	}

	/**
	 * @return string
	 */
	public function getCircleName() {
		return $this->circleName;
	}


	/**
	 * @param int $circleType
	 */
	public function setCircleType($circleType) {
		$this->circleType = $circleType;
	}

	/**
	 * @return int
	 */
	public function getCircleType() {
		return $this->circleType;
	}


	/**
	 * @param string $author
	 */
	public function setAuthor($author) {
		$this->author = (string)$author;
	}

	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}


	/**
	 * @param string $cloudId
	 */
	public function setCloudId($cloudId) {
		$this->cloudId = $cloudId;
	}

	/**
	 * @return string
	 */
	public function getCloudId() {
		return $this->cloudId;
	}


	/**
	 * @param string $uniqueId
	 *
	 * @return SharingFrame
	 */
	public function setUniqueId($uniqueId) {
		$this->uniqueId = (string)$uniqueId;

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
	public function setPayload($payload) {
		$this->payload = $payload;
	}

	/**
	 * @param bool $asJson
	 *
	 * @return array|string
	 */
	public function getPayload($asJson = false) {
		if ($asJson) {
			return json_encode($this->payload);
		}

		return $this->payload;
	}


	/**
	 * @param array $headers
	 */
	public function setHeaders($headers) {
		$this->headers = $headers;
	}

	/**
	 * @param bool $asJson
	 *
	 * @return array|string
	 */
	public function getHeaders($asJson = false) {
		if ($asJson) {
			return json_encode($this->headers);
		}

		return $this->headers;
	}


	/**
	 * @param string $k
	 *
	 * @return string
	 */
	public function getHeader($k) {
		if ($this->headers === null) {
			return null;
		}

		$k = (string)$k;
		if (!key_exists($k, $this->headers)) {
			return null;
		}

		return $this->headers[$k];
	}

	/**
	 * @param string $k
	 * @param string $v
	 */
	public function setHeader($k, $v) {
		$this->headers[(string)$k] = $v;
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


	/**
	 * @return bool
	 */
	public function isLocal() {
		return ($this->getCloudId() === null);
	}


	/**
	 * @return bool
	 */
	public function isCircleZero() {
		return ($this->getCloudId() === null);
	}

	/**
	 * @throws SharingFrameSourceCannotBeAppCirclesException
	 */
	public function cannotBeFromCircles() {
		if (strtolower($this->getSource()) === 'circles') {
			throw new SharingFrameSourceCannotBeAppCirclesException();
		}
	}


	public function jsonSerialize() {
		return array(
			'circle_id'   => $this->getCircleId(),
			'circle_name' => $this->getCircleName(),
			'circle_type' => $this->getCircleType(),
			'unique_id'   => $this->getUniqueId(),
			'source'      => $this->getSource(),
			'type'        => $this->getType(),
			'author'      => $this->getAuthor(),
			'cloud_id'    => $this->getCloudId(),
			'headers'     => $this->getHeaders(),
			'payload'     => $this->getPayload(),
			'creation'    => $this->getCreation(),
		);
	}

	public static function fromJSON($json) {

		$arr = json_decode($json, true);
		if (!is_array($arr) || !key_exists('source', $arr)) {
			return null;
		}

		$share = new SharingFrame($arr['source'], $arr['type']);
		$share->setCircleId($arr['circle_id']);
		if (key_exists('circle_name', $arr)) {
			$share->setCircleName($arr['circle_name']);
		}
		if (key_exists('circle_type', $arr)) {
			$share->setCircleType($arr['circle_type']);
		}


		if (key_exists('headers', $arr)) {
			$share->setHeaders($arr['headers']);
		}

		if (key_exists('cloud_id', $arr)) {
			$share->setCloudID($arr['cloud_id']);
		}

		$share->setUniqueId($arr['unique_id']);
		$share->setAuthor($arr['author']);
		$share->setPayload($arr['payload']);
		$share->setCreation($arr['creation']);

		return $share;
	}

}

