<?php


declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events\Files;

use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Mount;

/**
 * Class CreatingFileShareEvent
 *
 * @package OCA\Circles\Events\Files
 */
class CreatingFileShareEvent extends CircleGenericEvent {
	/** @var Mount */
	private $mount;


	/**
	 * CreatingFileShareEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct($federatedEvent);
	}


	/**
	 * @param Mount $mount
	 */
	public function setMount(Mount $mount): void {
		$this->mount = $mount;
	}

	/**
	 * @return Mount
	 */
	public function getMount(): Mount {
		return $this->mount;
	}

	/**
	 * @return bool
	 */
	public function hasMount(): bool {
		return !is_null($this->mount);
	}
}
