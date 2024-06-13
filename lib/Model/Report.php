<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Model;

use JsonSerializable;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;

/**
 * Class Report
 *
 * @package OCA\Circles\Model
 */
class Report implements IDeserializable, JsonSerializable {
	use TArrayTools;

	/** @var string */
	private $source = '';

	/** @var Circle[] */
	private $circles = [];

	/** @var array */
	private $obfuscated = [];


	/**
	 * Report constructor.
	 */
	public function __construct() {
	}


	/**
	 * @param string $source
	 *
	 * @return Report
	 */
	public function setSource(string $source): self {
		$this->source = $source;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSource(): string {
		return $this->source;
	}


	/**
	 * @param Circle[] $circles
	 *
	 * @return $this
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
	 * @param array $obfuscated
	 *
	 * @return $this
	 */
	public function setObfuscated(array $obfuscated): self {
		$this->obfuscated = $obfuscated;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getObfuscated(): array {
		return $this->obfuscated;
	}


	/**
	 * @param array $data
	 *
	 * @return IDeserializable
	 */
	public function import(array $data): IDeserializable {
		$this->setSource($this->get('source', $data));
		$this->setCircles($this->getArray('circles', $data));
		$this->setObfuscated($this->getArray('obfuscated', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'source' => $this->getSource(),
			'circles' => $this->getCircles(),
			'obfuscated' => $this->getObfuscated()
		];
	}
}
