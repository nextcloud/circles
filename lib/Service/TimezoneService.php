<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Circles\Service;

use DateTime;
use OC\AppFramework\Utility\TimeFactory;

class TimezoneService {
	/** @var string */
	private $userId;

	/** @var TimeFactory */
	private $timeFactory;

	/** @var ConfigService */
	private $configService;


	/**
	 * TimezoneService constructor.
	 *
	 * @param string $userId
	 * @param TimeFactory $timeFactory
	 * @param ConfigService $configService
	 */
	public function __construct(
		$userId,
		TimeFactory $timeFactory,
		ConfigService $configService,
	) {
		$this->userId = $userId;
		$this->timeFactory = $timeFactory;
		$this->configService = $configService;
	}


	/**
	 * @param string $time
	 *
	 * @return string
	 */
	public function convertTimeForCurrentUser($time) {
		return $this->convertTimeForUserId($this->userId, $time);
	}


	public function convertToTimestamp($time) {
		return strtotime($time);
	}


	/**
	 * @param string $userId
	 * @param string $time
	 *
	 * @return string
	 */
	public function convertTimeForUserId($userId, $time) {
		$timezone = $this->configService->getCoreValueForUser($userId, 'timezone', 'UTC');
		$date = \DateTime::createFromFormat('Y-m-d H:i:s', $time);
		if ($date === false) {
			return $time;
		}

		$date->setTimezone(new \DateTimeZone($timezone));

		return $date->format('Y-m-d H:i:s');
	}


	/**
	 * @param string $time
	 *
	 * @return DateTime
	 */
	public function getDateTime(string $time = 'now'): DateTime {
		return $this->timeFactory->getDateTime($time);
	}


	/**
	 * @return string
	 */
	public function getUTCDate(): string {
		$defaultTimezone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$format = date('Y-m-d H:i:s');
		date_default_timezone_set($defaultTimezone);

		return $format;
	}
}
