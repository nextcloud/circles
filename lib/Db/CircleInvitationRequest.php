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
	 * @param CircleInvitation $circleInvitation
	 *
	 * @throws InvalidIdException
	 */
	public function save(CircleInvitation $circleInvitation): void {
		$this->confirmValidId($circleInvitation->getCircleId());

		$qb = $this->getCircleInvitationInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($circleInvitation->getCircleId()))
			->setValue('invitation_code', $qb->createNamedParameter($circleInvitation->getInvitationCode()))
			->setValue('created_by', $qb->createNamedParameter($circleInvitation->getCreatedBy()));
		$qb->executeStatement();
	}

	/**
	 * @param CircleInvitation $circleInvitation
	 *
	 * @throws InvalidIdException
	 */
	public function replace(CircleInvitation $circleInvitation): void {
		$this->delete($circleInvitation->getCircleId());
		$this->save($circleInvitation);
	}

	/**
	 * @param string $circleId
	 */
	public function delete(string $circleId): void {
		$qb = $this->getCircleInvitationDeleteSql();
		$qb->limitToCircleId($circleId);

		$qb->executeStatement();
	}
}
