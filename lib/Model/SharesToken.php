<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use JsonSerializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\Share\IShare;

/**
 * Class SharesToken
 * @deprecated
 * @package OCA\Circles\Model
 */
class SharesToken implements JsonSerializable {
	use TArrayTools;


	/** @var string */
	private $circleId = '';

	/** @var string */
	private $memberId = '';

	/** @var int */
	private $accepted = IShare::STATUS_PENDING;

	/** @var string */
	private $userId = '';

	/** @var int */
	private $shareId = 0;

	/** @var string */
	private $token = '';


	/**
	 * SharesToken constructor.
	 */
	public function __construct() {
	}


	/**
	 * @return string
	 */
	public function getCircleId(): string {
		return $this->circleId;
	}

	/**
	 * @param string $circleId
	 *
	 * @return SharesToken
	 */
	public function setCircleId(string $circleId): self {
		$this->circleId = $circleId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getMemberId(): string {
		return $this->memberId;
	}

	/**
	 * @param string $memberId
	 *
	 * @return SharesToken
	 */
	public function setMemberId(string $memberId): self {
		$this->memberId = $memberId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @param string $userId
	 *
	 * @return SharesToken
	 */
	public function setUserId(string $userId): self {
		$this->userId = $userId;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getShareId(): int {
		return $this->shareId;
	}

	/**
	 * @param int $shareId
	 *
	 * @return SharesToken
	 */
	public function setShareId(int $shareId): self {
		$this->shareId = $shareId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @param string $token
	 *
	 * @return SharesToken
	 */
	public function setToken(string $token): self {
		$this->token = $token;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getAccepted(): int {
		return $this->accepted;
	}

	/**
	 * @param int $accepted
	 *
	 * @return SharesToken
	 */
	public function setAccepted(int $accepted): self {
		$this->accepted = $accepted;

		return $this;
	}


	/**
	 * @param array $data
	 */
	public function import(array $data) {
		$this->setCircleId($this->get('circle_id', $data, ''));
		$this->setMemberId($this->get('member_id', $data, ''));
		$this->setAccepted($this->getInt('accepted', $data, IShare::STATUS_PENDING));
		$this->setUserId($this->get('user_id', $data, ''));
		$this->setShareId($this->get('share_id', $data, ''));
		$this->setToken($this->get('token', $data, ''));
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'circleId' => $this->getCircleId(),
			'memberId' => $this->getMemberId(),
			'userId' => $this->getUserId(),
			'shareId' => $this->getShareId(),
			'token' => $this->getToken(),
			'accepted' => $this->getAccepted()
		];
	}
}
