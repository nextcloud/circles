<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Model\Probes;

use OCA\Circles\IQueryProbe;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Member;

/**
 * Class BasicProbe
 *
 * @package OCA\Circles\Model\Probes
 */
class BasicProbe implements IQueryProbe {
	public const DETAILS_NONE = 0;
	public const DETAILS_POPULATION = 32;
	public const DETAILS_ALL = 127;


	/** @var int */
	private $itemsOffset = 0;

	/** @var int */
	private $itemsLimit = -1;

	/** @var int */
	private $details = 0;

	/** @var Circle */
	private $filterCircle;

	/** @var Member */
	private $filterMember;

	/** @var RemoteInstance */
	private $filterRemoteInstance;

	/** @var array */
	private $options = [];


	/**
	 * @param int $itemsOffset
	 *
	 * @return BasicProbe
	 */
	public function setItemsOffset(int $itemsOffset): self {
		$this->itemsOffset = $itemsOffset;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getItemsOffset(): int {
		return $this->itemsOffset;
	}


	/**
	 * @param int $itemsLimit
	 *
	 * @return BasicProbe
	 */
	public function setItemsLimit(int $itemsLimit): self {
		$this->itemsLimit = $itemsLimit;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getItemsLimit(): int {
		return $this->itemsLimit;
	}


	/**
	 * @param int $details
	 *
	 * @return $this
	 */
	public function setDetails(int $details): self {
		$this->details = $details;
	}

	/**
	 * @return int
	 */
	public function getDetails(): int {
		return $this->details;
	}

	/**
	 * @param int $detail
	 *
	 * @return $this
	 */
	public function addDetail(int $detail): self {
		$this->details |= $detail;

		return $this;
	}

	/**
	 * @param int $detail
	 *
	 * @return bool
	 */
	public function showDetail(int $detail): bool {
		return (($this->getDetails() & $detail) !== 0);
	}


	/**
	 * @param Circle $filterCircle
	 *
	 * @return CircleProbe
	 */
	public function setFilterCircle(Circle $filterCircle): self {
		$this->filterCircle = $filterCircle;

		return $this;
	}

	/**
	 * @return Circle
	 */
	public function getFilterCircle(): Circle {
		return $this->filterCircle;
	}

	/**
	 * @return bool
	 */
	public function hasFilterCircle(): bool {
		return !is_null($this->filterCircle);
	}


	/**
	 * @param Member $filterMember
	 *
	 * @return CircleProbe
	 */
	public function setFilterMember(Member $filterMember): self {
		$this->filterMember = $filterMember;

		return $this;
	}

	/**
	 * @return Member
	 */
	public function getFilterMember(): Member {
		return $this->filterMember;
	}

	/**
	 * @return bool
	 */
	public function hasFilterMember(): bool {
		return !is_null($this->filterMember);
	}


	/**
	 * @param RemoteInstance $filterRemoteInstance
	 *
	 * @return CircleProbe
	 */
	public function setFilterRemoteInstance(RemoteInstance $filterRemoteInstance): self {
		$this->filterRemoteInstance = $filterRemoteInstance;

		return $this;
	}

	/**
	 * @return RemoteInstance
	 */
	public function getFilterRemoteInstance(): RemoteInstance {
		return $this->filterRemoteInstance;
	}

	/**
	 * @return bool
	 */
	public function hasFilterRemoteInstance(): bool {
		return !is_null($this->filterRemoteInstance);
	}


	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return $this
	 */
	public function addOption(string $key, string $value): self {
		$this->options[$key] = $value;

		return $this;
	}

	/**
	 * @param string $key
	 * @param int $value
	 *
	 * @return $this
	 */
	public function addOptionInt(string $key, int $value): self {
		$this->options[$key] = $value;

		return $this;
	}

	/**
	 * @param string $key
	 * @param bool $value
	 *
	 * @return $this
	 */
	public function addOptionBool(string $key, bool $value): self {
		$this->options[$key] = $value;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAsOptions(): array {
		return array_merge(
			$this->options,
			[
				'offset' => $this->getItemsOffset(),
				'limit' => $this->getItemsLimit(),
				'details' => $this->getDetails(),
				'detailsAll' => ($this->getDetails() === self::DETAILS_ALL)
			]
		);
	}


	/**
	 * @return array
	 */
	public function JsonSerialize(): array {
		return $this->getAsOptions();
	}
}
