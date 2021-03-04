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


namespace OCA\Circles\Exceptions;


use Exception;
use JsonSerializable;
use OCA\Circles\Service\ExceptionService;
use OCP\AppFramework\Http;
use Throwable;


/**
 * Class FederatedItemException
 *
 * @package OCA\Circles\Exceptions
 */
class FederatedItemException extends Exception implements JsonSerializable {


	static $CHILDREN = [
		FederatedItemBadRequestException::class,
		FederatedItemConflictException::class,
		FederatedItemForbiddenException::class,
		FederatedItemNotFoundException::class,
		FederatedItemRemoteException::class,
		FederatedItemServerException::class
	];


	/** @var string */
	private $rawMessage;

	/** @var array */
	private $params;

	/** @var int */
	private $status = Http::STATUS_INTERNAL_SERVER_ERROR;


	/**
	 * FederatedItemException constructor.
	 *
	 * @param string $message
	 * @param array $params
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(
		string $message = '',
		array $params = [],
		int $code = 0,
		?Throwable $previous = null
	) {
		$this->setRawMessage($message);

		if ($message !== '') {
			try {
				/** @var ExceptionService $exceptionService */
				$exceptionService = \OC::$server->get(ExceptionService::class);
				$l10n = $exceptionService->getL10n();
				$message = $l10n->t($message, $params);
			} catch (Throwable $t) {
			}
		}

		parent::__construct($message, $code, $previous);
		$this->setParams($params);
	}

	/**
	 * @param array $params
	 */
	private function setParams(array $params): void {
		$this->params = $params;
	}

	/**
	 * @return array
	 */
	public function getParams(): array {
		return $this->params;
	}


	/**
	 * @param string $message
	 */
	private function setRawMessage(string $message): void {
		$this->rawMessage = $message;
	}

	/**
	 * @return string
	 */
	public function getRawMessage(): string {
		return $this->rawMessage;
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
	 * @return mixed|void
	 */
	public function jsonSerialize(): array {
		return [
			'class'   => get_class($this),
			'status'  => $this->getStatus(),
			'message' => $this->getRawMessage(),
			'params'  => $this->getParams()
		];
	}

}

