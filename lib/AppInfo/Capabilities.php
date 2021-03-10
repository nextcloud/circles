<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 * @author Daniel Tygel <dtygel@eita.org.br>
 *
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


namespace OCA\Circles\AppInfo;


use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;


/**
 * Class Capabilities
 *
 * @package OCA\Circles\AppInfo
 */
class Capabilities implements ICapability {


	/** @var IAppManager */
	private $appManager;

	/** @var ConfigService */
	private $configService;


	/**
	 * Capabilities constructor.
	 *
	 * @param IAppManager $appManager
	 * @param ConfigService $configService
	 */
	public function __construct(IAppManager $appManager, ConfigService $configService) {
		$this->appManager = $appManager;
		$this->configService = $configService;
	}


	/**
	 * @return array
	 */
	public function getCapabilities(): array {
		return [
			Application::APP_ID => [
				'version'   => $this->appManager->getAppVersion(Application::APP_ID),
				'settings'  => $this->configService->getSettings(),
				'constants' => $this->getConstants()
			],
		];
	}


	private function getConstants(): array {
		return [
			'circle' => [
				'config' => $this->generateConstantsCircleConfig()
			],
			'member' => $this->generateConstantsMember()
		];
	}


	/**
	 * @return array
	 */
	private function generateConstantsCircleConfig(): array {
		$constants = [
			'flags'       => [],
			'coreFlags'   => Circle::$DEF_CFG_CORE_FILTER,
			'systemFlags' => Circle::$DEF_CFG_SYSTEM_FILTER
		];

		foreach (Circle::$DEF_CFG as $flag => $entry) {
			list(, $def) = explode('|', $entry);
			$constants['flags'][$flag] = $def;
		}

		return $constants;
	}


	/**
	 * @return array
	 */
	private function generateConstantsMember(): array {
		return [
			'type'   => Member::$DEF_TYPE,
			'level'  => Member::$DEF_TYPE,
			'status' => [
				Member::STATUS_INVITED,
				Member::STATUS_REQUEST,
				Member::STATUS_MEMBER,
				Member::STATUS_BLOCKED
			]
		];
	}

}
