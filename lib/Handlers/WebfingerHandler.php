<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Handlers;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;
use OCP\Http\WellKnown\JrdResponse;
use OCP\IURLGenerator;

/**
 * Class WebfingerHandler
 *
 * @package OCA\Circles\Handlers
 */
class WebfingerHandler implements IHandler {
	use TArrayTools;

	public function __construct(
		private IURLGenerator $urlGenerator,
		private RemoteStreamService $remoteStreamService,
		private InterfaceService $interfaceService,
		private ConfigService $configService,
	) {
	}

	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
		if ($service !== 'webfinger') {
			return $previousResponse;
		}

		$request = $context->getHttpRequest();
		$subject = $request->getParam('resource', '');
		if ($subject !== Application::APP_SUBJECT) {
			return $previousResponse;
		}

		$token = $request->getParam('check', '');

		$response = $previousResponse;
		if (!($response instanceof JrdResponse)) {
			$response = new JrdResponse($subject);
		}

		try {
			$this->interfaceService->setCurrentInterfaceFromRequest($request, $request->getParam('test', ''));
			$this->remoteStreamService->getAppSignatory();
			$href = $this->interfaceService->getCloudPath('circles.Remote.appService');
			$info = [
				'app' => Application::APP_ID,
				'name' => Application::APP_NAME,
				'token' => Application::APP_TOKEN,
				'version' => $this->configService->getAppValue('installed_version'),
				'api' => Application::APP_API
			];
		} catch (UnknownInterfaceException|SignatoryException $e) {
			return $response;
		}

		return $response->addLink(Application::APP_REL, 'application/json', $href, [], $info);
	}
}
