<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Exceptions;

use OCP\AppFramework\Http;
use Throwable;

/**
 * Class FederatedItemNotFoundException
 *
 * @package OCA\Circles\Exceptions
 */
class FederatedItemNotFoundException extends FederatedItemException {
	public const STATUS = Http::STATUS_NOT_FOUND;


	/**
	 * FederatedItemNotFoundException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(
		string $message = '',
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct($message, ($code > 0) ? $code : self::STATUS, $previous);
		$this->setStatus(self::STATUS);
	}
}
