<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Model\Probes;

use OCA\Circles\Db\CoreQueryBuilder;

/**
 * Class CircleProbe
 *
 * @package OCA\Circles\Model\Probes
 */
class DataProbe extends BasicProbe {
	public const OWNER = CoreQueryBuilder::OWNER;
	public const MEMBER = CoreQueryBuilder::MEMBER;
	public const BASED_ON = CoreQueryBuilder::BASED_ON;
	public const MEMBERSHIPS = CoreQueryBuilder::MEMBERSHIPS;
	public const CONFIG = CoreQueryBuilder::CONFIG;
	public const INITIATOR = CoreQueryBuilder::INITIATOR;
	public const INHERITED_BY = CoreQueryBuilder::INHERITED_BY;


	private array $path = [];


	public function __construct() {
	}


	/**
	 * @param string $key
	 * @param array $path
	 *
	 * @return $this
	 */
	public function add(string $key, array $path = []): self {
		$this->path[$key] = $path;

		return $this;
	}


	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has(string $key): bool {
		return (array_key_exists($key, $this->path));
	}


	/**
	 * @return array
	 */
	public function getPath(): array {
		return $this->path;
	}


	/**
	 * Return an array with includes as options
	 *
	 * @return array
	 */
	public function getAsOptions(): array {
		return array_merge(
			[
				'path' => $this->getPath()
			],
			parent::getAsOptions()
		);
	}

	/**
	 * Return a JSON object with includes as options
	 *
	 * @return array
	 */
	public function JsonSerialize(): array {
		return $this->getAsOptions();
	}
}
