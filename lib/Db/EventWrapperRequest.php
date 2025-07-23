<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Circles\Model\Federated\EventWrapper;
use Psr\Log\LoggerInterface;

/**
 * Class EventWrapperRequest
 *
 * @package OCA\Circles\Db
 */
class EventWrapperRequest extends EventWrapperRequestBuilder {
	/**
	 * @param EventWrapper $wrapper
	 */
	public function save(EventWrapper $wrapper): void {
		$qb = $this->getEventWrapperInsertSql();
		$qb->setValue('token', $qb->createNamedParameter($wrapper->getToken()))
			->setValue(
				'event', $qb->createNamedParameter(json_encode($wrapper->getEvent(), JSON_UNESCAPED_SLASHES))
			)
			->setValue(
				'result', $qb->createNamedParameter(json_encode($wrapper->getResult(), JSON_UNESCAPED_SLASHES))
			)
			->setValue('instance', $qb->createNamedParameter($wrapper->getInstance()))
			->setValue('interface', $qb->createNamedParameter($wrapper->getInterface()))
			->setValue('severity', $qb->createNamedParameter($wrapper->getSeverity()))
			->setValue('retry', $qb->createNamedParameter($wrapper->getRetry()))
			->setValue('status', $qb->createNamedParameter($wrapper->getStatus()))
			->setValue('creation', $qb->createNamedParameter($wrapper->getCreation()));

		try {
			$qb->execute();
		} catch (UniqueConstraintViolationException $e) {
			$logger = \OCP\Server::get(LoggerInterface::class);
			$logger->warning('issue while storing event', ['exception' => $e]);
		}
	}

	/**
	 * @param EventWrapper $wrapper
	 */
	public function update(EventWrapper $wrapper): void {
		$qb = $this->getEventWrapperUpdateSql();
		$qb->set('result', $qb->createNamedParameter(json_encode($wrapper->getResult())))
			->set('status', $qb->createNamedParameter($wrapper->getStatus()))
			->set('retry', $qb->createNamedParameter($wrapper->getRetry()));

		$qb->limitToInstance($wrapper->getInstance());
		$qb->limitToToken($wrapper->getToken());

		$qb->execute();
	}


	/**
	 * @param string $token
	 * @param int $status
	 */
	public function updateAll(string $token, int $status): void {
		$qb = $this->getEventWrapperUpdateSql();
		$qb->set('status', $qb->createNamedParameter($status));

		$qb->limitToToken($token);

		$qb->execute();
	}


	/**
	 * returns unique token not set as FAILED
	 *
	 * @return EventWrapper[]
	 */
	public function getFailedEvents(array $retryRange): array {
		$qb = $this->getEventWrapperSelectSql();

		$expr = $qb->expr();
		$qb->andWhere(
			$expr->orX(
				$qb->exprLimitInt('status', EventWrapper::STATUS_FAILED),
				$expr->andX(
					$qb->exprLimitInt('status', EventWrapper::STATUS_INIT),
					$qb->exprGt('creation', time() - 86400),  // only freshly created; less than 3 hours
					$qb->exprLt('creation', time() - 900)     // but not too fresh, at least 15 minutes
				)
			)
		);

		$qb->gt('retry', $retryRange[0], true);
		$qb->lt('retry', $retryRange[1]);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $token
	 *
	 * @return EventWrapper[]
	 */
	public function getByToken(string $token): array {
		$qb = $this->getEventWrapperSelectSql();
		$qb->limitToToken($token);

		return $this->getItemsFromRequest($qb);
	}

	/**
	 * delete completed entries older than a month
	 *
	 * @param bool $allOldEntries delete also not-completed entries
	 */
	public function deleteOldEntries(bool $allOldEntries = false): void {
		$qb = $this->getEventWrapperDeleteSql();
		$qb->where(
			$qb->exprLimitInt('status', EventWrapper::STATUS_OVER),
			$qb->exprLt('creation', time() - 30 * 86400)
		);
		if (!$allOldEntries) {
			$qb->andWhere($qb->exprLimitInt('status', EventWrapper::STATUS_OVER));
		}

		$qb->executeStatement();
	}
}
