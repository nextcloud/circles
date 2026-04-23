<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use OCA\Circles\ConfigLexicon;
use OCP\AppFramework\Services\IAppConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Share\IShare;

/**
 * Class RemoveShareTokens
 *
 * @package OCA\Circles\Migration
 */
class RemoveShareTokens implements IRepairStep {

	public function __construct(
		private IDBConnection $dbConnection,
		private readonly IAppConfig $appConfig,
	) {
	}

	public function getName(): string {
		return 'Remove token from shares related to circles';
	}

	public function run(IOutput $output): void {
		if ($this->appConfig->getAppValueBool(ConfigLexicon::REMOVE_SHARE_TOKENS_DONE)) {
			return;
		}

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update('share')
			->set('token', $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_CIRCLE, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->isNotNull('token'))
			->setMaxResults(1000);

		while (true) {
			$updated = $qb->executeStatement();
			if ($updated === 0) {
				break;
			}
		}

		$this->appConfig->setAppValueBool(ConfigLexicon::REMOVE_SHARE_TOKENS_DONE, true);
	}
}
