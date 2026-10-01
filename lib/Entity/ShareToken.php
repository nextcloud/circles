<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Entity;

use JsonSerializable;
use OCA\Circles\Service\InterfaceService;
use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;
use OCP\Server;
use OCP\Share\IShare;

/**
 * Grants a mail or contact member access to a file shared with a circle, via a dedicated,
 * optionally password-protected share token.
 *
 * `oc_share` has one row per circle-level share and one `token` column, meant for a single
 * public link. That doesn't work for circles: mail/contact members have no Nextcloud account
 * to authenticate an ACL check with, so each one needs their own individually accessible link.
 */
#[Entity(name: 'circles_token')]
final class ShareToken implements JsonSerializable {
	#[Id]
	#[Column(name: 'id', type: ColumnType::Integer)]
	public ?int $id = null;

	#[Column(name: 'share_id', type: ColumnType::Integer)]
	public int $shareId = 0;

	#[Column(name: 'circle_id', type: ColumnType::String, length: 31)]
	public string $circleId = '';

	#[Column(name: 'single_id', type: ColumnType::String, length: 31)]
	public string $singleId = '';

	#[Column(name: 'member_id', type: ColumnType::String, length: 31)]
	public string $memberId = '';

	#[Column(name: 'token', type: ColumnType::String, length: 31)]
	public string $token = '';

	#[Column(name: 'password', type: ColumnType::String, length: 127)]
	public string $password = '';

	#[Column(name: 'accepted', type: ColumnType::Integer)]
	public int $accepted = IShare::STATUS_PENDING;

	public function getLink(InterfaceService $interfaceService): string {
		return $interfaceService->getFrontalPath(
			'files_sharing.sharecontroller.showShare',
			['token' => $this->token]
		);
	}

	public function jsonSerialize(): array {
		return [
			'shareId' => $this->shareId,
			'circleId' => $this->circleId,
			'singleId' => $this->singleId,
			'memberId' => $this->memberId,
			'token' => $this->token,
			'password' => $this->password,
			'accepted' => $this->accepted,
			'link' => $this->getLink(Server::get(InterfaceService::class)),
		];
	}
}
