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


use OCA\Circles\Model\Circle;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCA\Circles\Model\Timezone;

class SharingFrameRequestBuilder extends CoreRequestBuilder {

	/** @var CirclesRequest */
	protected $circlesRequest;

	/** @var MembersRequest */
	protected $membersRequest;

	/**
	 * CirclesRequestBuilder constructor.
	 *
	 * {@inheritdoc}
	 * @param MembersRequest $membersRequest
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, CirclesRequest $circlesRequest,
		MembersRequest $membersRequest, ConfigService $configService, MiscService $miscService
	) {
		parent::__construct($l10n, $connection, $configService, $miscService);
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			's.circle_id', 's.source', 's.type', 's.author', 's.cloud_id', 's.payload',
			's.creation', 's.headers', 's.unique_id'
		)
		   ->from(self::TABLE_SHARES, 's');

		$this->default_select_alias = 's';

		return $qb;
	}


	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_SHARES);

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Shares
	 *
	 * @param string $uniqueId
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesUpdateSql($uniqueId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_SHARES)
		   ->where(
			   $qb->expr()
				  ->eq('unique_id', $qb->createNamedParameter((string)$uniqueId))
		   );

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return SharingFrame
	 */
	protected function parseSharesSelectSql($data) {
		$frame = new SharingFrame($data['source'], $data['type']);

		$circle = new Circle();
		$circle->setUniqueId($data['circle_id']);
		if (key_exists('circle_type', $data)) {
			$circle->setType($data['circle_type']);
			$circle->setName($data['circle_name']);
		}

		$frame->setCircle($circle);

		$frame->setAuthor($data['author']);
		$frame->setCloudId($data['cloud_id']);
		$frame->setPayload(json_decode($data['payload'], true));
		$frame->setCreation($data['creation']);
		$frame->setHeaders(json_decode($data['headers'], true));
		$frame->setUniqueId($data['unique_id']);

		return $frame;
	}


}