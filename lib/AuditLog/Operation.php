<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\AuditLog;

/**
 * Operations Teams writes to the admin audit log, in the form `app.entity.action`
 *
 * The values are matched by log consumers (filters, alert rules, SIEM imports),
 * so changing one is a breaking change for them. Case names may be renamed.
 */
enum Operation: string {
	case TeamCreated = 'circles.team.created';
	case TeamDeleted = 'circles.team.deleted';
	case TeamRenamed = 'circles.team.renamed';
	case TeamConfigUpdated = 'circles.team_config.updated';
	case TeamSettingUpdated = 'circles.team_setting.updated';
	case InvitationCreated = 'circles.invitation.created';
	case InvitationRevoked = 'circles.invitation.revoked';

	/**
	 * Short description of when the operation is logged, used for the documentation
	 */
	public function description(): string {
		return match ($this) {
			self::TeamCreated => 'A team was created',
			self::TeamDeleted => 'A team was deleted, directly or because its last member left it',
			self::TeamRenamed => 'A team was renamed',
			self::TeamConfigUpdated => 'The configuration flags of a team (open, invite only, visible, federated, ...) were changed',
			self::TeamSettingUpdated => 'A setting of a team, for example the password of its shares, was set or removed',
			self::InvitationCreated => 'An invitation link to join a team was created',
			self::InvitationRevoked => 'The invitation link to join a team was revoked',
		};
	}
}
