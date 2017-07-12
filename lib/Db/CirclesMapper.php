<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

use OC\L10N\L10N;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailable;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;

use OCA\Circles\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class CirclesMapper extends Mapper {

	const TABLENAME = 'circles_circles';

	/** @var string */
	private $userId;

	/** @var L10N */
	private $l10n;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var MiscService */
	private $miscService;

	public function __construct(
		$userId, IDBConnection $db, $l10n, MembersRequest $membersRequest, $miscService
	) {
		parent::__construct($db, self::TABLENAME, 'OCA\Circles\Db\Circles');
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->membersRequest = $membersRequest;
		$this->miscService = $miscService;
	}


	/**
	 * @param $circleName
	 *
	 * @return Circle|null
	 */
	public function getDetailsFromCircleByName($circleName) {
		$qb = $this->isCircleUniqueSql();
		$expr = $qb->expr();

		$qb->andWhere($expr->iLike('c.name', $qb->createNamedParameter($circleName)));
		$qb->andWhere($expr->neq('c.type', $qb->createNamedParameter(Circle::CIRCLES_PERSONAL)));

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			return null;
		}

		$circle = new Circle($this->l10n);
		$circle->setId($data['id']);
		$circle->setName($data['name']);
		$circle->setType($data['type']);
		$circle->setUniqueId($data['unique_id']);
		$circle->setSettings($data['settings']);

		return $circle;
	}


	/**
	 * remove a circle
	 *
	 * @param int $circleId
	 */
	public function destroy($circleId) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLENAME)
		   ->where(
			   $qb->expr()
				  ->eq(
					  'id', $qb->createNamedParameter($circleId)
				  )
		   );

		$qb->execute();
	}


	/**
	 * Return SQL for isCircleUnique();
	 *
	 * @return IQueryBuilder
	 */
	private function isCircleUniqueSql() {
		$qb = $this->db->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'c.id', 'c.unique_id', 'c.name', 'c.type', 'c.settings'
		)
		   ->from(self::TABLENAME, 'c')
		   ->where(
			   $qb->expr()
				  ->neq('c.type', $qb->createNamedParameter(Circle::CIRCLES_PERSONAL))
		   );

		return $qb;
	}
}

