<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use OCA\Circles\Exceptions\CircleInvitationNotFoundException;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TDeserialize;

class CircleInvitation extends ManagedModel implements IDeserializable, IQueryRow, \JsonSerializable {
	use TArrayTools;
	use TDeserialize;

	/** @var string */
	private $circleId;

	/** @var string */
	private $invitationCode;

	/** @var string */
	private $createdBy;

	/** @var int */
	private $created;

	/**
	 * @param string $circleId
	 *
	 * @return self
	 */
	public function setCircleId(string $circleId): self {
		$this->circleId = $circleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCircleId(): string {
		return $this->circleId;
	}

	/**
	 * @param string $invitationCode
	 *
	 * @return self
	 */
	public function setInvitationCode(string $invitationCode): self {
		$this->invitationCode = $invitationCode;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInvitationCode(): string {
		return $this->invitationCode;
	}

	/**
	 * @param string $createdBy
	 *
	 * @return self
	 */
	public function setCreatedBy(string $createdBy): self {
		$this->createdBy = $createdBy;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCreatedBy(): string {
		return $this->createdBy;
	}

	/**
	 * @param int $created
	 *
	 * @return self
	 */
	public function setCreated(int $created): self {
		$this->created = $created;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCreated(): int {
		return $this->created;
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 * @throws InvalidItemException
	 */
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


	/**
	 * @return array
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function jsonSerialize(): array {
		$arr = [
			'circleId' => $this->getCircleId(),
			'invitationCode' => $this->getInvitationCode(),
			'createdBy' => $this->getCreatedBy(),
			'created' => $this->getCreated(),
		];

		return $arr;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IQueryRow
	 * @throws CircleNotFoundException
	 */
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
