<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Tools\Traits;

use OCP\IConfig;
use OCP\Server;

trait TNCSetup {
	use TArrayTools;


	/** @var array */
	private $_setup = [];


	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @param string $default
	 *
	 * @return string
	 */
	public function setup(string $key, string $value = '', string $default = ''): string {
		if ($value !== '') {
			$this->_setup[$key] = $value;
		}

		return $this->get($key, $this->_setup, $default);
	}

	/**
	 * @param string $key
	 * @param array $value
	 * @param array $default
	 *
	 * @return array
	 */
	public function setupArray(string $key, array $value = [], array $default = []): array {
		if (!empty($value)) {
			$this->_setup[$key] = $value;
		}

		return $this->getArray($key, $this->_setup, $default);
	}

	/**
	 * @param string $key
	 * @param int $value
	 * @param int $default
	 *
	 * @return int
	 */
	public function setupInt(string $key, int $value = -999, int $default = 0): int {
		if ($value !== -999) {
			$this->_setup[$key] = $value;
		}

		return $this->getInt($key, $this->_setup, $default);
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function appConfig(string $key): string {
		$app = $this->setup('app');
		if ($app === '') {
			return '';
		}

		$config = Server::get(IConfig::class);

		return $config->getAppValue($app, $key, '');
	}
}
