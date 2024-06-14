<?php


declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Exceptions;

use Exception;
use JsonSerializable;
use OCP\AppFramework\Http;
use Throwable;

/**
 * Class FederatedItemException
 *
 * @package OCA\Circles\Exceptions
 */
class FederatedItemException extends Exception implements JsonSerializable {
	public static $CHILDREN = [
		FederatedItemBadRequestException::class,
		FederatedItemConflictException::class,
		FederatedItemForbiddenException::class,
		FederatedItemNotFoundException::class,
		FederatedItemRemoteException::class,
		FederatedItemServerException::class,
		FederatedItemUnauthorizedException::class
	];


	/** @var int */
	private $status = Http::STATUS_BAD_REQUEST;


	/**
	 * FederatedItemException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null) {
		parent::__construct($message, ($code > 0) ? $code : $this->status, $previous);
	}


	/**
	 * @param int $status
	 */
	protected function setStatus(int $status): void {
		$this->status = $status;
	}

	/**
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'class' => get_class($this),
			'status' => $this->getStatus(),
			'code' => $this->getCode(),
			'message' => $this->getMessage()
		];
	}
}
