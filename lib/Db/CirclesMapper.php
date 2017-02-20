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


	public function findCirclesByUser($userId, $type, $level = 0) {

		$type = (int)$type;
		$level = (int)$level;

		$orTypes = array();
		if (Circle::CIRCLES_PERSONAL & (int)$type) {
			array_push($orTypes, '(g.type=' . Circle::CIRCLES_PERSONAL . ')');
		}
		if (Circle::CIRCLES_HIDDEN & (int)$type) {
			array_push(
				$orTypes,
				'(g.type=' . Circle::CIRCLES_HIDDEN . ' AND m.level=' . Member::LEVEL_ADMIN
				. ')'
			);
		}
		if (Circle::CIRCLES_PRIVATE & (int)$type) {
			array_push($orTypes, '(g.type=' . Circle::CIRCLES_PRIVATE . ')');
		}
		if (Circle::CIRCLES_PUBLIC & (int)$type) {
			array_push($orTypes, '(g.type=' . Circle::CIRCLES_PUBLIC . ')');
		}

		if (sizeof($orTypes) === 0) {
			return null;
		}

		$sqlTypes = implode(' OR ', $orTypes);

		try {
			$sql = sprintf(
				"SELECT g.id, g.name, g.description, g.type, UNIX_TIMESTAMP(g.creation) AS creation, "
				. "UNIX_TIMESTAMP(u.creation) AS joined, u.level, u.status, "
				. "o.user_id AS owner, "
				. "COUNT(m.user_id) AS count "
				. "FROM (*PREFIX*%s AS g, *PREFIX*%s AS u, *PREFIX*%s AS o) "
				. " LEFT JOIN *PREFIX*%s AS m ON g.id=m.circle_id AND m.status='"
				. Member::STATUS_MEMBER . "'"
				. " WHERE g.id=u.circle_id AND u.user_id=? "
				. " AND g.id=o.circle_id AND o.level=" . Member::LEVEL_ADMIN
				. " %s "
				. " GROUP BY g.id ORDER BY g.id DESC "
				,
				self::TABLENAME, MembersMapper::TABLENAME, MembersMapper::TABLENAME,
				MembersMapper::TABLENAME,
				"AND ($sqlTypes)"
			);

			$result = $this->execute($sql, [$userId]);

			$data = [];
			foreach ($result as $entry) {
				$data[] = Circle::fromArray($entry);
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
				$owner->getUserId(), $circle->getType(), Member::LEVEL_ADMIN
			);

			foreach ($list AS $item) {
				if ($item->getName() === $circle->getName()) {
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
			'INSERT INTO *PREFIX*%s(name, description, type, creation) VALUES(?, ?, ?, NOW())',
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

