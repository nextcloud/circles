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

use daita\MySmallPhpTools\Db\Nextcloud\nc21\INC21QueryRow;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\INC21Convert;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\IMember;


/**
 * Class Viewer
 *
 * @package OCA\Circles\Model
 */
class CurrentUser extends ManagedModel implements IMember, INC21Convert, INC21QueryRow, JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $id = '';

	/** @var string */
	private $userId;

	/** @var int */
	private $userType;

	/** @var string */
	private $instance;

	/** @var Membership[] */
	private $memberships = [];


	/**
	 * Viewer constructor.
	 *
	 * @param string $userId
	 * @param int $type
	 * @param string $instance
	 */
	public function __construct(string $userId = '', $instance = '', int $type = Member::TYPE_USER) {
		$this->userId = $userId;
		$this->setInstance($instance);
		$this->userType = $type;
	}


	/**
	 * @param string $id
	 *
	 * @return self
	 */
	public function setId(string $id): self {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}


	/**
	 * @param string $userId
	 *
	 * @return self
	 */
	public function setUserId(string $userId): self {
		$this->userId = $userId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->userId;
	}


	/**
	 * @param int $userType
	 *
	 * @return self
	 */
	public function setUserType(int $userType): self {
		$this->userType = $userType;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserType(): int {
		return $this->userType;
	}


	/**
	 * @param string $instance
	 *
	 * @return self
	 */
	public function setInstance(string $instance): self {
		if ($instance === '') {
			$instance = $this->getManager()->getLocalInstance();
		}

		$this->instance = $instance;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInstance(): string {
		return $this->instance;
	}


	/**
	 * @param Membership[] $memberships
	 *
	 * @return self
	 */
	public function setMemberships(array $memberships): self {
		$this->memberships = $memberships;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getMemberships(): array {
		if ($this->memberships === null) {
			$this->getManager()->getMemberships($this);
		}

		return $this->memberships;
	}


	/**
	 * @param IMember $member
	 *
	 * @return self
	 */
	public function importFromIMember(IMember $member): IMember {
		$this->getManager()->importFromIMember($this, $member);

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function import(array $data): INC21Convert {
		$this->setId($this->get('id', $data));
		$this->setUserId($this->get('user_id', $data));
		$this->setUserType($this->getInt('user_type', $data));
		$this->setInstance($this->get('instance', $data));

//$this->setMemberships($this->getArray('memberships'));
		return $this;
	}


	/**
	 * @return string[]
	 */
	public function jsonSerialize(): array {
		return [
			'id'          => $this->getId(),
			'user_id'     => $this->getUserId(),
			'user_type'   => $this->getUserType(),
			'instance'    => $this->getInstance(),
			'memberships' => $this->getMemberships()
		];
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return INC21QueryRow
	 */
	public function importFromDatabase(array $data, string $prefix = ''): INC21QueryRow {
		$this->setId($this->get($prefix . 'member_id', $data));
		$this->setUserId($this->get($prefix . 'user_id', $data));
		$this->setUserType($this->getInt($prefix . 'user_type', $data));
		$this->setInstance($this->get($prefix . 'instance', $data));

		return $this;
	}

}
