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
use OCP\IL10N;


/**
 * Class Capabilities
 *
 * @package OCA\Circles\AppInfo
 */
class Capabilities implements ICapability {


	/** @var IL10N */
	private $l10n;

	/** @var IAppManager */
	private $appManager;

	/** @var ConfigService */
	private $configService;


	/**
	 * Capabilities constructor.
	 *
	 * @param IL10N $l10n
	 * @param IAppManager $appManager
	 * @param ConfigService $configService
	 */
	public function __construct(IL10N $l10n, IAppManager $appManager, ConfigService $configService) {
		$this->l10n = $l10n;
		$this->appManager = $appManager;
		$this->configService = $configService;
	}


	/**
	 * @return array
	 */
	public function getCapabilities(): array {
		return [
			Application::APP_ID => [
				'version'  => $this->appManager->getAppVersion(Application::APP_ID),
				'settings' => $this->configService->getSettings(),
				'circle'   => $this->getCapabilitiesCircle(),
				'member'   => $this->getCapabilitiesMember()
			],
		];
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesCircle(): array {
		return [
			'constants' => $this->getCapabilitiesCircleConstants(),
			'config'    => $this->getCapabilitiesCircleConfig()
		];
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesMember(): array {
		return [
			'constants' => $this->getCapabilitiesMemberConstants()
		];
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesCircleConstants(): array {
		return [
			'flags'  => [
				1     => $this->l10n->t('Single'),
				2     => $this->l10n->t('Personal'),
				4     => $this->l10n->t('System'),
				8     => $this->l10n->t('Visible'),
				16    => $this->l10n->t('Open'),
				32    => $this->l10n->t('Invite'),
				64    => $this->l10n->t('Join Request'),
				128   => $this->l10n->t('Friends'),
				256   => $this->l10n->t('Password Protected'),
				512   => $this->l10n->t('No Owner'),
				1024  => $this->l10n->t('Hidden'),
				2048  => $this->l10n->t('Backend'),
				4096  => $this->l10n->t('Root'),
				8192  => $this->l10n->t('Circle Invite'),
				16384 => $this->l10n->t('Federated')
			],
			'source' => [
				1  => $this->l10n->t('Nextcloud User'),
				2  => $this->l10n->t('Nextcloud Group'),
				3  => $this->l10n->t('Mail Address'),
				4  => $this->l10n->t('Contact'),
				10 => $this->l10n->t('Circle')
			]
		];
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesCircleConfig(): array {
		return [
			'coreFlags'   => Circle::$DEF_CFG_CORE_FILTER,
			'systemFlags' => Circle::$DEF_CFG_SYSTEM_FILTER
		];
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesMemberConstants(): array {
		return [
			'type'  => Member::$TYPE,
			'level' => [
				1 => $this->l10n->t('Member'),
				4 => $this->l10n->t('Moderator'),
				8 => $this->l10n->t('Admin'),
				9 => $this->l10n->t('Owner')
			]
		];
	}

}
