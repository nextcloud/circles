<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\SharingFrameSourceCannotBeAppCirclesException;
use OCA\Circles\Service\MiscService;

/**
 * @deprecated
 * Class SharingFrame
 *
 * @package OCA\Circles\Model
 */
class SharingFrame implements \JsonSerializable {
	/** @var string */
	private $source;

	/** @var string */
	private $type;

	/** @var DeprecatedCircle */
	private $circle;

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
	 * @param DeprecatedCircle $circle
	 */
	public function setCircle($circle) {
		$this->circle = $circle;
	}

	/**
	 * @return DeprecatedCircle
	 */
	public function getCircle() {
		return $this->circle;
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
	public function is0Circle() {
		return ($this->getCloudId() === null);
	}

	/**
	 * @throws SharingFrameSourceCannotBeAppCirclesException
	 */
	public function cannotBeFromCircles() {
		if (strtolower($this->getSource()) === Application::APP_ID) {
			throw new SharingFrameSourceCannotBeAppCirclesException();
		}
	}


	public function jsonSerialize(): array {
		return [
			'unique_id' => $this->getUniqueId(),
			'circle' => $this->getCircle()
				->getArray(false, true),
			'source' => $this->getSource(),
			'type' => $this->getType(),
			'author' => $this->getAuthor(),
			'cloud_id' => $this->getCloudId(),
			'headers' => $this->getHeaders(),
			'payload' => $this->getPayload(),
			'creation' => $this->getCreation(),
		];
	}

	public static function fromJSON($json) {
		$arr = json_decode($json, true);

		return self::fromArray($arr);
	}


	public static function fromArray($arr) {
		if (!is_array($arr) || !key_exists('source', $arr)) {
			return null;
		}

		$share = new SharingFrame($arr['source'], $arr['type']);
		$share->setCircle(self::getCircleFromArray($arr));
		$share->setHeaders(self::getHeadersFromArray($arr));
		if (key_exists('cloud_id', $arr)) {
			$share->setCloudID($arr['cloud_id']);
		}

		$share->setUniqueId($arr['unique_id']);
		$share->setAuthor($arr['author']);
		$share->setPayload($arr['payload']);
		$share->setCreation($arr['creation']);

		return $share;
	}


	/**
	 * @param array $arr
	 *
	 * @return array
	 */
	private static function getHeadersFromArray($arr) {
		$headers = [];
		if (key_exists('headers', $arr)) {
			$headers = $arr['headers'];
		}

		return $headers;
	}


	/**
	 * @param array $arr
	 *
	 * @return DeprecatedCircle
	 */
	private static function getCircleFromArray($arr) {
		return DeprecatedCircle::fromArray(MiscService::get($arr, 'circle', null));
	}
}
