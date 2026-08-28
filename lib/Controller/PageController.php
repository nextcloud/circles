<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\TeamFolderPolicy;
use OCA\Text\Event\LoadEditor;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Teams\ITeamManager;
use OCP\Util;

/**
 * Serves the Teams single-page application.
 */
class PageController extends Controller {
	public function __construct(
		IRequest $request,
		private ConfigService $configService,
		private IInitialState $initialState,
		private ITeamManager $teamManager,
		private TeamFolderPolicy $teamFolderPolicy,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/teams')]
	public function index(): TemplateResponse|NotFoundResponse {
		// The frontend can be disabled by admins; the OCS API the SPA relies on
		// refuses every request in that case, so don't serve the app shell.
		if (!$this->configService->getAppValueBool(ConfigService::FRONTEND_ENABLED)) {
			return new NotFoundResponse();
		}

		$providerAvailable = $this->teamManager->getTeamFolderProvider() !== null;
		$this->initialState->provideInitialState('teamFolderProviderAvailable', $providerAvailable);
		$this->initialState->provideInitialState(
			'teamFolderProvisioningEnabled',
			$this->teamFolderPolicy->isTeamFolderProvisioningEnabled(),
		);

		Util::addScript(Application::APP_ID, 'teams-main');
		Util::addStyle(Application::APP_ID, 'teams-main');

		// Load the Text editor so team pages can be edited inline on their
		// tabs. The class only resolves while the Text app is enabled.
		if (class_exists(LoadEditor::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadEditor());
		}

		return new TemplateResponse(Application::APP_ID, 'main');
	}

	/**
	 * Catch-all for the SPA's client-side (HTML5 history) routes so deep-link
	 * reloads still serve the app shell. $path is handled by the client router.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/teams/{path}', requirements: ['path' => '.*'], defaults: ['path' => ''])]
	public function indexPath(string $path): TemplateResponse|NotFoundResponse {
		return $this->index();
	}
}
