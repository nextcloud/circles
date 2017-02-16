<?php

namespace OCA\Circles\Service;

use OCP\ILogger;

class MiscService {

	private $logger;

	private $appName;

	public function __construct(ILogger $logger, $appName) {
		$this->logger = $logger;
		$this->appName = $appName;
	}

	public function log($message, $level = 2) {
		$data = array(
			'app'   => $this->appName,
			'level' => $level
		);

		$this->logger->log($level, $message, $data);
	}
}