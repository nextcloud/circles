<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use JsonException;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;

class TeamDashboardSettingsService {
	public const SETTING = 'teamsDashboardWidgetIds';

	public function decode(?string $value): ?array {
		if ($value === null) {
			return null;
		}
		try {
			return $this->validate(json_decode($value, false, 32, JSON_THROW_ON_ERROR));
		} catch (JsonException|OCSBadRequestException $exception) {
			throw new OCSException('Invalid dashboard settings', 500, $exception);
		}
	}

	public function validate(mixed $widgetIds, ?array $allowedIds = null): ?array {
		if ($widgetIds === null) {
			return null;
		}
		if (!is_array($widgetIds) || !array_is_list($widgetIds) || count($widgetIds) > 100) {
			throw new OCSBadRequestException('Expected at most 100 widget identifiers');
		}
		foreach ($widgetIds as $id) {
			if (!is_string($id) || strlen($id) > 64 || !preg_match('/^[a-z][a-z0-9_-]*$/D', $id)) {
				throw new OCSBadRequestException('Invalid widget identifier');
			}
			if ($allowedIds !== null && !in_array($id, $allowedIds, true)) {
				throw new OCSBadRequestException('Unknown team widget');
			}
		}
		return array_values(array_unique($widgetIds));
	}
}
