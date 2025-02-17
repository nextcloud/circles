<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class RemoteRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class RemoteRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getRemoteInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_REMOTE)
			->setValue('creation', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Groups
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getRemoteUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_REMOTE);

		return $qb;
	}


	/**
	 * @param string $alias
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getRemoteSelectSql(string $alias = CoreQueryBuilder::REMOTE): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_REMOTE, self::$tables[self::TABLE_REMOTE], $alias);

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getRemoteDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_REMOTE);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return RemoteInstance
	 * @throws RemoteNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): RemoteInstance {
		/** @var RemoteInstance $appService */
		try {
			$appService = $qb->asItem(RemoteInstance::class);
		} catch (RowNotFoundException $e) {
			throw new RemoteNotFoundException('Unknown remote instance');
		}

		return $appService;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return RemoteInstance[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var RemoteInstance[] $result */
		return $qb->asItems(RemoteInstance::class);
	}
}
