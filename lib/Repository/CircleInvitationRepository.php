<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Repository;

use OCA\Circles\Entity\CircleInvitation;
use OCA\Circles\Exceptions\InvalidIdException;
use OCP\AppFramework\ORM\Repository;

/**
 * @extends Repository<CircleInvitation>
 */
class CircleInvitationRepository extends Repository {
	public const string entityClass = CircleInvitation::class;

	/**
	 * Replaces the circle's current invitation (if any) with a new one, since a circle can only
	 * have one active invitation at a time.
	 *
	 * @throws InvalidIdException
	 */
	public function replace(CircleInvitation $circleInvitation): CircleInvitation {
		if (strlen($circleInvitation->circleId) < 14) {
			throw new InvalidIdException();
		}

		$this->deleteBy(['circleId' => $circleInvitation->circleId]);

		return $this->insert($circleInvitation);
	}
}
