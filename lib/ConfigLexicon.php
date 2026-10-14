<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles;

use OCP\Config\IUserConfig;
use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for circles/teams.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 */
class ConfigLexicon implements ILexicon {
	public const USER_SINGLE_ID = 'userSingleId';
	public const FEDERATED_TEAMS_ENABLED = 'federated_teams_enabled';
	public const FEDERATED_TEAMS_FRONTAL = 'federated_teams_frontal';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(key: self::FEDERATED_TEAMS_ENABLED, type: ValueType::BOOL, defaultRaw: false, definition: 'disable/enable Federated Teams', lazy: true),
			new Entry(key: self::FEDERATED_TEAMS_FRONTAL, type: ValueType::STRING, defaultRaw: '', definition: 'domain name used to auth public request', lazy: true),
		];
	}

	public function getUserConfigs(): array {
		return [
			new Entry(key: self::USER_SINGLE_ID, type: ValueType::STRING, defaultRaw: '', definition: 'caching singleId for each local account', lazy: false, flags: IUserConfig::FLAG_INDEXED),
		];
	}
}
