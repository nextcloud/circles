<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\AppInfo;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\InterfaceService;
use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;
use OCP\IL10N;
use OCP\Teams\ITeamResourceProvider;
use Psr\Container\ContainerInterface;

class Capabilities implements ICapability {
	public function __construct(
		private IL10N $l10n,
		private IAppManager $appManager,
		private InterfaceService $interfaceService,
		private ConfigService $configService,
		private Coordinator $coordinator,
		private ContainerInterface $container,
	) {
	}

	public function getCapabilities(bool $complete = false): array {
		return [
			Application::APP_ID => [
				'version' => $this->appManager->getAppVersion(Application::APP_ID),
				'status' => $this->getCapabilitiesStatus($complete),
				'settings' => $this->configService->getSettings(),
				'circle' => $this->getCapabilitiesCircle(),
				'member' => $this->getCapabilitiesMember(),
				'teamResourceProviders' => $this->getCapabilitiesTeamResourceProviders(),
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
				Circle::CFG_REQUEST => $this->l10n->t('Join request'),
				Circle::CFG_FRIEND => $this->l10n->t('Friends'),
				Circle::CFG_PROTECTED => $this->l10n->t('Password protected'),
				Circle::CFG_NO_OWNER => $this->l10n->t('No Owner'),
				Circle::CFG_HIDDEN => $this->l10n->t('Hidden'),
				Circle::CFG_BACKEND => $this->l10n->t('Backend'),
				Circle::CFG_LOCAL => $this->l10n->t('Local'),
				Circle::CFG_ROOT => $this->l10n->t('Root'),
				Circle::CFG_CIRCLE_INVITE => $this->l10n->t('Team invite'),
				Circle::CFG_FEDERATED => $this->l10n->t('Federated'),
				Circle::CFG_MOUNTPOINT => $this->l10n->t('Mount point')
			],
			'source' =>
				[
					'core' => [
						Member::TYPE_USER => $this->l10n->t('Nextcloud Account'),
						Member::TYPE_GROUP => $this->l10n->t('Nextcloud Group'),
						Member::TYPE_MAIL => $this->l10n->t('Email address'),
						Member::TYPE_CONTACT => $this->l10n->t('Contact'),
						Member::TYPE_CIRCLE => $this->l10n->t('Team'),
						Member::TYPE_APP => $this->l10n->t('Nextcloud App')
					],
					'extra' => [
						Member::APP_CIRCLES => $this->l10n->t('Teams App'),
						Member::APP_OCC => $this->l10n->t('Admin Command Line'),
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

	/**
	 * @return string[]
	 */
	private function getCapabilitiesTeamResourceProviders() {
		$providers = $this->coordinator->getRegistrationContext()?->getTeamResourceProviders();
		if ($providers === null) {
			return [];
		}
		$providerIds = array_map(
			function (ServiceRegistration $registration) {
				/** @var ITeamResourceProvider $provider */
				$provider = $this->container->get($registration->getService());
				return $provider->getId();
			},
			$providers,
		);
		return $providerIds;
	}
}
