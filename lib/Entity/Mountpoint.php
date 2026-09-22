<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Entity;

use JsonSerializable;
use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;

/**
 * The filesystem mountpoint a member sees a circle-shared mount under, when it differs from the
 * mount's original mountpoint (e.g. to avoid a filename clash).
 *
 * Maps to the `circles_mountpoint` table. `mountpointHash` is not app-facing data: it mirrors
 * `mountPoint` (md5, or empty for the placeholder value '-') purely so the DB can enforce
 * uniqueness per member via an index - callers should not need to read or set it directly.
 */
#[Entity(name: 'circles_mountpoint')]
final class Mountpoint implements JsonSerializable {
	#[Id]
	#[Column(name: 'id', type: ColumnType::Integer)]
	public ?int $id = null;

	#[Column(name: 'mount_id', type: ColumnType::String, length: 31)]
	public string $mountId = '';

	#[Column(name: 'single_id', type: ColumnType::String, length: 31)]
	public string $singleId = '';

	#[Column(name: 'mountpoint', type: ColumnType::Text)]
	public string $mountPoint = '';

	#[Column(name: 'mountpoint_hash', type: ColumnType::String, length: 64)]
	public string $mountpointHash = '';

	public function jsonSerialize(): array {
		return [
			'mountId' => $this->mountId,
			'singleId' => $this->singleId,
			'mountPoint' => $this->mountPoint,
		];
	}
}
