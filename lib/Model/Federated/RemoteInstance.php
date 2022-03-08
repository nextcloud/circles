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


namespace OCA\Circles\Model\Federated;

use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Model\NCSignatory;
use OCA\Circles\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteUidException;

/**
 * Class AppService
 *
 * @package OCA\Circles\Model
 */
class RemoteInstance extends NCSignatory implements IQueryRow, JsonSerializable {
	use TArrayTools;

	public const TYPE_UNKNOWN = 'Unknown';    // not trusted
	public const TYPE_PASSIVE = 'Passive';    // Minimum information about Federated Circles are broadcasted if a member belongs to the circle.
	public const TYPE_EXTERNAL = 'External';  // info about Federated Circles and their members are broadcasted  if a member belongs to the circle.
	public const TYPE_TRUSTED = 'Trusted';    // everything about Federated Circles are broadcasted.
	public const TYPE_GLOBALSCALE = 'GlobalScale';  // every Circle is broadcasted,

	public static $LIST_TYPE = [
		self::TYPE_UNKNOWN,
		self::TYPE_PASSIVE,
		self::TYPE_EXTERNAL,
		self::TYPE_TRUSTED,
		self::TYPE_GLOBALSCALE
	];

	public const ROOT = 'root';
	public const TEST = 'test';
	public const ALIASES = 'aliases';
	public const INCOMING = 'incoming';
	public const EVENT = 'event';
	public const CIRCLES = 'circles';
	public const CIRCLE = 'circle';
	public const MEMBERS = 'members';
	public const MEMBER = 'member';
	public const MEMBERSHIPS = 'memberships';
	public const INHERITED = 'inherited';
	public const UID = 'uid';
	public const AUTH_SIGNED = 'auth-signed';

	/** @var int */
	private $dbId = 0;

	/** @var string */
	private $type = self::TYPE_UNKNOWN;

	/** @var int */
	private $interface = 0;

	/** @var string */
	private $test = '';

	/** @var array */
	private $aliases = [];

	/** @var string */
	private $incoming = '';

	/** @var string */
	private $root = '';

	/** @var string */
	private $event = '';

	/** @var string */
	private $circles = '';

	/** @var string */
	private $circle = '';

	/** @var string */
	private $members = '';

	/** @var string */
	private $member = '';

	/** @var string */
	private $inherited = '';

	/** @var string */
	private $memberships = '';

	/** @var string */
	private $uid = '';

	/** @var string */
	private $authSigned = '';

	/** @var bool */
	private $identityAuthed = false;


	/**
	 * @param int $dbId
	 *
	 * @return self
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
	 * @param string $type
	 *
	 * @return $this
	 */
	public function setType(string $type): self {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}


	/**
	 * @param int $interface
	 *
	 * @return RemoteInstance
	 */
	public function setInterface(int $interface): self {
		$this->interface = $interface;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getInterface(): int {
		return $this->interface;
	}


	/**
	 * @param array $aliases
	 *
	 * @return RemoteInstance
	 */
	public function setAliases(array $aliases): self {
		$this->aliases = $aliases;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAliases(): array {
		return $this->aliases;
	}


	/**
	 * @return string
	 */
	public function getIncoming(): string {
		return $this->incoming;
	}

	/**
	 * @param string $incoming
	 *
	 * @return self
	 */
	public function setIncoming(string $incoming): self {
		$this->incoming = $incoming;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getRoot(): string {
		return $this->root;
	}

	/**
	 * @param string $root
	 *
	 * @return $this
	 */
	public function setRoot(string $root): self {
		$this->root = $root;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getEvent(): string {
		return $this->event;
	}

	/**
	 * @param string $event
	 *
	 * @return self
	 */
	public function setEvent(string $event): self {
		$this->event = $event;

		return $this;
	}


	/**
	 * @param string $test
	 *
	 * @return RemoteInstance
	 */
	public function setTest(string $test): self {
		$this->test = $test;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTest(): string {
		return $this->test;
	}


	/**
	 * @return string
	 */
	public function getCircles(): string {
		return $this->circles;
	}

	/**
	 * @param string $circles
	 *
	 * @return self
	 */
	public function setCircles(string $circles): self {
		$this->circles = $circles;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getCircle(): string {
		return $this->circle;
	}

	/**
	 * @param string $circle
	 *
	 * @return self
	 */
	public function setCircle(string $circle): self {
		$this->circle = $circle;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getMembers(): string {
		return $this->members;
	}

	/**
	 * @param string $members
	 *
	 * @return self
	 */
	public function setMembers(string $members): self {
		$this->members = $members;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getInherited(): string {
		return $this->inherited;
	}

	/**
	 * @param string $inherited
	 *
	 * @return self
	 */
	public function setInherited(string $inherited): self {
		$this->inherited = $inherited;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getMemberships(): string {
		return $this->memberships;
	}

	/**
	 * @param string $memberships
	 *
	 * @return self
	 */
	public function setMemberships(string $memberships): self {
		$this->memberships = $memberships;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getMember(): string {
		return $this->member;
	}

	/**
	 * @param string $member
	 *
	 * @return self
	 */
	public function setMember(string $member): self {
		$this->member = $member;

		return $this;
	}


	/**
	 * @return $this
	 */
	public function setUidFromKey(): self {
		$this->setUid(hash('sha512', $this->getPublicKey()));

		return $this;
	}

	/**
	 * @param string $uid
	 *
	 * @return RemoteInstance
	 */
	public function setUid(string $uid): self {
		$this->uid = $uid;

		return $this;
	}

	/**
	 * @param bool $shorten
	 *
	 * @return string
	 */
	public function getUid(bool $shorten = false): string {
		if ($shorten) {
			return substr($this->uid, 0, 18);
		}

		return $this->uid;
	}


	/**
	 * @param string $authSigned
	 *
	 * @return RemoteInstance
	 */
	public function setAuthSigned(string $authSigned): self {
		$this->authSigned = $authSigned;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAuthSigned(): string {
		return $this->authSigned;
	}


	/**
	 * @param bool $identityAuthed
	 *
	 * @return RemoteInstance
	 */
	public function setIdentityAuthed(bool $identityAuthed): self {
		$this->identityAuthed = $identityAuthed;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isIdentityAuthed(): bool {
		return $this->identityAuthed;
	}

	/**
	 * @throws RemoteUidException
	 */
	public function mustBeIdentityAuthed(): void {
		if (!$this->isIdentityAuthed()) {
			throw new RemoteUidException('identity not authed');
		}
	}


	/**
	 * @param array $data
	 *
	 * @return NCSignatory
	 */
	public function import(array $data): NCSignatory {
		parent::import($data);

		$this->setTest($this->get(self::TEST, $data))
			 ->setAliases($this->getArray(self::ALIASES, $data))
			 ->setEvent($this->get(self::EVENT, $data))
			 ->setRoot($this->get(self::ROOT, $data))
			 ->setIncoming($this->get(self::INCOMING, $data))
			 ->setCircles($this->get(self::CIRCLES, $data))
			 ->setCircle($this->get(self::CIRCLE, $data))
			 ->setMembers($this->get(self::MEMBERS, $data))
			 ->setMember($this->get(self::MEMBER, $data))
			 ->setInherited($this->get(self::INHERITED, $data))
			 ->setMemberships($this->get(self::MEMBERSHIPS, $data))
			 ->setUid($this->get(self::UID, $data));

		$algo = '';
		$authSigned = trim($this->get(self::AUTH_SIGNED, $data), ':');
		if (strpos($authSigned, ':') > 0) {
			[$algo, $authSigned] = explode(':', $authSigned);
		}

		$this->setAuthSigned($authSigned)
			 ->setAlgorithm($algo);

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$data = [
			self::UID => $this->getUid(true),
			self::ROOT => $this->getRoot(),
			self::EVENT => $this->getEvent(),
			self::INCOMING => $this->getIncoming(),
			self::TEST => $this->getTest(),
			self::CIRCLES => $this->getCircles(),
			self::CIRCLE => $this->getCircle(),
			self::MEMBERS => $this->getMembers(),
			self::MEMBER => $this->getMember(),
			self::INHERITED => $this->getInherited(),
			self::MEMBERSHIPS => $this->getMemberships()
		];

		if ($this->getAuthSigned() !== '') {
			$data[self::AUTH_SIGNED] = $this->getAlgorithm() . ':' . $this->getAuthSigned();
		}

		if (!empty($this->getAliases())) {
			$data[self::ALIASES] = $this->getAliases();
		}

		return array_filter(array_merge($data, parent::jsonSerialize()));
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return self
	 * @throws RemoteNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if ($this->getInt($prefix . 'id', $data) === 0) {
			throw new RemoteNotFoundException();
		}

		$this->setDbId($this->getInt($prefix . 'id', $data));
		$this->import($this->getArray($prefix . 'item', $data));
		$this->setOrigData($this->getArray($prefix . 'item', $data));
		$this->setType($this->get($prefix . 'type', $data));
		$this->setInterface($this->getInt($prefix . 'interface', $data));
		$this->setInstance($this->get($prefix . 'instance', $data));
		$this->setId($this->get($prefix . 'href', $data));

		return $this;
	}
}
