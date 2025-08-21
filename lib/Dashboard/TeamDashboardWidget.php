<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Dashboard;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Service\ConfigService;
use OCP\App\IAppManager;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IConditionalWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;

class TeamDashboardWidget implements IIconWidget, IButtonWidget, IConditionalWidget {
	public function __construct(
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		private ConfigService $configService,
		private IAppManager $appManager,
	) {
	}


	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'circles';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Teams');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconClass(): string {
		return 'icon-teams';
	}

	/**
	 * @inheritDoc
	 */
	public function getUrl(): ?string {
		return $this->getTeamPage();
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		Util::addScript(Application::APP_ID, 'teams-dashboard');
		Util::addStyle(Application::APP_ID, 'teams-dashboard');
	}

	public function getWidgetButtons(string $userId): array {
		return [
			new WidgetButton(
				WidgetButton::TYPE_MORE,
				$this->getTeamPage(),
				$this->l10n->t('Show all teams')
			),
			new WidgetButton(
				WidgetButton::TYPE_SETUP,
				$this->getTeamPage(),
				$this->l10n->t('Create a new team')
			),
		];
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('circles', 'circles-dark.svg'));
	}

	private function getTeamPage(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->linkToRoute('contacts.page.index')
		);
	}

	public function isEnabled(): bool {
		return $this->appManager->isEnabledForUser('contacts') &&
			$this->configService->getAppValueBool(ConfigService::FRONTEND_ENABLED);
	}
}
