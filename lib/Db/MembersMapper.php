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
use OC\L10N\L10N;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Model\Member;

use OCA\Circles\Service\MiscService;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class MembersMapper extends Mapper {

	const TABLENAME = 'circles_members';

	/** @var L10N */
	private $l10n;

	/** @var MiscService */
	private $miscService;


	public function __construct(IDBConnection $db, $l10n, $miscService) {
		parent::__construct($db, self::TABLENAME, 'OCA\Circles\Db\Members');
		$this->l10n = $l10n;
		$this->miscService = $miscService;
	}


	/**
	 * @param $circleId
	 * @param $userId
	 * @param bool $moderator
	 *
	 * @return Member
	 * @throws MemberDoesNotExistException
	 */
	public function getMemberFromCircle($circleId, $userId, $moderator = false) {

		$circleId = (int)$circleId;
		$qb = $this->getMemberFromCircleSql($circleId, $userId);
		$cursor = $qb->setMaxResults(1)
					 ->execute();

		$data = $cursor->fetch();
		if ($data === false) {
			// TODO	- Check that the author of the request have privileges or it will return this wrong error
			throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
		}

		if ($moderator !== true) {
			$data['note'] = '';
		}

		$member = Member::fromArray2($this->l10n, $data);
//		$member->fromArray($data);
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

			$qb = $this->getMembersFromCircleSql((int)$circleId);
			$cursor = $qb->execute();
			$result = [];
			while ($data = $cursor->fetch()) {
				if (!$user->isLevel(Member::LEVEL_MODERATOR)) {
					$data['note'] = '';
				}

				$result[] = Member::fromArray2($this->l10n, $data);
			}
			$cursor->closeCursor();

		} catch (MemberDoesNotExistException $e) {
			throw new $e;
		}

		return $result;
	}


	/**
	 * Generate SQL Request for getMemberFromCircle()
	 *
	 * @param integer $circleId
	 * @param $userId
	 *
	 * @return \OCP\DB\QueryBuilder\IQueryBuilder
	 */
	private function getMemberFromCircleSql($circleId, $userId) {
		$qb = $this->getMembersFromCircleSqlBase($circleId);
		$expr = $qb->expr();

		$qb->andWhere(
			$expr->eq('user_id', $qb->createNamedParameter($userId))
		);

		return $qb;
	}


	/**
	 * Return SQL for getMembersFromCircle.
	 *
	 * @param integer $circleId
	 *
	 * @return \OCP\DB\QueryBuilder\IQueryBuilder
	 */
	private function getMembersFromCircleSql($circleId) {
		$qb = $this->getMembersFromCircleSqlBase($circleId);
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->andwhere(
			$expr->neq('status', $qb->createNamedParameter(Member::STATUS_NONMEMBER))
		);

		return $qb;
	}


	/**
	 *
	 * Return the base select request for both
	 * getMembersFromCircleSql()
	 * getMemberFromCircleSql()
	 *
	 * @param $circleId
	 *
	 * @return \OCP\DB\QueryBuilder\IQueryBuilder
	 */
	private function getMembersFromCircleSqlBase($circleId) {
		$qb = $this->db->getQueryBuilder();
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'circle_id', 'user_id', 'level', 'status', 'note', 'joined'
		)
		   ->from(self::TABLENAME)
		   ->where(
			   $expr->eq('circle_id', $qb->createNamedParameter($circleId))
		   );

		return $qb;
	}


	/**
	 * update database entry for a specific Member.
	 *
	 * @param Member $member
	 *
	 * @return bool
	 */
	public function editMember(Member $member) {

		$qb = $this->db->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->update(self::TABLENAME)
		   ->set('level', $qb->createNamedParameter($member->getLevel()))
		   ->set('status', $qb->createNamedParameter($member->getStatus()))
		   ->where(
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
			   ->setValue('note', $qb->createNamedParameter($member->getNote()))
			   ->setValue('joined', $qb->createFunction('NOW()'));

			$qb->execute();
		} catch (UniqueConstraintViolationException $e) {
			throw new MemberAlreadyExistsException(
				$this->l10n->t('This user is already a member of the circle')
			);
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

		/** @noinspection PhpMethodParametersCountMismatchInspection */
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
	 * @param int $circleId
	 *
	 * @return bool
	 */
	public function removeAllFromCircle($circleId) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLENAME)
		   ->where(
			   $qb->expr()
				  ->eq('circle_id', $qb->createNamedParameter($circleId))
		   );

		$qb->execute();

		return true;
	}


	/**
	 * remove all members/owner from a circle
	 *
	 * @param string $userId
	 *
	 * @return bool
	 */
	public function removeAllFromUserId($userId) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLENAME)
		   ->where(
			   $qb->expr()
				  ->eq('user_id', $qb->createNamedParameter($userId))
		   );

		$qb->execute();

		return true;
	}
}

