<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Log\Audit\CriticalActionPerformedEvent;

/**
 * Writes the actions done in Teams to the admin audit log
 */
class AuditLogService {
	public function __construct(
		private readonly IEventDispatcher $eventDispatcher,
		private readonly ConfigService $configService,
	) {
	}

	/**
	 * @param string $message format string, its placeholders are filled with the first values of $parameters in order
	 * @param array<string, scalar|null> $parameters
	 */
	public function log(Operation $operation, string $message, array $parameters): void {
		$this->eventDispatcher->dispatchTyped(
			new CriticalActionPerformedEvent($message, $parameters, false, $operation->value)
		);
	}

	/**
	 * Logs a change of a team, only on the instance owning the team: the other instances
	 * of a federated team apply the same change when it is broadcast to them.
	 *
	 * Teams mirroring a Nextcloud group are not logged, their changes come from the group
	 * which is already audited.
	 *
	 * @param array<string, scalar|null> $parameters
	 */
	public function logTeamChange(Circle $circle, Operation $operation, string $message, array $parameters): void {
		if ($circle->getSource() === Member::TYPE_GROUP || !$this->isOwnedLocally($circle)) {
			return;
		}

		$this->log($operation, $message, $parameters);
	}

	/**
	 * The user who did the change. Changes are often applied in a background request
	 * without a user session, so the audit log cannot tell the user otherwise.
	 */
	public function getInitiator(Circle $circle): ?string {
		if (!$circle->hasInitiator()) {
			return null;
		}

		return $this->getUserId($circle->getInitiator());
	}

	/**
	 * User, group, team or app ID of a member, with the instance when it is remote.
	 * Email addresses and contacts are not logged, the member ID identifies them.
	 */
	public function getUserId(IFederatedUser $user): ?string {
		if ($user->getUserType() === Member::TYPE_MAIL || $user->getUserType() === Member::TYPE_CONTACT) {
			return null;
		}

		return $this->configService->displayFederatedUser($user);
	}

	/**
	 * Parameters identifying a member, for messages starting with `Member "%s" (%s, member %s)`
	 *
	 * @return array{userId: ?string, memberType: string, memberId: string}
	 */
	public function getMemberParameters(Member $member): array {
		return [
			'userId' => $this->getUserId($member),
			'memberType' => Member::$TYPE[$member->getUserType()] ?? (string)$member->getUserType(),
			'memberId' => $member->getId(),
		];
	}

	public function getLevelName(int $level): string {
		return Member::$DEF_LEVEL[$level] ?? (string)$level;
	}

	public function getTeamName(Circle $circle): string {
		return $circle->getDisplayName() !== '' ? $circle->getDisplayName() : $circle->getName();
	}

	private function isOwnedLocally(Circle $circle): bool {
		try {
			return $this->configService->isLocalInstance($circle->getInstance());
		} catch (OwnerNotFoundException) {
			return true;
		}
	}
}
