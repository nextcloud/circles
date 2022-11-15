<?php

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

namespace OCA\Circles\Db;

use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\TimezoneService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

/**
 * @deprecated
 * Class FederatedLinksRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class FederatedLinksRequestBuilder extends DeprecatedRequestBuilder {
	/**
	 * CirclesRequestBuilder constructor.
	 *
	 * {@inheritdoc}
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, ConfigService $configService,
		TimezoneService $timezoneService, MiscService $miscService
	) {
		parent::__construct($l10n, $connection, $configService, $timezoneService, $miscService);
	}


	/**
	 * Base of the Sql Insert request
	 *
	 * @return IQueryBuilder
	 */
	protected function getLinksInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_LINKS)
		   ->setValue('creation', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}


	/**
	 * Base of the Sql Update request
	 *
	 * @return IQueryBuilder
	 */
	protected function getLinksUpdateSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_LINKS);

		return $qb;
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getLinksSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'l.id', 'l.status', 'l.address', 'l.token', 'l.circle_id', 'l.unique_id', 'l.creation'
		)
		   ->from(self::TABLE_LINKS, 'l');

		$this->default_select_alias = 'l';

		return $qb;
	}

	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getLinksDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_LINKS);

		return $qb;
	}

	/**
	 * @param array $data
	 *
	 * @return FederatedLink
	 */
	protected function parseLinksSelectSql($data) {
		$link = new FederatedLink();
		$link->setId($data['id'])
			 ->setUniqueId($data['unique_id'])
			 ->setStatus($data['status'])
			 ->setCreation($data['creation'])
			 ->setAddress($data['address'])
			 ->setToken($data['token'])
			 ->setCircleId($data['circle_id']);

		return $link;
	}
}
