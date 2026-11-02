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
	case InvitationCodeRejected = 'circles.invitation_code.rejected';
	case MemberAdded = 'circles.member.added';
	case MemberInvited = 'circles.member.invited';
	case MemberRemoved = 'circles.member.removed';
	case MemberLevelUpdated = 'circles.member_level.updated';
	case MemberJoined = 'circles.member.joined';
	case MemberRequested = 'circles.member.requested';
	case MemberLeft = 'circles.member.left';
	case RemoteInstanceAdded = 'circles.remote_instance.added';
	case RemoteInstanceUpdated = 'circles.remote_instance.updated';
	case RemoteInstanceRemoved = 'circles.remote_instance.removed';
	case RemoteRequestRejected = 'circles.remote_request.rejected';
	case ScimInstanceAdded = 'circles.scim_instance.added';
	case ScimInstanceRemoved = 'circles.scim_instance.removed';
	case OidcAccountLinked = 'circles.oidc_account.linked';
	case OidcAccountLinkFailed = 'circles.oidc_account.link_failed';
	case ConfigUpdated = 'circles.config.updated';

	/**
	 * Short description of when the operation is logged, used for the documentation
	 */
	public function description(): string {
		return match ($this) {
			self::TeamCreated => 'A team was created',
			self::TeamDeleted => 'A team was deleted',
			self::TeamRenamed => 'A team was renamed',
			self::TeamConfigUpdated => 'The configuration flags of a team (open, invite only, visible, federated, ...) were changed',
			self::TeamSettingUpdated => 'A setting of a team, for example the password of its shares, was set or removed',
			self::InvitationCreated => 'An invitation link to join a team was created',
			self::InvitationRevoked => 'The invitation link to join a team was revoked',
			self::InvitationCodeRejected => 'An invitation code that matches no team was used to look up or join a team',
			self::MemberAdded => 'A member was added to a team, or a request to join it was accepted',
			self::MemberInvited => 'A user, group or team was invited to join an invite only team',
			self::MemberRemoved => 'A member was removed from a team',
			self::MemberLevelUpdated => 'The level of a member of a team (member, moderator, admin, owner) was changed, including ownership transfers',
			self::MemberJoined => 'A user joined an open team, through an invitation link or by accepting an invitation',
			self::MemberRequested => 'A user requested to join a team that requires confirmation',
			self::MemberLeft => 'A member left a team',
			self::RemoteInstanceAdded => 'A remote instance was added for federated teams, with the type defining how much it is trusted',
			self::RemoteInstanceUpdated => 'The identity or the type of a known remote instance for federated teams was changed',
			self::RemoteInstanceRemoved => 'A remote instance for federated teams was removed',
			self::RemoteRequestRejected => 'A federated teams request was rejected',
			self::ScimInstanceAdded => 'A remote instance was allowed to add members to teams managed by SCIM',
			self::ScimInstanceRemoved => 'A remote instance was no longer allowed to add members to teams managed by SCIM',
			self::OidcAccountLinked => 'A user linked their OIDC account',
			self::OidcAccountLinkFailed => 'Linking an OIDC account failed',
			self::ConfigUpdated => 'An admin setting of Teams (federated teams, frontal address, team folder quotas) was changed',
		};
	}
}
