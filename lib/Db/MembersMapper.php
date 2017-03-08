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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class MembersMapper extends Mapper {

	const TABLENAME = 'circles_members';

	private $miscService;

	public function __construct(IDBConnection $db, $miscService) {
		parent::__construct($db, self::TABLENAME, 'OCA\Circles\Db\Members');
		$this->miscService = $miscService;
	}


	/**
	 * @param $circleId
	 * @param $userId
	 * @param bool $moderator
	 *
	 * @return null|Member
	 * @throws MemberDoesNotExistException
	 */
	public function getMemberFromCircle($circleId, $userId, $moderator = false) {

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
		if ($data === false) {
			throw new MemberDoesNotExistException();
		}

		if ($moderator !== true) {
			unset($data['note']);
		}

		$member = new Member();
		$member->fromArray($data);
		$cursor->closeCursor();

		return $member;
	}


	/**
	 * get members list from a circle. If moderator, returns also notes about each member.
	 *
	 * @param $circleId
	 * @param Member $user
	 *
	 * @return array
	 * @internal param Member $member
	 * @internal param bool $moderator
	 *
	 */
	public function getMembersFromCircle($circleId, Member $user) {

		try {
			$user->hasToBeMember();

			$circleId = (int)$circleId;

			$qb = $this->db->getQueryBuilder();
			$qb->select(
				'circle_id', 'user_id', 'level', 'status', 'note', 'joined'
			)
			   ->from(self::TABLENAME)
			   ->where(
				   $qb->expr()
					  ->eq('circle_id', $qb->createNamedParameter($circleId))
			   )
			   ->andwhere(
				   $qb->expr()
					  ->neq('status', $qb->createNamedParameter(Member::STATUS_NONMEMBER))
			   );

			$cursor = $qb->execute();

			$result = [];
			while ($data = $cursor->fetch()) {
				if (!$user->isModerator()) {
					unset($data['note']);
				}

				$member = new Member();
				$member->fromArray($data);
				$result[] = $member;
			}
			$cursor->closeCursor();
		} catch (MemberDoesNotExistException $e) {
			throw new $e;
		}

		return $result;

	}


	public function editMember(Member $member) {

		$qb = $this->db->getQueryBuilder();
		$qb->update(self::TABLENAME);
		$qb->set('level', $qb->createNamedParameter($member->getLevel()));
		$qb->set('status', $qb->createNamedParameter($member->getStatus()));
		$qb->where(
			$qb->expr()
			   ->andX(
				   $qb->expr()
					  ->eq('circle_id', $qb->createNamedParameter($member->getCircleId())),
				   $qb->expr()
					  ->eq('user_id', $qb->createNamedParameter($member->getUserId()))
			   )
		);

		$qb->execute();

		return true;
	}


	/**
	 * Insert Member into database.
	 *
	 * @param Member $member
	 *
	 * @throws MemberAlreadyExistsException
	 */
	public function add(Member $member) {

		try {
			$qb = $this->db->getQueryBuilder();
			$qb->insert(self::TABLENAME)
			   ->setValue('circle_id', $qb->createNamedParameter($member->getCircleId()))
			   ->setValue('user_id', $qb->createNamedParameter($member->getUserId()))
			   ->setValue('level', $qb->createNamedParameter($member->getLevel()))
			   ->setValue('status', $qb->createNamedParameter($member->getStatus()))
			   ->setValue('note', $qb->createNamedParameter($member->getNote()));
			$qb->execute();
		} catch (UniqueConstraintViolationException $e) {
			throw new MemberAlreadyExistsException();
		}
	}


	/**
	 * Remove a member from a circle.
	 *
	 * @param Member $member
	 *
	 * @return bool
	 */
	public function remove(Member $member) {

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

	/**
	 * remove all members/owner from a circle
	 *
	 * @param Circle $circle
	 *
	 * @return bool
	 */
	public function removeAllFromCircle(Circle $circle) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLENAME)
		   ->where(
			   $qb->expr()
				  ->eq('circle_id', $qb->createNamedParameter($circle->getId()))
		   );

		$qb->execute();

		return true;

	}
}

