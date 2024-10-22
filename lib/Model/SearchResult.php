<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use JsonSerializable;

class SearchResult implements JsonSerializable {
	public function __construct(
		private string $ident = '',
		private int $type = 0,
		private string $instance = '',
		private array $data = [],
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
