<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Model;

use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\ShareTokenNotFoundException;
use OCP\Share\IShare;

class ShareToken implements IDeserializable, IQueryRow, JsonSerializable {
	use TArrayTools;


	/** @var int */
	private $dbId = 0;

	/** @var int */
	private $shareId = 0;

	/** @var string */
	private $circleId = '';

	/** @var string */
	private $singleId = '';

	/** @var string */
	private $memberId = '';

	/** @var string */
	private $token = '';

	/** @var string */
	private $password = '';

	/** @var int */
	private $accepted = IShare::STATUS_PENDING;

	/** @var string */
	private $link = '';


	/**
	 * ShareToken constructor.
	 */
	public function __construct() {
	}


	/**
	 * @param int $dbId
	 *
	 * @return ShareToken
	 */
	public function setDbId(int $dbId): self {
		$this->dbId = $dbId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getDbId(): int {
		return $this->dbId;
	}


	/**
	 * @param int $shareId
	 *
	 * @return ShareToken
	 */
	public function setShareId(int $shareId): self {
		$this->shareId = $shareId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getShareId(): int {
		return $this->shareId;
	}


	/**
	 * @param string $circleId
	 *
	 * @return ShareToken
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
	 * @param string $singleId
	 *
	 * @return ShareToken
	 */
	public function setSingleId(string $singleId): self {
		$this->singleId = $singleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSingleId(): string {
		return $this->singleId;
	}


	/**
	 * @param string $memberId
	 *
	 * @return ShareToken
	 */
	public function setMemberId(string $memberId): self {
		$this->memberId = $memberId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMemberId(): string {
		return $this->memberId;
	}


	/**
	 * @param string $token
	 *
	 * @return ShareToken
	 */
	public function setToken(string $token): self {
		$this->token = $token;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}


	/**
	 * @param string $password
	 *
	 * @return ShareToken
	 */
	public function setPassword(string $password): self {
		$this->password = $password;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPassword(): string {
		return $this->password;
	}


	/**
	 * @param int $accepted
	 *
	 * @return ShareToken
	 */
	public function setAccepted(int $accepted): self {
		$this->accepted = $accepted;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getAccepted(): int {
		return $this->accepted;
	}


	/**
	 * @param string $link
	 *
	 * @return ShareToken
	 */
	public function setLink(string $link): self {
		$this->link = $link;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLink(): string {
		return $this->link;
	}


	/**
	 * @param array $data
	 *
	 * @return ShareToken
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		if ($this->getInt('shareId', $data) === 0) {
			throw new InvalidItemException();
		}

		$this->setShareId($this->getInt('shareId', $data));
		$this->setCircleId($this->get('circleId', $data));
		$this->setSingleId($this->get('singleId', $data));
		$this->setMemberId($this->get('memberId', $data));
		$this->setToken($this->get('token', $data));
		$this->setPassword($this->get('password', $data));
		$this->setAccepted($this->getInt('accepted', $data, IShare::STATUS_PENDING));
		$this->setLink($this->get('link', $data));

		return $this;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IQueryRow
	 * @throws ShareTokenNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if ($this->get($prefix . 'token', $data) === '') {
			throw new ShareTokenNotFoundException();
		}

		$this->setShareId($this->getInt($prefix . 'share_id', $data));
		$this->setCircleId($this->get($prefix . 'circle_id', $data));
		$this->setSingleId($this->get($prefix . 'single_id', $data));
		$this->setMemberId($this->get($prefix . 'member_id', $data));
		$this->setToken($this->get($prefix . 'token', $data));
		$this->setPassword($this->get($prefix . 'password', $data));
		$this->setAccepted($this->getInt($prefix . 'accepted', $data, IShare::STATUS_PENDING));

		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'shareId' => $this->getShareId(),
			'circleId' => $this->getCircleId(),
			'singleId' => $this->getSingleId(),
			'memberId' => $this->getMemberId(),
			'token' => $this->getToken(),
			'password' => $this->getPassword(),
			'accepted' => $this->getAccepted(),
			'link' => $this->getLink()
		];
	}
}
