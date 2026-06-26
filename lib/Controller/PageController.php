<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

/**
 * Serves the Teams single-page application.
 */
class PageController extends Controller {
	public function __construct(
		IRequest $request,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(): TemplateResponse {
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
	public function indexPath(string $path): TemplateResponse {
		return $this->index();
	}
}
