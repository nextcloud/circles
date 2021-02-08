<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


use daita\MySmallPhpTools\Db\Nextcloud\nc21\INC21QueryRow;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Signatory;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\RemoteUidException;


/**
 * Class AppService
 *
 * @package OCA\Circles\Model
 */
class RemoteInstance extends NC21Signatory implements INC21QueryRow, JsonSerializable {


	use TArrayTools;

	const TYPE_UNKNOWN = 'Unknown';    // not trusted
	const TYPE_EXTERNAL = 'External';  // info about Federated Circles are not broadcasted by default.
	const TYPE_TRUSTED = 'Trusted';    // evething about Federated Circles are broadcasted.
	const TYPE_GLOBAL_SCALE = 'GlobalScale';  // every Circle is broadcasted,

	const TEST = 'test';
	const INCOMING = 'incoming';
	const EVENT = 'event';
	const CIRCLES = 'circles';
	const CIRCLE = 'circle';


	/** @var int */
	private $dbId = 0;

	/** @var string */
	private $type = self::TYPE_UNKNOWN;

	/** @var string */
	private $test = '';

	/** @var string */
	private $incoming = '';

	/** @var string */
	private $event = '';

	/** @var string */
	private $circles = '';

	/** @var string */
	private $circle = '';

	/** @var string */
	private $members = '';

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
	 * @return NC21Signatory
	 */
	public function import(array $data): NC21Signatory {
		parent::import($data);

		$this->setTest($this->get('test', $data));
		$this->setEvent($this->get('event', $data));
		$this->setIncoming($this->get('incoming', $data));
		$this->setCircles($this->get('circles', $data));
		$this->setCircle($this->get('circle', $data));
		$this->setMembers($this->get('members', $data));
		$this->setAuthSigned($this->get('auth-signed', $data));
		$this->setUid($this->get('uid', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$data = [
			'uid'      => $this->getUid(true),
			'event'    => $this->getEvent(),
			'incoming' => $this->getIncoming(),
			'test'     => $this->getTest(),
			'circles'  => $this->getCircles(),
			'circle'   => $this->getCircle(),
			'members'  => $this->getMembers(),
		];

		if ($this->getAuthSigned() !== '') {
			$data['auth-signed'] = $this->getAlgorithm() . ':' . $this->getAuthSigned();
		}

		return array_filter(array_merge($data, parent::jsonSerialize()));
	}


	/**
	 * @param array $data
	 *
	 * @return self
	 */
	public function importFromDatabase(array $data): INC21QueryRow {
		$this->setDbId($this->getInt('id', $data));
		$this->import($this->getArray('item', $data));
		$this->setOrigData($this->getArray('item', $data));
		$this->setType($this->get('type', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setId($this->get('href', $data));

		return $this;
	}

}

