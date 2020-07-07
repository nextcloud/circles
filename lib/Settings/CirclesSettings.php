<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Settings;


use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Service\ConfigService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;


class CirclesSettings implements ISettings {


	/** @var IConfig */
	private $config;

	/** @var ConfigService */
	private $configService;


	/**
	 * CirclesSettings constructor.
	 *
	 * @param IConfig $config
	 * @param ConfigService $configService
	 */
	public function __construct(IConfig $config, ConfigService $configService) {
		$this->config = $config;
		$this->configService = $configService;
	}


	/**
	 * @return TemplateResponse
	 * @throws GSStatusException
	 */
	public function getForm() {
		return new TemplateResponse(
			'circles', 'settings.admin',
			[
				'gsEnabled' => $this->configService->getGSStatus(ConfigService::GS_ENABLED)
			]
		);
	}


	/**
	 * @return string
	 */
	public function getSection() {
		return 'groupware';
	}


	/**
	 * @return int
	 */
	public function getPriority() {
		return 90;
	}

}

