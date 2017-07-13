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

