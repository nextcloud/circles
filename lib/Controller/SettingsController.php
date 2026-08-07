<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Service\ConfigService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IRequest;

class SettingsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IAppConfig $appConfig,
	) {
		parent::__construct($appName, $request);
	}

	public function setValue(string $key, string $value): DataResponse {
		if ($key === ConfigLexicon::FEDERATED_TEAMS_FRONTAL) {
			if ($this->setFrontalValue($value)) {
				return $this->getValues();
			}

			return new DataResponse(['data' => ['message' => 'wrongly formated value']], Http::STATUS_BAD_REQUEST);
		}

		if ($key === ConfigLexicon::FEDERATED_TEAMS_ENABLED) {
			$this->appConfig->setAppValueBool(ConfigLexicon::FEDERATED_TEAMS_ENABLED, $value === 'yes');
			return $this->getValues();
		}

		if ($key === ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS) {
			if (!$this->isValidAllowedGroupsValue($value)) {
				return new DataResponse(['data' => ['message' => 'allowed groups must be a JSON array of group ids']], Http::STATUS_BAD_REQUEST);
			}

			$this->appConfig->setAppValueString(ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS, $value);
			return $this->getValues();
		}

		return new DataResponse(['data' => ['message' => 'unsupported key']], Http::STATUS_BAD_REQUEST);
	}

	public function getValues(): DataResponse {
		return new DataResponse([
			ConfigLexicon::FEDERATED_TEAMS_FRONTAL => $this->getFrontalValue() ?? '',
			ConfigLexicon::FEDERATED_TEAMS_ENABLED => $this->appConfig->getAppValueBool(ConfigLexicon::FEDERATED_TEAMS_ENABLED),
			ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS => $this->appConfig->getAppValueString(
				ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS,
				'[]',
			),
		]);
	}

	private function isValidAllowedGroupsValue(string $value): bool {
		$decoded = json_decode($value, true);
		if (!is_array($decoded)) {
			return false;
		}

		foreach ($decoded as $groupId) {
			if (!is_string($groupId) || $groupId === '') {
				return false;
			}
		}

		return true;
	}

	private function setFrontalValue(string $url): bool {
		[$scheme, $cloudId, $path] = $this->parseFrontalAddress($url);
		if (is_null($scheme)) {
			return false;
		}

		$this->appConfig->setAppValueString(ConfigLexicon::FEDERATED_TEAMS_FRONTAL, $url);
		$this->appConfig->setAppValueString(ConfigService::FRONTAL_CLOUD_SCHEME, $scheme);
		$this->appConfig->setAppValueString(ConfigService::FRONTAL_CLOUD_ID, $cloudId);
		$this->appConfig->setAppValueString(ConfigService::FRONTAL_CLOUD_PATH, $path);

		return true;
	}

	private function getFrontalValue(): ?string {
		if ($this->appConfig->hasAppKey(ConfigLexicon::FEDERATED_TEAMS_FRONTAL)) {
			return $this->appConfig->getAppValueString(ConfigLexicon::FEDERATED_TEAMS_FRONTAL);
		}

		if (!$this->appConfig->hasAppKey(ConfigService::FRONTAL_CLOUD_SCHEME)
			|| !$this->appConfig->hasAppKey(ConfigService::FRONTAL_CLOUD_ID)
			|| !$this->appConfig->hasAppKey(ConfigService::FRONTAL_CLOUD_PATH)) {
			return null;
		}

		return $this->appConfig->getAppValueString(ConfigService::FRONTAL_CLOUD_SCHEME) . '://'
			. $this->appConfig->getAppValueString(ConfigService::FRONTAL_CLOUD_ID)
			. $this->appConfig->getAppValueString(ConfigService::FRONTAL_CLOUD_PATH) . '/';
	}

	private function parseFrontalAddress(string $url): ?array {
		$scheme = parse_url($url, PHP_URL_SCHEME);
		$cloudId = parse_url($url, PHP_URL_HOST);
		$cloudIdPort = parse_url($url, PHP_URL_PORT);
		$path = parse_url($url, PHP_URL_PATH);

		if (is_bool($scheme) || is_bool($cloudId) || is_null($scheme) || is_null($cloudId)) {
			return null;
		}

		if (is_null($path) || is_bool($path)) {
			$path = '';
		}
		$path = rtrim($path, '/');
		if (!is_null($cloudIdPort)) {
			$cloudId .= ':' . ((string)$cloudIdPort);
		}

		return [$scheme, $cloudId, $path];
	}
}
