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
	public const REMOVE_SHARE_TOKENS_DONE = 'remove_share_tokens_done';
	public const TEAM_FOLDER_AUTO_CREATE = 'team_folder_auto_create';
	public const TEAM_FOLDER_DEFAULT_QUOTA = 'team_folder_default_quota';

	// OIDC
	public const OIDC_ENABLED = 'oidc_enabled';
	public const OIDC_ISSUER = 'oidc_issuer';
	public const OIDC_CLIENT_ID = 'oidc_client_id';
	public const OIDC_CLIENT_SECRET = 'oidc_client_secret';
	public const OIDC_AUTHORIZATION_ENDPOINT = 'oidc_authorization_endpoint';
	public const OIDC_TOKEN_ENDPOINT = 'oidc_token_endpoint';
	public const OIDC_USERINFO_ENDPOINT = 'oidc_userinfo_endpoint';
	public const OIDC_SCOPE = 'oidc_scope';
	public const OIDC_MEMBERSHIP_CLAIM = 'oidc_membership_claim';

	// SCIM
	public const SCIM_ENABLED = 'scim_enabled';
	public const SCIM_ENDPOINT = 'scim_endpoint';
	public const SCIM_TOKEN = 'scim_token';
	public const SCIM_ALLOW_FEDERATED_MODERATOR = 'scim_allow_federated_moderator';
	public const SCIM_FEDERATED_MODERATOR_INSTANCES = 'scim_federated_moderator_instances';

	// Federation agent
	public const FEDERATION_AGENT_ENABLED = 'federation_agent_enabled';
	public const FEDERATION_AGENT_LOCAL_ID = 'federation_agent_local_id';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(key: self::FEDERATED_TEAMS_ENABLED, type: ValueType::BOOL, defaultRaw: false, definition: 'disable/enable Federated Teams', lazy: true),
			new Entry(key: self::FEDERATED_TEAMS_FRONTAL, type: ValueType::STRING, defaultRaw: '', definition: 'domain name used to auth public request', lazy: true),
			new Entry(key: self::REMOVE_SHARE_TOKENS_DONE, type: ValueType::BOOL, defaultRaw: false, definition: 'whether the remove share tokens repair step has already been executed', lazy: true),
			new Entry(key: self::TEAM_FOLDER_AUTO_CREATE, type: ValueType::BOOL, defaultRaw: true, definition: 'automatically create a team folder when a new team is created', lazy: true),
			new Entry(key: self::TEAM_FOLDER_DEFAULT_QUOTA, type: ValueType::INT, defaultRaw: 0, definition: 'default quota in bytes for auto-created team folders (0 means unlimited)', lazy: true),
			// OIDC
			new Entry(key: self::OIDC_ENABLED, type: ValueType::BOOL, defaultRaw: false, definition: 'disable/enable OIDC integration', lazy: true),
			new Entry(key: self::OIDC_ISSUER, type: ValueType::STRING, defaultRaw: '', definition: 'OIDC provider issuer URL', lazy: true),
			new Entry(key: self::OIDC_CLIENT_ID, type: ValueType::STRING, defaultRaw: '', definition: 'OIDC client id', lazy: true),
			new Entry(key: self::OIDC_CLIENT_SECRET, type: ValueType::STRING, defaultRaw: '', definition: 'OIDC client secret', lazy: true),
			new Entry(key: self::OIDC_AUTHORIZATION_ENDPOINT, type: ValueType::STRING, defaultRaw: '', definition: 'OIDC authorization endpoint', lazy: true),
			new Entry(key: self::OIDC_TOKEN_ENDPOINT, type: ValueType::STRING, defaultRaw: '', definition: 'OIDC token endpoint', lazy: true),
			new Entry(key: self::OIDC_USERINFO_ENDPOINT, type: ValueType::STRING, defaultRaw: '', definition: 'OIDC userinfo endpoint', lazy: true),
			new Entry(key: self::OIDC_SCOPE, type: ValueType::STRING, defaultRaw: 'openid', definition: 'OIDC scope(s) requested during authorization', lazy: true),
			new Entry(key: self::OIDC_MEMBERSHIP_CLAIM, type: ValueType::STRING, defaultRaw: '', definition: 'claim name containing group membership information', lazy: true),
			// SCIM
			new Entry(key: self::SCIM_ENABLED, type: ValueType::BOOL, defaultRaw: false, definition: 'disable/enable SCIM integration', lazy: true),
			new Entry(key: self::SCIM_ENDPOINT, type: ValueType::STRING, defaultRaw: '', definition: 'SCIM server endpoint for group discovery', lazy: true),
			new Entry(key: self::SCIM_TOKEN, type: ValueType::STRING, defaultRaw: '', definition: 'bearer token used to authenticate against the SCIM server', lazy: true),
			new Entry(key: self::SCIM_ALLOW_FEDERATED_MODERATOR, type: ValueType::BOOL, defaultRaw: true, definition: 'whether circles created from SCIM groups accept a federated moderator', lazy: true),
			new Entry(key: self::SCIM_FEDERATED_MODERATOR_INSTANCES, type: ValueType::ARRAY, defaultRaw: [], definition: 'list of remote instances whose federation agent is trusted as a moderator on this instance\'s SCIM circles', lazy: true),
			// Federation agent
			new Entry(key: self::FEDERATION_AGENT_ENABLED, type: ValueType::BOOL, defaultRaw: false, definition: 'disable/enable the federation agent, used to grant remote instances moderator rights via SCIM/OIDC-based mechanisms', lazy: true),
			new Entry(key: self::FEDERATION_AGENT_LOCAL_ID, type: ValueType::STRING, defaultRaw: '', definition: "single ID of this instance's own federated moderator circle", lazy: true),
		];
	}

	public function getUserConfigs(): array {
		return [
			new Entry(key: self::USER_SINGLE_ID, type: ValueType::STRING, defaultRaw: '', definition: 'caching singleId for each local account', lazy: false, flags: IUserConfig::FLAG_INDEXED),
		];
	}
}
