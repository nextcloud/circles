<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Entity;

use DateTime;
use DateTimeZone;
use JsonSerializable;
use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;

/**
 * A pending invitation to join a circle, identified by its own invitation code.
 */
#[Entity(name: 'circles_invitations')]
final class CircleInvitation implements JsonSerializable {
	#[Id]
	#[Column(name: 'id', type: ColumnType::Integer)]
	public ?int $id = null;

	#[Column(name: 'circle_id', type: ColumnType::String, length: 32)]
	public string $circleId = '';

	#[Column(name: 'invitation_code', type: ColumnType::String, length: 16)]
	public string $invitationCode = '';

	#[Column(name: 'created_by', type: ColumnType::String, length: 255)]
	public string $createdBy = '';

	#[Column(name: 'created', type: ColumnType::Datetime)]
	public DateTime $created;

	public function __construct() {
		$this->created = new DateTime('now', new DateTimeZone('UTC'));
	}

	public function jsonSerialize(): array {
		return [
			'circleId' => $this->circleId,
			'invitationCode' => $this->invitationCode,
			'createdBy' => $this->createdBy,
			'created' => $this->created->getTimestamp(),
		];
	}
}
