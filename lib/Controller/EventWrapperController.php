<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Controller;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventWrapperService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\RemoteDownstreamService;
use OCA\Circles\Service\RemoteUpstreamService;
use OCA\Circles\Tools\Traits\TAsync;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IAppConfig;
use OCP\IRequest;

/**
 * Class EventWrapperController
 *
 * @package OCA\Circles\Controller
 */
class EventWrapperController extends Controller {
	use TStringTools;
	use TAsync;


	/** @var EventWrapperService */
	private $eventWrapperService;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var RemoteUpstreamService */
	private $remoteUpstreamService;

	/** @var RemoteDownstreamService */
	private $remoteDownstreamService;

	/** @var ConfigService */
	private $configService;


	/**
	 * EventWrapperController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param EventWrapperService $eventWrapperService
	 * @param FederatedEventService $federatedEventService
	 * @param RemoteUpstreamService $remoteUpstreamService
	 * @param RemoteDownstreamService $remoteDownstreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IAppConfig $appConfig,
		EventWrapperService $eventWrapperService,
		FederatedEventService $federatedEventService,
		RemoteUpstreamService $remoteUpstreamService,
		RemoteDownstreamService $remoteDownstreamService,
		ConfigService $configService,
	) {
		parent::__construct($appName, $request);
		$this->eventWrapperService = $eventWrapperService;
		$this->federatedEventService = $federatedEventService;
		$this->remoteUpstreamService = $remoteUpstreamService;
		$this->remoteDownstreamService = $remoteDownstreamService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
		$this->setupInt(self::$SETUP_TIME_LIMIT, 900);
	}


	/**
	 * Called locally.
	 *
	 * Async process and broadcast the event to every instances of GS
	 * This should be initiated by the instance that owns the Circles.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 *
	 * @return DataResponse
	 */
	public function asyncBroadcast(string $token): DataResponse {
		$wrappers = $this->remoteUpstreamService->getEventsByToken($token);
		if (empty($wrappers) && $token !== 'test-dummy-token') {
			return new DataResponse([], Http::STATUS_OK);
		}

		if ($token === 'test-dummy-token' && $this->appConfig->getValueInt(Application::APP_ID, 'test_dummy_token') < time()) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		// closing socket, keep current process running.
		$this->async();

		foreach ($wrappers as $wrapper) {
			$this->eventWrapperService->manageWrapper($wrapper);
		}

		$this->eventWrapperService->confirmStatus($token);

		// so circles:check can check async is fine
		if ($token === 'test-dummy-token') {
			sleep(4);
		}

		// exit() or useless log will be generated
		exit();
	}

}
