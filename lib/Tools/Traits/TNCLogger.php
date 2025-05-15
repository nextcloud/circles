<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Tools\Traits;

use Exception;
use OC\HintException;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;

trait TNCLogger {
	use TNCSetup;


	public static $EMERGENCY = 4;
	public static $ALERT = 3;
	public static $CRITICAL = 3;
	public static $ERROR = 3;
	public static $WARNING = 2;
	public static $NOTICE = 1;
	public static $INFO = 1;
	public static $DEBUG = 0;


	/**
	 * @param Throwable $t
	 * @param array $serializable
	 */
	public function t(Throwable $t, array $serializable = []): void {
		$this->throwable($t, self::$ERROR, $serializable);
	}

	/**
	 * @param Throwable $t
	 * @param int $level
	 * @param array $serializable
	 */
	public function throwable(Throwable $t, int $level = 3, array $serializable = []): void {
		$message = '';
		if (!empty($serializable)) {
			$message = json_encode($serializable);
		}

		$this->logger()
			->log(
				$level,
				$message,
				[
					'app' => $this->setup('app'),
					'exception' => $t
				]
			);
	}


	/**
	 * @param Exception $e
	 * @param array $serializable
	 */
	public function e(Exception $e, array $serializable = []): void {
		$this->exception($e, self::$ERROR, $serializable);
	}

	/**
	 * @param Exception $e
	 * @param int|array $level
	 * @param array $serializable
	 */
	public function exception(Exception $e, $level = 3, array $serializable = []): void {
		if (is_array($level) && empty($serializable)) {
			$serializable = $level;
			$level = 3;
		}

		$message = '';
		if (!empty($serializable)) {
			$message = json_encode($serializable);
		}

		if ($level === self::$DEBUG) {
			$level = (int)$this->appConfig('debug_level');
		}

		$this->logger()
			->log(
				$level,
				$message,
				[
					'app' => $this->setup('app'),
					'exception' => $e
				]
			);
	}


	/**
	 * @param string $message
	 * @param bool $trace
	 * @param array $serializable
	 */
	public function emergency(string $message, bool $trace = false, array $serializable = []): void {
		$this->log(self::$EMERGENCY, '[emergency] ' . $message, $trace, $serializable);
	}

	/**
	 * @param string $message
	 * @param bool $trace
	 * @param array $serializable
	 */
	public function alert(string $message, bool $trace = false, array $serializable = []): void {
		$this->log(self::$ALERT, '[alert] ' . $message, $trace, $serializable);
	}

	/**
	 * @param string $message
	 * @param bool $trace
	 * @param array $serializable
	 */
	public function warning(string $message, bool $trace = false, array $serializable = []): void {
		$this->log(self::$WARNING, '[warning] ' . $message, $trace, $serializable);
	}

	/**
	 * @param string $message
	 * @param bool $trace
	 * @param array $serializable
	 */
	public function notice(string $message, bool $trace = false, array $serializable = []): void {
		$this->log(self::$NOTICE, '[notice] ' . $message, $trace, $serializable);
	}

	/**
	 * @param string $message
	 * @param array $serializable
	 */
	public function debug(string $message, array $serializable = []): void {
		$message = '[debug] ' . $message;
		$debugLevel = (int)$this->appConfig('debug_level');
		$this->log($debugLevel, $message, ($this->appConfig('debug_trace') === '1'), $serializable);
	}


	/**
	 * @param int $level
	 * @param string $message
	 * @param bool $trace
	 * @param array $serializable
	 */
	public function log(int $level, string $message, bool $trace = false, array $serializable = []): void {
		$opts = ['app' => $this->setup('app')];
		if ($trace) {
			$opts['exception'] = new HintException($message, json_encode($serializable));
		} elseif (!empty($serializable)) {
			$message .= ' -- ' . json_encode($serializable);
		}

		$this->logger()
			->log($level, $message, $opts);
	}


	/**
	 * @return LoggerInterface
	 */
	public function logger(): LoggerInterface {
		if (isset($this->logger)) {
			return $this->logger;
		} else {
			return Server::get(LoggerInterface::class);
		}
	}
}
