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


use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;


/**
 * Class FederatedEvent
 *
 * @package OCA\Circles\Model\Federated
 */
class FederatedEvent implements JsonSerializable {


	const SEVERITY_LOW = 1;
	const SEVERITY_HIGH = 3;

	const BYPASS_LOCALCIRCLECHECK = 1;
	const BYPASS_LOCALMEMBERCHECK = 2;
	const BYPASS_INITIATORCHECK = 4;
	const BYPASS_INITIATORMEMBERSHIP = 8;

	use TArrayTools;


	/** @var string */
	private $class;

	/** @var string */
	private $source = '';

	/** @var Circle */
	private $circle;

	/** @var string */
	private $itemId = '';

	/** @var string */
	private $itemSource = '';

	/** @var Member */
	private $member;

	/** @var SimpleDataStore */
	private $data;

	/** @var int */
	private $severity = self::SEVERITY_LOW;

	/** @var SimpleDataStore */
	private $readingOutcome;

	/** @var SimpleDataStore */
	private $dataOutcome;

	/** @var SimpleDataStore */
	private $result;

	/** @var bool */
	private $async = false;

	/** @var bool */
	private $limitedToInstanceWithMember = false;

	/** @var bool */
	private $dataRequestOnly = false;

	/** @var string */
	private $incomingOrigin = '';


	/** @var string */
	private $wrapperToken = '';

	/** @var bool */
	private $verifiedViewer = false;

	/** @var bool */
	private $verifiedCircle = false;

	/** @var int */
	private $bypass = 0;


	/**
	 * FederatedEvent constructor.
	 *
	 * @param string $class
	 */
	function __construct(string $class = '') {
		$this->class = $class;
		$this->data = new SimpleDataStore();
		$this->result = new SimpleDataStore();
		$this->readingOutcome = new SimpleDataStore();
		$this->dataOutcome = new SimpleDataStore();
	}


	/**
	 * @return string
	 */
	public function getClass(): string {
		return $this->class;
	}

	/**
	 * @param mixed $class
	 *
	 * @return self
	 */
	public function setClass($class): self {
		$this->class = $class;

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
	 * @return self
	 */
	public function setSource(string $source): self {
		$this->source = $source;

		if ($this->hasMember() && $this->member->getInstance() === '') {
			$this->member->setInstance($source);
		}

//		if ($this->hasCircle()
//			&& $this->getCircle()
//					->hasViewer()
//			&& $this->getCircle()
//					->getViewer()
//					->getInstance() === '') {
//			$this->getCircle()
//				 ->getViewer()
//				 ->setInstance($source);
//		}

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
	 * @return self
	 */
	public function setAsync(bool $async): self {
		$this->async = $async;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isLimitedToInstanceWithMember(): bool {
		return $this->limitedToInstanceWithMember;
	}

	/**
	 * @param bool $limitedToInstanceWithMember
	 *
	 * @return self
	 */
	public function setLimitedToInstanceWithMember(bool $limitedToInstanceWithMember): self {
		$this->limitedToInstanceWithMember = $limitedToInstanceWithMember;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isDataRequestOnly(): bool {
		return $this->dataRequestOnly;
	}

	/**
	 * @param bool $dataRequestOnly
	 *
	 * @return self
	 */
	public function setDataRequestOnly(bool $dataRequestOnly): self {
		$this->dataRequestOnly = $dataRequestOnly;

		return $this;
	}


	/**
	 * @param string $incomingOrigin
	 *
	 * @return self
	 */
	public function setIncomingOrigin(string $incomingOrigin): self {
		$this->incomingOrigin = $incomingOrigin;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getIncomingOrigin(): string {
		return $this->incomingOrigin;
	}


	/**
	 * @param bool $verifiedViewer
	 *
	 * @return FederatedEvent
	 */
	public function setVerifiedViewer(bool $verifiedViewer): self {
		$this->verifiedViewer = $verifiedViewer;

		return $this;
	}

//	/**
//	 * @return bool
//	 */
//	public function isVerifiedViewer(): bool {
//		return $this->verifiedViewer;
//	}

//	/**
//	 * @throws InitiatorNotConfirmedException
//	 */
//	public function confirmVerifiedViewer(): void {
//		if ($this->isVerifiedViewer()) {
//			return;
//		}
//
//		throw new InitiatorNotConfirmedException();
//	}


//	/**
//	 * @param bool $verifiedCircle
//	 *
//	 * @return FederatedEvent
//	 */
//	public function setVerifiedCircle(bool $verifiedCircle): self {
//		$this->verifiedCircle = $verifiedCircle;
//
//		return $this;
//	}
//
//	/**
//	 * @return bool
//	 */
//	public function isVerifiedCircle(): bool {
//		return $this->verifiedCircle;
//	}


	/**
	 * @param string $wrapperToken
	 *
	 * @return FederatedEvent
	 */
	public function setWrapperToken(string $wrapperToken): self {
		$this->wrapperToken = $wrapperToken;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getWrapperToken(): string {
		return $this->wrapperToken;
	}


	/**
	 * @return bool
	 */
	public function hasCircle(): bool {
		return ($this->circle !== null);
	}

	/**
	 * @param Circle $circle
	 *
	 * @return self
	 */
	public function setCircle(Circle $circle): self {
		$this->circle = $circle;

		return $this;
	}

	/**
	 * @return Circle
	 */
	public function getCircle(): Circle {
		return $this->circle;
	}


	/**
	 * @param string $itemId
	 *
	 * @return self
	 */
	public function setItemId(string $itemId): self {
		$this->itemId = $itemId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getItemId(): string {
		return $this->itemId;
	}


	/**
	 * @param string $itemSource
	 *
	 * @return self
	 */
	public function setItemSource(string $itemSource): self {
		$this->itemSource = $itemSource;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getItemSource(): string {
		return $this->itemSource;
	}


	/**
	 * @return Member
	 */
	public function getMember(): Member {
		return $this->member;
	}

	/**
	 * @param Member|null $member
	 *
	 * @return self
	 */
	public function setMember(?Member $member): self {
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
	 * @return self
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
	 * @return self
	 */
	public function setSeverity(int $severity): self {
		$this->severity = $severity;

		return $this;
	}


	/**
	 * @return SimpleDataStore
	 */
	public function getOutcome(): SimpleDataStore {
		return new SimpleDataStore(
			[
				'reading' => $this->getReadingOutcome(),
				'data'    => $this->getDataOutcome()
			]
		);
	}

	/**
	 * @return SimpleDataStore
	 */
	public function getReadingOutcome(): SimpleDataStore {
		return $this->readingOutcome;
	}

	/**
	 * @param string $message
	 * @param array $params
	 * @param bool $fail
	 *
	 * @return $this
	 */
	public function setReadingOutcome(string $message, array $params = [], bool $fail = false): self {
		$this->readingOutcome = new SimpleDataStore(
			[
				'message' => $message,
				'params'  => $params,
				'fail'    => $fail
			]
		);

		return $this;
	}


	/**
	 * @return SimpleDataStore
	 */
	public function getDataOutcome(): SimpleDataStore {
		return $this->dataOutcome;
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function setDataOutcome(array $data): self {
		$this->dataOutcome = new SimpleDataStore($data);

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
	 * @return self
	 */
	public function setResult(SimpleDataStore $result): self {
		$this->result = $result;

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return self
	 * @throws InvalidItemException
	 */
	public function import(array $data): self {
		$this->setClass($this->get('class', $data));
		$this->setSeverity($this->getInt('severity', $data));
		$this->setData(new SimpleDataStore($this->getArray('data', $data)));
		$this->setResult(new SimpleDataStore($this->getArray('result', $data)));
		$this->setSource($this->get('source', $data));
		$this->setItemId($this->get('itemId', $data));

		$circle = new Circle();
		$circle->import($this->getArray('circle', $data));
		$this->setCircle($circle);

		if (array_key_exists('member', $data)) {
			$member = new Member();
			$member->import($this->getArray('member', $data));
			$this->setMember($member);
		}

		return $this;
	}


	/**
	 * @return array
	 */
	function jsonSerialize(): array {
		$arr = [
			'class'    => $this->getClass(),
			'severity' => $this->getSeverity(),
			'data'     => $this->getData(),
			'result'   => $this->getResult(),
			'source'   => $this->getSource(),
			'itemId'   => $this->getItemId(),

			'outcome' => [
				'message' => $this->getReadingOutcome(),
				'data'    => $this->getDataOutcome()
			]
		];

		if ($this->hasCircle()) {
			$arr['circle'] = $this->getCircle();
		}
		if ($this->hasMember()) {
			$arr['member'] = $this->getMember();
		}

		return $arr;
	}


	/**
	 * @param int $flag
	 *
	 * @return FederatedEvent
	 */
	public function bypass(int $flag): self {
		if (!$this->canBypass($flag)) {
			$this->bypass += $flag;
		}

		return $this;
	}

	/**
	 * @param int $flag
	 *
	 * @return bool
	 */
	public function canBypass(int $flag): bool {
		return (($this->bypass & $flag) !== 0);
	}

}

