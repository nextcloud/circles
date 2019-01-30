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
			'membersLimit'          => $this->configService->getAppValue(
				ConfigService::CIRCLES_MEMBERS_LIMIT
			),
			'allowLinkedGroups'     => $this->configService->getAppValue(
				ConfigService::CIRCLES_ALLOW_LINKED_GROUPS
			),
			'allowFederatedCircles' => $this->configService->getAppValue(
				ConfigService::CIRCLES_ALLOW_FEDERATED_CIRCLES
			),
			'disableNotificationForSeenUsers' => $this->configService->getAppValue(
				ConfigService::CIRCLES_DISABLE_NOTIFICATION_FOR_SEEN_USERS
			)
		];

		return $params;
	}


	public function setSettings($members_limit, $allow_linked_groups, $allow_federated_circles, $disable_notification_for_seen_users) {
		$this->configService->setAppValue(
			ConfigService::CIRCLES_MEMBERS_LIMIT, $members_limit
		);
		$this->configService->setAppValue(
			ConfigService::CIRCLES_ALLOW_LINKED_GROUPS, $allow_linked_groups
		);
		$this->configService->setAppValue(
			ConfigService::CIRCLES_ALLOW_FEDERATED_CIRCLES, $allow_federated_circles
		);
		$this->configService->setAppValue(
			ConfigService::CIRCLES_DISABLE_NOTIFICATION_FOR_SEEN_USERS, $disable_notification_for_seen_users
		);

		return $this->getSettings();
	}

}