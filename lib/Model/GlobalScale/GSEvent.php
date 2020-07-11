<?php declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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


namespace OCA\Circles\Model\GlobalScale;


use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;


/**
 * Class GSEvent
 *
 * @package OCA\Circles\Model\GlobalScale
 */
class GSEvent implements JsonSerializable {


	const SEVERITY_LOW = 1;
	const SEVERITY_HIGH = 3;

	const GLOBAL_SYNC = '\OCA\Circles\GlobalScale\GlobalSync';
	const CIRCLE_STATUS = '\OCA\Circles\GlobalScale\CircleStatus';

	const CIRCLE_CREATE = '\OCA\Circles\GlobalScale\CircleCreate';
	const CIRCLE_UPDATE = '\OCA\Circles\GlobalScale\CircleUpdate';
	const CIRCLE_DESTROY = '\OCA\Circles\GlobalScale\CircleDestroy';
	const MEMBER_ADD = '\OCA\Circles\GlobalScale\MemberAdd';
	const MEMBER_JOIN = '\OCA\Circles\GlobalScale\MemberJoin';
	const MEMBER_LEAVE = '\OCA\Circles\GlobalScale\MemberLeave';
	const MEMBER_LEVEL = '\OCA\Circles\GlobalScale\MemberLevel';
	const MEMBER_UPDATE = '\OCA\Circles\GlobalScale\MemberUpdate';
	const MEMBER_REMOVE = '\OCA\Circles\GlobalScale\MemberRemove';
	const USER_DELETED = '\OCA\Circles\GlobalScale\UserDeleted';

	const FILE_SHARE = '\OCA\Circles\GlobalScale\FileShare';
	const FILE_UNSHARE = '\OCA\Circles\GlobalScale\FileUnshare';


	use TArrayTools;


	/** @var string */
	private $type = '';

	/** @var string */
	private $source = '';

	/** @var Circle */
	private $circle;

	/** @var Member */
	private $member;

	/** @var SimpleDataStore */
	private $data;

	/** @var int */
	private $severity = self::SEVERITY_LOW;

	/** @var SimpleDataStore */
	private $result;

	/** @var string */
	private $key = '';

	/** @var bool */
	private $local = false;

	/** @var bool */
	private $force = false;

	/** @var bool */
	private $async = false;


	/**
	 * GSEvent constructor.
	 *
	 * @param string $type
	 * @param bool $local
	 * @param bool $force
	 */
	function __construct(string $type = '', bool $local = false, bool $force = false) {
		$this->type = $type;
		$this->local = $local;
		$this->force = $force;
		$this->data = new SimpleDataStore();
		$this->result = new SimpleDataStore();
	}


	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @param mixed $type
	 *
	 * @return GSEvent
	 */
	public function setType($type): self {
		$this->type = $type;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSource(): string {
		return $this->source;
	}

	/**
	 * @param string $source
	 *
	 * @return GSEvent
	 */
	public function setSource(string $source): self {
		$this->source = $source;

		if ($this->hasMember() && $this->member->getInstance() === '') {
			$this->member->setInstance($source);
		}

		if ($this->hasCircle()
			&& $this->getCircle()
					->hasViewer()
			&& $this->getCircle()
					->getViewer()
					->getInstance() === '') {
			$this->getCircle()
				 ->getViewer()
				 ->setInstance($source);
		}

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isLocal(): bool {
		return $this->local;
	}

	/**
	 * @param bool $local
	 *
	 * @return GSEvent
	 */
	public function setLocal(bool $local): self {
		$this->local = $local;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isForced(): bool {
		return $this->force;
	}

	/**
	 * @param bool $force
	 *
	 * @return GSEvent
	 */
	public function setForced(bool $force): self {
		$this->force = $force;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isAsync(): bool {
		return $this->async;
	}

	/**
	 * @param bool $async
	 *
	 * @return GSEvent
	 */
	public function setAsync(bool $async): self {
		$this->async = $async;

		return $this;
	}


	/**
	 * @return Circle
	 */
	public function getCircle(): Circle {
		return $this->circle;
	}

	/**
	 * @param Circle $circle
	 *
	 * @return GSEvent
	 */
	public function setCircle(Circle $circle): self {
		$this->circle = $circle;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasCircle(): bool {
		return ($this->circle !== null);
	}


	/**
	 * @return Member
	 */
	public function getMember(): Member {
		return $this->member;
	}

	/**
	 * @param Member $member
	 *
	 * @return GSEvent
	 */
	public function setMember(Member $member): self {
		$this->member = $member;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasMember(): bool {
		return ($this->member !== null);
	}


	/**
	 * @param SimpleDataStore $data
	 *
	 * @return GSEvent
	 */
	public function setData(SimpleDataStore $data): self {
		$this->data = $data;

		return $this;
	}

	/**
	 * @return SimpleDataStore
	 */
	public function getData(): SimpleDataStore {
		return $this->data;
	}


	/**
	 * @return int
	 */
	public function getSeverity(): int {
		return $this->severity;
	}

	/**
	 * @param int $severity
	 *
	 * @return GSEvent
	 */
	public function setSeverity(int $severity): self {
		$this->severity = $severity;

		return $this;
	}


	/**
	 * @return SimpleDataStore
	 */
	public function getResult(): SimpleDataStore {
		return $this->result;
	}

	/**
	 * @param SimpleDataStore $result
	 *
	 * @return GSEvent
	 */
	public function setResult(SimpleDataStore $result): self {
		$this->result = $result;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @param string $key
	 *
	 * @return GSEvent
	 */
	public function setKey(string $key): self {
		$this->key = $key;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isValid(): bool {
		if ($this->getType() === '') {
			return false;
		}

		return true;
	}


	/**
	 * @param string $json
	 *
	 * @return GSEvent
	 * @throws JsonException
	 * @throws ModelException
	 */
	public function importFromJson(string $json): self {
		$data = json_decode($json, true);
		if (!is_array($data)) {
			throw new JsonException('invalid JSON');
		}

		return $this->import($data);
	}


	/**
	 * @param array $data
	 *
	 * @return GSEvent
	 * @throws ModelException
	 */
	public function import(array $data): self {
		$this->setType($this->get('type', $data));
		$this->setSeverity($this->getInt('severity', $data));
		$this->setData(new SimpleDataStore($this->getArray('data', $data)));
		$this->setResult(new SimpleDataStore($this->getArray('result', $data)));
		$this->setSource($this->get('source', $data));
		$this->setKey($this->get('key', $data));
		$this->setForced($this->getBool('force', $data));
		$this->setAsync($this->getBool('async', $data));

		if (array_key_exists('circle', $data)) {
			$this->setCircle(Circle::fromArray($data['circle']));
		}

		if (array_key_exists('member', $data)) {
			$this->setMember(Member::fromArray($data['member']));
		}

		if (!$this->isValid()) {
			throw new ModelException('invalid GSEvent');
		}

		return $this;
	}


	/**
	 * @return array
	 */
	function jsonSerialize(): array {
		$arr = [
			'type'     => $this->getType(),
			'severity' => $this->getSeverity(),
			'data'     => $this->getData(),
			'result'   => $this->getResult(),
			'key'      => $this->getKey(),
			'source'   => $this->getSource(),
			'force'    => $this->isForced(),
			'async'    => $this->isAsync()
		];

		if ($this->hasCircle()) {
			$arr['circle'] = $this->getCircle();
		}
		if ($this->hasMember()) {
			$arr['member'] = $this->getMember();
		}

		$this->cleanArray($arr);

		return $arr;
	}


}

