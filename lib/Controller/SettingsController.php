<?php

namespace OCA\Circles\Controller;

use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;


class SettingsController extends Controller {

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	public function __construct(
		$appName, IRequest $request, ConfigService $configService, MiscService $miscService
	) {
		parent::__construct($appName, $request);
		$this->configService = $configService;
		$this->miscService = $miscService;
	}

	/**
	 * @NoCSRFRequired
	 */
	public function admin() {
		return new TemplateResponse($this->appName, 'settings.admin', [], 'blank');
	}


	public function getSettings() {
		$params = [
			'allowFederatedCircles' => $this->configService->getAppValue(
				ConfigService::CIRCLES_ALLOW_FEDERATED
			)
		];

		return $params;
	}


	public function setSettings($allow_federated_circles) {
		$this->configService->setAppValue(
			ConfigService::CIRCLES_ALLOW_FEDERATED, $allow_federated_circles
		);

		return $this->getSettings();
	}
}