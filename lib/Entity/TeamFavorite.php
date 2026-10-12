<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Entity;

use DateTime;
use DateTimeZone;
use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity as EntityAttribute;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;

#[EntityAttribute(name: 'circles_team_favorites')]
final class TeamFavorite {
	#[Id]
	#[Column(name: 'id', type: ColumnType::Integer)]
	public ?int $id = null;

	#[Column(name: 'user_id', type: ColumnType::String, length: 255)]
	public string $userId = '';

	#[Column(name: 'circle_id', type: ColumnType::String, length: 32)]
	public string $circleId = '';

	#[Column(name: 'position', type: ColumnType::Integer)]
	public int $position = 0;

	#[Column(name: 'created', type: ColumnType::Datetime)]
	public DateTime $created;

	public function __construct() {
		$this->created = new DateTime('now', new DateTimeZone('UTC'));
	}
}
