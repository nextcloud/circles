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

use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use JsonSerializable;

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
