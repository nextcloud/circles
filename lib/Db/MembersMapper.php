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
use \OCA\Circles\Model\Member;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class MembersMapper extends Mapper {

	const TABLENAME = 'circles_members';

	private $miscService;

	public function __construct(IDBConnection $db, $miscService) {
		parent::__construct($db, self::TABLENAME, 'OCA\Circles\Db\Members');
		$this->miscService = $miscService;
	}


	public function getMemberFromCircle($circleId, $userId, &$iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}

		$circleId = (int)$circleId;

		$qb = $this->db->getQueryBuilder();
		$qb->select(
			'circle_id', 'user_id', 'level', 'status', 'note', 'joined'
		)
		   ->from(self::TABLENAME)
		   ->where(
			   $qb->expr()
				  ->eq('circle_id', $qb->createNamedParameter($circleId))
		   );

		$qb->andWhere(
			$qb->expr()
			   ->eq('user_id', $qb->createNamedParameter($userId))
		);

		$cursor = $qb->setMaxResults(1)
					 ->execute();

		$data = $cursor->fetch();
		$member = Member::fromArray($data);
		$cursor->closeCursor();

		return $member;
	}


	public function getMembersFromCircle($circleId, $moderator = false, &$iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}

		$circleId = (int)$circleId;

		$qb = $this->db->getQueryBuilder();
		$qb->select(
			'circle_id', 'user_id', 'level', 'status', 'note', 'joined'
		)
		   ->from(self::TABLENAME)
		   ->where(
			   $qb->expr()
				  ->eq('circle_id', $qb->createNamedParameter($circleId))
		   );

		$cursor = $qb->execute();

		$result = [];
		while ($data = $cursor->fetch()) {
			if ($moderator !== true) {
				$data['note'] = '';
			}

			$result[] = Member::fromArray($data);
		}
		$cursor->closeCursor();

		return $result;
	}


	public function add(Member $member, &$iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}

		$qb = $this->db->getQueryBuilder();
		$qb->insert(self::TABLENAME)
		   ->setValue('circle_id', $qb->createNamedParameter($member->getCircleId()))
		   ->setValue('user_id', $qb->createNamedParameter($member->getUserId()))
		   ->setValue('level', $qb->createNamedParameter($member->getLevel()))
		   ->setValue('status', $qb->createNamedParameter($member->getStatus()))
		   ->setValue('note', $qb->createNamedParameter($member->getNote()))
		   ->setValue('joined', 'CURRENT_TIMESTAMP()');
		$qb->execute();

		return true;
	}


	public function remove(Member $member, &$iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}

		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLENAME)
		   ->where(
			   $qb->expr()
				  ->eq('circle_id', $qb->createNamedParameter($member->getCircleId())),
			   $qb->expr()
				  ->eq('user_id', $qb->createNamedParameter($member->getUserId()))
		   );

		$qb->execute();

		return true;
	}

}

