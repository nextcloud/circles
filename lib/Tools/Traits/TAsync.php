<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Tools\Traits;

use JsonSerializable;

trait TAsync {
	use TNCSetup;


	/** @var string */
	public static $SETUP_TIME_LIMIT = 'async_time_limit';


	/**
	 * Hacky way to async the rest of the process without keeping client on hold.
	 *
	 * @param string $result
	 */
	public function async(string $result = ''): void {
		if (ob_get_contents() !== false) {
			ob_end_clean();
		}

		header('Connection: close');
		header('Content-Encoding: none');
		ignore_user_abort();
		$timeLimit = $this->setupInt(self::$SETUP_TIME_LIMIT);
		set_time_limit(($timeLimit > 0) ? $timeLimit : 0);
		ob_start();

		echo($result);

		$size = ob_get_length();
		header('Content-Length: ' . $size);
		ob_end_flush();
		flush();
	}

	/**
	 * @param JsonSerializable $obj
	 */
	public function asyncObj(JsonSerializable $obj): void {
		$this->async(json_encode($obj));
	}
}
