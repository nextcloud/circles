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
//
//	public function findAllFromCircle($circleid) {
//
//		try {
//			$sql = sprintf('SELECT * FROM *PREFIX*%s WHERE circle_id = ?', self::TABLENAME);
//
//			return $this->findEntity($sql, [$circleid]);
//		} catch (DoesNotExistException $dnee) {
//			return null;
//		}
//	}


	public function getMemberFromCircle($circleId, $userId, $iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}

		$circleId = (int)$circleId;

		try {
			$sql = sprintf(
				"SELECT circle_id, user_id, level, status, note, joined FROM *PREFIX*%s WHERE circle_id=? AND user_id=?",
				self::TABLENAME
			);

			$entry = $this->findEntity($sql, [$circleId, $userId]);
			$member = $entry->toModel();

			return $member;
		} catch (MultipleObjectsReturnedException $me) {
			$iError->setCode(iError::MEMBER_CIRCLE_MULTIPLE_ENTRY)
				   ->setMessage('multiple name - fatal error');
		} catch (DoesNotExistException $ne) {
			$iError->setCode(iError::MEMBER_NOT_EXIST)
				   ->setMessage('member does not exist');
		}

		return null;
	}


	public function getMembersFromCircle($circleId, $moderator = false, &$iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}

		$circleId = (int)$circleId;

		try {
			$sql = sprintf(
				"SELECT m.circle_id, m.user_id, m.level, m.status, m.joined %s "
				. "FROM *PREFIX*%s AS m "
				. " WHERE m.circle_id=%d ORDER BY m.user_id ASC "
				,
				(($moderator) ? ', m.note ' : ''),
				self::TABLENAME, $circleId
			);

			$result = $this->execute($sql, [$circleId]);

			$data = [];
			foreach ($result as $entry) {
				$data[] = Member::fromArray($entry);
			}

			return $data;
		} catch (DoesNotExistException $ne) {
			return null;
		}

	}


	public function add(Member $member, &$iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}

		$sql = sprintf(
			'INSERT INTO *PREFIX*%s (circle_id, user_id, level, status, joined) VALUES (?, ?, ?, ?, NOW())',
			self::TABLENAME
		);

		try {
			$this->execute(
				$sql,
				[
					$member->getCircleId(), $member->getUserId(), $member->getLevel(),
					$member->getStatus()
				]
			);
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}
}

