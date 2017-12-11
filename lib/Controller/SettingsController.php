<?php

namespace OCA\Circles\Controller;

use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Http\Client\IClientService;
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
	 * @return TemplateResponse
	 */
	public function admin() {
		return new TemplateResponse($this->appName, 'settings.admin', [], 'blank');
	}


	/**
	 * @NoCSRFRequired
	 */
	public function getSettings() {
		$params = [
			'allowLinkedGroups' => $this->configService->getAppValue(
				ConfigService::CIRCLES_ALLOW_LINKED_GROUPS
			),
			'allowFederatedCircles' => $this->configService->getAppValue(
				ConfigService::CIRCLES_ALLOW_FEDERATED_CIRCLES
			),
			'enableAudit' => $this->configService->getAppValue(
				ConfigService::CIRCLES_ENABLE_AUDIT
			)
		];

		return $params;
	}


	public function setSettings($allow_linked_groups, $allow_federated_circles, $enable_audit) {
		$this->configService->setAppValue(
			ConfigService::CIRCLES_ALLOW_LINKED_GROUPS, $allow_linked_groups
		);
		$this->configService->setAppValue(
			ConfigService::CIRCLES_ALLOW_FEDERATED_CIRCLES, $allow_federated_circles
		);
		$this->configService->setAppValue(
			ConfigService::CIRCLES_ENABLE_AUDIT, $enable_audit
		);

		return $this->getSettings();
	}

}