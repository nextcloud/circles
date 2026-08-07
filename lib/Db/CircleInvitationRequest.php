<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Model\CircleInvitation;

/**
 * Class CircleInvitationRequest
 *
 * @package OCA\Circles\Db
 */
class CircleInvitationRequest extends CircleRequestBuilder {
	/**
	 * @throws InvalidIdException
	 */
	public function save(CircleInvitation $circleInvitation): void {
		$this->confirmValidId($circleInvitation->getCircleId());

		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_INVITATIONS)
			->setValue('created', $qb->createNamedParameter($this->timezoneService->getUTCDate()));
		$qb->setValue('circle_id', $qb->createNamedParameter($circleInvitation->getCircleId()))
			->setValue('invitation_code', $qb->createNamedParameter($circleInvitation->getInvitationCode()))
			->setValue('created_by', $qb->createNamedParameter($circleInvitation->getCreatedBy()));
		$qb->executeStatement();
	}

	/**
	 * @throws InvalidIdException
	 */
	public function replace(CircleInvitation $circleInvitation): void {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_INVITATIONS);
		$qb->limitToCircleId($circleInvitation->getCircleId());
		$qb->executeStatement();

		$this->save($circleInvitation);
	}

	public function delete(string $circleId): void {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_INVITATIONS);
		$qb->limitToCircleId($circleId);
		$qb->executeStatement();
	}
}
