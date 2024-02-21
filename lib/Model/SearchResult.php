<?php

declare(strict_types=1);
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

use JsonSerializable;

class SearchResult implements JsonSerializable {
	public function __construct(
		private string $ident = '',
		private int $type = 0,
		private string $instance = '',
		private array $data = []
	) {
	}

	public function setIdent(string $ident): self {
		$this->ident = $ident;
		return $this;
	}

	public function getIdent(): string {
		return $this->ident;
	}

	public function setInstance(string $instance): self {
		$this->instance = $instance;
		return $this;
	}

	public function getInstance(): string {
		return $this->instance;
	}

	public function setType(int $type): self {
		$this->type = $type;
		return $this;
	}

	public function getType(): int {
		return $this->type;
	}

	public function setData(array $data): self {
		$this->data = $data;
		return $this;
	}

	public function getData(): array {
		if (!key_exists('display', $this->data)) {
			return ['display' => $this->getIdent()];
		}
		return $this->data;
	}

	public function jsonSerialize(): array {
		return [
			'ident' => $this->getIdent(),
			'instance' => $this->getInstance(),
			'type' => $this->getType(),
			'data' => $this->getData()
		];
	}
}
