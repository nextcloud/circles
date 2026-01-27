<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

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
		if ($key === 'frontal_cloud') {
			if ($this->setFrontalValue($value)) {
				return $this->getValues();
			}

			return new DataResponse(['data' => ['message' => 'wrongly formated value']], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse(['data' => ['message' => 'unsupported key']], Http::STATUS_BAD_REQUEST);
	}

	public function getValues(): DataResponse {
		return new DataResponse([
			'frontal_cloud' => $this->getFrontalValue() ?? '',
		]);
	}

	private function setFrontalValue(string $url): bool {
		[$scheme, $cloudId, $path] = $this->parseFrontalAddress($url);
		if (is_null($scheme)) {
			return false;
		}

		$this->appConfig->setAppValueString(ConfigService::FRONTAL_CLOUD_SCHEME, $scheme);
		$this->appConfig->setAppValueString(ConfigService::FRONTAL_CLOUD_ID, $cloudId);
		$this->appConfig->setAppValueString(ConfigService::FRONTAL_CLOUD_PATH, $path);

		return true;
	}

	private function getFrontalValue(): ?string {
		if (!$this->appConfig->hasAppKey(ConfigService::FRONTAL_CLOUD_SCHEME)
			|| !$this->appConfig->hasAppKey(ConfigService::FRONTAL_CLOUD_ID)
			|| !$this->appConfig->hasAppKey(ConfigService::FRONTAL_CLOUD_PATH)) {
			return null;
		}

		return $this->appConfig->getAppValueString(ConfigService::FRONTAL_CLOUD_SCHEME) . '://' .
			$this->appConfig->getAppValueString(ConfigService::FRONTAL_CLOUD_ID) .
			$this->appConfig->getAppValueString(ConfigService::FRONTAL_CLOUD_PATH) . '/';
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
