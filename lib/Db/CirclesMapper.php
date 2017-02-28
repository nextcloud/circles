<?php
/**
 * Circles - bring cloud-users closer
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

use \OCA\Circles\Model\iError;
use \OCA\Circles\Model\Circle;
use \OCA\Circles\Model\Member;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class CirclesMapper extends Mapper {

	const TABLENAME = 'circles_circles';

	private $miscService;

	public function __construct(IDBConnection $db, $miscService) {
		parent::__construct($db, self::TABLENAME, 'OCA\Circles\Db\Circles');
		$this->miscService = $miscService;

	}

	public function find($id) {
		try {
			$sql = sprintf('SELECT * FROM *PREFIX*%s WHERE id = ?', self::TABLENAME);

			return $this->findEntity($sql, [$id]);
		} catch (DoesNotExistException $dnee) {
			return null;
		}
	}


	public function findCirclesByUser($userId, $type, $name = '', $level = 0) {

		$type = (int)$type;
		$level = (int)$level;

		$qb = $this->db->getQueryBuilder();
		$qb->select(
			'c.id', 'c.name', 'c.description', 'c.type', 'c.creation',
			'u.joined', 'u.level', 'u.status'
		)
		   ->selectAlias('o.user_id', 'owner')
		   ->from(self::TABLENAME, 'c')
		   ->from(MembersMapper::TABLENAME, 'o')
		   ->where(
			   $qb->expr()
				  ->eq('c.id', 'o.circle_id'),
			   $qb->expr()
				  ->eq('o.level', $qb->createNamedParameter(Member::LEVEL_OWNER))
		   );

		$qb->leftJoin(
			'c', MembersMapper::TABLENAME, 'u',
			$qb->expr()
			   ->andX(
				   $qb->expr()
					  ->eq('c.id', 'u.circle_id'),
				   $qb->expr()
					  ->eq('u.user_id', $qb->createNamedParameter($userId))
			   )
		);

		$orTypesArray = [];
		if (Circle::CIRCLES_PERSONAL & (int)$type) {
			array_push(
				$orTypesArray,
				$qb->expr()
				   ->andX(
					   $qb->expr()
						  ->eq('c.type', $qb->createNamedParameter(Circle::CIRCLES_PERSONAL)),
					   $qb->expr()
						  ->eq('o.user_id', $qb->createNamedParameter($userId))
				   )
			);
		}

		if (Circle::CIRCLES_HIDDEN & (int)$type) {
			array_push(
				$orTypesArray, $qb->expr()
								  ->andX(
									  $qb->expr()
										 ->eq(
											 'c.type',
											 $qb->createNamedParameter(Circle::CIRCLES_HIDDEN)
										 ),
									  $qb->expr()
										 ->orX(
											 $qb->expr()
												->gte(
													'u.level',
													$qb->createNamedParameter(Member::LEVEL_MEMBER)
												),
											 $qb->expr()
												->eq(
													'c.name',
													$qb->createNamedParameter($name)
												)
										 )
								  )
			);
		}
		if (Circle::CIRCLES_PRIVATE & (int)$type) {
			array_push(
				$orTypesArray, $qb->expr()
								  ->eq(
									  'c.type',
									  $qb->createNamedParameter(Circle::CIRCLES_PRIVATE)
								  )
			);
		}
		if (Circle::CIRCLES_PUBLIC & (int)$type) {
			array_push(
				$orTypesArray, $qb->expr()
								  ->eq(
									  'c.type',
									  $qb->createNamedParameter(Circle::CIRCLES_PUBLIC)
								  )
			);
		}

		if (sizeof($orTypesArray) === 0) {
			return null;
		}

		$orXTypes = $qb->expr()
					   ->orX();

		foreach ($orTypesArray as $orTypes) {
			$orXTypes->add($orTypes);
		}

		$qb->andWhere($orXTypes);

		$qb->groupBy('c.id');
		$qb->orderBy('c.name', 'ASC');

		$cursor = $qb->execute();

		$result = [];
		while ($data = $cursor->fetch()) {
			//		if ($name === '' || strtolower($data['name']) === strtolower($name)) {

			$this->miscService->log("__" . var_export($data, true));
			$result[] = Circle::fromArray($data);
//			}
		}
		$cursor->closeCursor();

		return $result;
	}


	public function getDetailsFromCircle($circleId, $userId, &$iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}

		$circleId = (int)$circleId;

		$orTypes = array();
		array_push(
			$orTypes,
			'(c.type=' . Circle::CIRCLES_PERSONAL . ' AND u.level=' . Member::LEVEL_OWNER
			. ')'
		);
		array_push($orTypes, '(c.type=' . Circle::CIRCLES_HIDDEN . ')');
		array_push($orTypes, '(c.type=' . Circle::CIRCLES_PRIVATE . ')');
		array_push($orTypes, '(c.type=' . Circle::CIRCLES_PUBLIC . ')');

		if (sizeof($orTypes) === 0) {
			return null;
		}

		$sqlTypes = implode(' OR ', $orTypes);


		try {
			$sql = sprintf(
				"SELECT c.id, c.name, c.description, c.type, UNIX_TIMESTAMP(c.creation) AS creation, "
				. "UNIX_TIMESTAMP(u.joined) AS joined, u.level, u.status, "
				. "o.user_id AS owner, "
				. "COUNT(m.user_id) AS count "
				. "FROM (*PREFIX*%s AS c, *PREFIX*%s AS u, *PREFIX*%s AS o) "
				. " LEFT JOIN *PREFIX*%s AS m ON c.id=m.circle_id AND m.status='"
				. Member::STATUS_MEMBER . "'"
				. " WHERE c.id=u.circle_id AND u.user_id=? AND c.id=%d"
				. " AND c.id=o.circle_id AND o.level=" . Member::LEVEL_OWNER
				. " %s "
				. " GROUP BY c.id ORDER BY c.id DESC "
				,
				self::TABLENAME, MembersMapper::TABLENAME, MembersMapper::TABLENAME,
				MembersMapper::TABLENAME,
				$circleId,
				"AND ($sqlTypes)"
			);

			$result = $this->execute($sql, [$userId]);

			$data = null;
			foreach ($result as $entry) {
				$data = Circle::fromArray($entry);
			}

			return $data;
		} catch (DoesNotExistException $ne) {
			return null;
		}

	}


	public function create(Circle &$circle, Member &$owner, &$iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}


		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {

			$list = $this->findCirclesByUser(
				$owner->getUserId(), $circle->getType(), $circle->getName(), Member::LEVEL_OWNER
			);

			foreach ($list as $test) {
				if (stripos($test->getName(), $circle->getName) !== false) {
					$iError->setCode(iError::CIRCLE_CREATION_DUPLICATE_NAME)
						   ->setMessage('duplicate name');

					return false;
				}
			}

		} else {
			try {
				$sql = sprintf(
					"SELECT id FROM *PREFIX*%s WHERE LCASE(name)=? AND type!=%d",
					self::TABLENAME, Circle::CIRCLES_PERSONAL
				);

				$this->findEntity($sql, [strtolower($circle->getName())]);
				$iError->setCode(iError::CIRCLE_CREATION_DUPLICATE_NAME)
					   ->setMessage('duplicate name');

				return false;
			} catch (MultipleObjectsReturnedException $me) {
				$iError->setCode(iError::CIRCLE_CREATION_MULTIPLE_NAME)
					   ->setMessage('multiple name - fatal error');

				return false;
			} catch (DoesNotExistException $ne) {
			}
		}

		$sql = sprintf(
			'INSERT INTO *PREFIX*%s (name, description, type, creation) VALUES(?, ?, ?, NOW())',
			self::TABLENAME
		);

		$this->execute(
			$sql, [$circle->getName(), $circle->getDescription(), $circle->getType()]
		);

		$circleid = $this->db->lastInsertId(self::TABLENAME);
		if ($circleid < 1) {
			$iError->setCode(iError::CIRCLE_INSERT_CIRCLE_DATABASE)
				   ->setMessage('issue creating circle - fatal error');

			return false;
		}

		$circle->setId($circleid);
		$owner->setLevel(9)
			  ->setCircleId($circleid);

		return true;
	}

	public function destroy(Circle $circle) {
		$this->delete(new Circles($circle));
	}

}

