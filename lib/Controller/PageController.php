<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Service\ConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

/**
 * Serves the Teams single-page application.
 */
class PageController extends Controller {
	public function __construct(
		IRequest $request,
		private ConfigService $configService,
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

		Util::addScript(Application::APP_ID, 'teams-main');
		Util::addStyle(Application::APP_ID, 'teams-main');

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
