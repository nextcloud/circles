<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @copyright 2021
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
use OCA\Circles\Service\InterfaceService;
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

	/** @var InterfaceService */
	private $interfaceService;

	/** @var ConfigService */
	private $configService;


	/**
	 * Capabilities constructor.
	 *
	 * @param IL10N $l10n
	 * @param IAppManager $appManager
	 * @param InterfaceService $interfaceService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IL10N $l10n,
		IAppManager $appManager,
		InterfaceService $interfaceService,
		ConfigService $configService
	) {
		$this->l10n = $l10n;
		$this->appManager = $appManager;
		$this->interfaceService = $interfaceService;
		$this->configService = $configService;
	}


	/**
	 * @param bool $complete
	 *
	 * @return array
	 */
	public function getCapabilities(bool $complete = false): array {
		return [
			Application::APP_ID => [
				'version' => $this->appManager->getAppVersion(Application::APP_ID),
				'status' => $this->getCapabilitiesStatus($complete),
				'settings' => $this->configService->getSettings(),
				'circle' => $this->getCapabilitiesCircle(),
				'member' => $this->getCapabilitiesMember()
			],
		];
	}


	/**
	 * @param bool $complete
	 *
	 * @return array
	 */
	private function getCapabilitiesStatus(bool $complete = false): array {
		$status = [
			'globalScale' => $this->configService->isGSAvailable()
		];

		if ($complete) {
			$status['interfaces'] = [
				'all' => $this->interfaceService->getInterfaces(true),
				'internal' => $this->interfaceService->getInternalInterfaces(true)
			];
		}

		return $status;
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesCircle(): array {
		return [
			'constants' => $this->getCapabilitiesCircleConstants(),
			'config' => $this->getCapabilitiesCircleConfig()
		];
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesMember(): array {
		return [
			'constants' => $this->getCapabilitiesMemberConstants(),
			'type' => Member::$TYPE
		];
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesCircleConstants(): array {
		return [
			'flags' => [
				Circle::CFG_SINGLE => $this->l10n->t('Single'),
				Circle::CFG_PERSONAL => $this->l10n->t('Personal'),
				Circle::CFG_SYSTEM => $this->l10n->t('System'),
				Circle::CFG_VISIBLE => $this->l10n->t('Visible'),
				Circle::CFG_OPEN => $this->l10n->t('Open'),
				Circle::CFG_INVITE => $this->l10n->t('Invite'),
				Circle::CFG_REQUEST => $this->l10n->t('Join Request'),
				Circle::CFG_FRIEND => $this->l10n->t('Friends'),
				Circle::CFG_PROTECTED => $this->l10n->t('Password Protected'),
				Circle::CFG_NO_OWNER => $this->l10n->t('No Owner'),
				Circle::CFG_HIDDEN => $this->l10n->t('Hidden'),
				Circle::CFG_BACKEND => $this->l10n->t('Backend'),
				Circle::CFG_LOCAL => $this->l10n->t('Local'),
				Circle::CFG_ROOT => $this->l10n->t('Root'),
				Circle::CFG_CIRCLE_INVITE => $this->l10n->t('Circle Invite'),
				Circle::CFG_FEDERATED => $this->l10n->t('Federated'),
				Circle::CFG_MOUNTPOINT => $this->l10n->t('Mount point')
			],
			'source' =>
				[
					'core' => [
						Member::TYPE_USER => $this->l10n->t('Nextcloud Account'),
						Member::TYPE_GROUP => $this->l10n->t('Nextcloud Group'),
						Member::TYPE_MAIL => $this->l10n->t('Email Address'),
						Member::TYPE_CONTACT => $this->l10n->t('Contact'),
						Member::TYPE_CIRCLE => $this->l10n->t('Circle'),
						Member::TYPE_APP => $this->l10n->t('Nextcloud App')
					],
					'extra' => [
						Member::APP_CIRCLES => 'Circles App',
						Member::APP_OCC => 'Admin Command Line'
					]
				]
		];
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesCircleConfig(): array {
		return [
			'coreFlags' => Circle::$DEF_CFG_CORE_FILTER,
			'systemFlags' => Circle::$DEF_CFG_SYSTEM_FILTER
		];
	}


	/**
	 * @return array
	 */
	private function getCapabilitiesMemberConstants(): array {
		return [
			'level' => [
				Member::LEVEL_MEMBER => $this->l10n->t('Member'),
				Member::LEVEL_MODERATOR => $this->l10n->t('Moderator'),
				Member::LEVEL_ADMIN => $this->l10n->t('Admin'),
				Member::LEVEL_OWNER => $this->l10n->t('Owner')
			]
		];
	}
}
