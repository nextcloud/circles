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

use ArtificialOwl\MySmallPhpTools\Db\Nextcloud\nc22\INC22QueryRow;
use ArtificialOwl\MySmallPhpTools\Exceptions\InvalidItemException;
use ArtificialOwl\MySmallPhpTools\IDeserializable;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Deserialize;
use ArtificialOwl\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\IMemberships;

/**
 * Class FederatedUser
 *
 * @package OCA\Circles\Model
 */
class FederatedUser extends ManagedModel implements
	IFederatedUser,
	IMemberships,
	IDeserializable,
	INC22QueryRow,
	JsonSerializable {
	use TArrayTools;
	use TNC22Deserialize;


	/** @var string */
	private $singleId = '';

	/** @var string */
	private $userId;

	/** @var int */
	private $userType;

	/** @var string */
	private $displayName = '';

	/** @var Circle */
	private $basedOn;

	/** @var int */
	private $config = 0;

	/** @var string */
	private $instance;

	/** @var Membership */
	private $link;

	/** @var Membership[] */
	private $memberships = null;


	/**
	 * FederatedUser constructor.
	 */
	public function __construct() {
	}


	/**
	 * @param string $userId
	 * @param string $instance
	 * @param int $type
	 * @param string $displayName
	 * @param Circle|null $basedOn
	 *
	 * @return $this
	 */
	public function set(
		string $userId,
		string $instance = '',
		int $type = Member::TYPE_USER,
		string $displayName = '',
		?Circle $basedOn = null
	): self {
		$this->userId = $userId;
		$this->setInstance($instance);
		$this->userType = $type;
		$this->displayName = ($displayName === '') ? $userId : $displayName;
		$this->basedOn = $basedOn;

		return $this;
	}


	/**
	 * @param string $singleId
	 *
	 * @return self
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
	 * @param string $displayName
	 *
	 * @return FederatedUser
	 */
	public function setDisplayName(string $displayName): self {
		$this->displayName = $displayName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->displayName;
	}


	/**
	 * @return bool
	 */
	public function hasBasedOn(): bool {
		return !is_null($this->basedOn);
	}

	/**
	 * @param Circle|null $basedOn
	 *
	 * @return $this
	 */
	public function setBasedOn(?Circle $basedOn): self {
		$this->basedOn = $basedOn;

		return $this;
	}

	/**
	 * @return Circle
	 */
	public function getBasedOn(): Circle {
		return $this->basedOn;
	}


	/**
	 * @param int $config
	 *
	 * @return self
	 */
	public function setConfig(int $config): self {
		$this->config = $config;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getConfig(): int {
		return $this->config;
	}


	/**
	 * @param string $instance
	 *
	 * @return self
	 */
	public function setInstance(string $instance): self {
		if ($instance === '') {
			// TODO: is it needed ?
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
	 * @return bool
	 */
	public function isLocal(): bool {
		return $this->getManager()->isLocalInstance($this->getInstance());
	}


	/**
	 * @return bool
	 */
	public function hasMemberships(): bool {
		return !is_null($this->memberships);
	}

	/**
	 * @param array $memberships
	 *
	 * @return self
	 */
	public function setMemberships(array $memberships): IMemberships {
		$this->memberships = $memberships;

		return $this;
	}

	/**
	 * @return Membership[]
	 */
	public function getMemberships(): array {
		if (!$this->hasMemberships()) {
			$this->getManager()->getMemberships($this);
		}

		return $this->memberships;
	}


	/**
	 * @return bool
	 */
	public function hasLink(): bool {
		return !is_null($this->link);
	}

	/**
	 * @param Membership $link
	 *
	 * @return $this
	 */
	public function setLink(Membership $link): self {
		$this->link = $link;

		return $this;
	}

	/**
	 * @return Membership
	 */
	public function getLink(): Membership {
		return $this->link;
	}


	/**
	 * @param array $data
	 *
	 * @return $this
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		if ($this->get('userId', $data) === '') {
			throw new InvalidItemException();
		}

		$this->setSingleId($this->get('id', $data));
		$this->setUserId($this->get('userId', $data));
		$this->setUserType($this->getInt('userType', $data));
		$this->setDisplayName($this->get('displayName', $data));
		$this->setInstance($this->get('instance', $data));
		//$this->setMemberships($this->getArray('memberships'));

		try {
			/** @var Circle $circle */
			$circle = $this->deserialize($this->getArray('basedOn', $data), Circle::class);
			$this->setBasedOn($circle);
		} catch (InvalidItemException $e) {
		}

		return $this;
	}


	/**
	 * @param Circle $circle
	 *
	 * @return FederatedUser
	 * @throws OwnerNotFoundException
	 */
	public function importFromCircle(Circle $circle): self {
		$this->setSingleId($circle->getSingleId());

		if ($circle->isConfig(Circle::CFG_SINGLE)) {
			$owner = $circle->getOwner();
			$this->set(
				$owner->getUserId(),
				$owner->getInstance(),
				$owner->getUserType(),
				$owner->getDisplayName(),
				$circle
			);
		} else {
			$this->set(
				$circle->getDisplayName(),
				$circle->getInstance(),
				Member::TYPE_CIRCLE,
				$circle->getDisplayName(),
				$circle
			);
		}

		return $this;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return INC22QueryRow
	 * @throws FederatedUserNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): INC22QueryRow {
		if ($this->get($prefix . 'single_id', $data) === '') {
			throw new FederatedUserNotFoundException();
		}

		$this->setSingleId($this->get($prefix . 'single_id', $data));
		$this->setUserId($this->get($prefix . 'user_id', $data));
		$this->setUserType($this->getInt($prefix . 'user_type', $data));
		$this->setDisplayName($this->get($prefix . 'cached_name', $data));
		$this->setInstance($this->get($prefix . 'instance', $data));

		$this->getManager()->manageImportFromDatabase($this, $data, $prefix);

		return $this;
	}


	/**
	 * @return string[]
	 * @throws UnknownInterfaceException
	 */
	public function jsonSerialize(): array {
		$arr = [
			'id' => $this->getSingleId(),
			'userId' => $this->getUserId(),
			'userType' => $this->getUserType(),
			'displayName' => $this->getDisplayName(),
			'instance' => $this->getManager()->fixInstance($this->getInstance())
		];

		if ($this->hasBasedOn()) {
			$arr['basedOn'] = $this->getBasedOn();
		}

		if ($this->hasLink()) {
			$arr['link'] = $this->getLink();
		}

		if (!is_null($this->memberships)) {
			$arr['memberships'] = $this->getMemberships();
		}

		return $arr;
	}


	/**
	 * @param IFederatedUser $member
	 *
	 * @return bool
	 */
	public function compareWith(IFederatedUser $member): bool {
		$local = ($this->getManager()->isLocalInstance($this->getInstance())
				  && $this->getManager()->isLocalInstance($member->getInstance()));

		return !($this->getSingleId() !== $member->getSingleId()
				 || $this->getUserId() !== $member->getUserId()
				 || $this->getUserType() <> $member->getUserType()
				 || (!$local && $this->getInstance() !== $member->getInstance()));
	}
}
