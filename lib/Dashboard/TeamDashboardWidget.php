<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Dashboard;

use OCA\Circles\Exceptions\FrontendException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\ModelManager;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCP\App\IAppManager;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IConditionalWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetItems;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class TeamDashboardWidget implements IAPIWidgetV2, IIconWidget, IButtonWidget, IConditionalWidget {
	public function __construct(
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		private CircleService $circleService,
		private ModelManager $modelManager,
		private FederatedUserService $federatedUserService,
		private ConfigService $configService,
		private IUserSession $userSession,
		private IAppManager $appManager,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems {
		$circles = [];

		try {
			if (!$this->configService->getAppValueBool(ConfigService::FRONTEND_ENABLED)) {
				throw new FrontendException('frontend disabled');
			}

			$user = $this->userSession->getUser();
			$this->federatedUserService->setLocalCurrentUser($user);

			$probe = new CircleProbe();
			$probe->filterHiddenCircles()
				->filterBackendCircles()
				->setItemsLimit($limit)
				->setItemsOffset($since ? (int)$since : 0);

			$circles = array_map(function (Circle $circle) {
				return new WidgetItem(
					$circle->getDisplayName(),
					'',
					$this->urlGenerator->getAbsoluteURL($this->modelManager->generateLinkToCircle($circle->getSingleId())),
					$this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute('core.GuestAvatar.getAvatar', ['guestName' => $circle->getSanitizedName(), 'size' => 64]))
				);
			}, $this->circleService->probeCircles($probe));
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
		}
		return new WidgetItems($circles);
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
		return 'Teams';
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
