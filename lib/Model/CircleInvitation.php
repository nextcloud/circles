<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use OCA\Circles\Exceptions\CircleInvitationNotFoundException;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TDeserialize;

class CircleInvitation extends ManagedModel implements IDeserializable, IQueryRow, \JsonSerializable {
	use TArrayTools;
	use TDeserialize;

	private string $circleId = '';
	private string $invitationCode = '';
	private string $createdBy = '';
	private int $created = 0;

	public function setCircleId(string $circleId): self {
		$this->circleId = $circleId;

		return $this;
	}

	public function getCircleId(): string {
		return $this->circleId;
	}

	public function setInvitationCode(string $invitationCode): self {
		$this->invitationCode = $invitationCode;

		return $this;
	}

	public function getInvitationCode(): string {
		return $this->invitationCode;
	}

	public function setCreatedBy(string $createdBy): self {
		$this->createdBy = $createdBy;

		return $this;
	}

	public function getCreatedBy(): string {
		return $this->createdBy;
	}

	public function setCreated(int $created): self {
		$this->created = $created;

		return $this;
	}

	public function getCreated(): int {
		return $this->created;
	}

	public function import(array $data): IDeserializable {
		if (!$this->get('circleId', $data)) {
			throw new InvalidItemException();
		}

		$this->setCircleId($this->get('circleId', $data))
			->setInvitationCode($this->get('invitationCode', $data))
			->setCreatedBy($this->get('createdBy', $data))
			->setCreated($this->getInt('created', $data));

		return $this;
	}


	public function jsonSerialize(): array {
		$arr = [
			'circleId' => $this->getCircleId(),
			'invitationCode' => $this->getInvitationCode(),
			'createdBy' => $this->getCreatedBy(),
			'created' => $this->getCreated(),
		];

		return $arr;
	}


	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if (!$this->get($prefix . 'circle_id', $data)) {
			throw new CircleInvitationNotFoundException();
		}

		$this->setCircleId($this->get($prefix . 'circle_id', $data))
			->setInvitationCode($this->get($prefix . 'invitation_code', $data))
			->setCreatedBy($this->get($prefix . 'created_by', $data));

		$created = $this->get($prefix . 'created', $data);
		$dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $created);
		$timestamp = $dateTime ? $dateTime->getTimestamp() : (int)strtotime($created);
		$this->setCreated($timestamp);

		return $this;
	}
}
