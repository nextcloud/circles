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

	public function findAllFromCircle($circleid) {

		try {
			$sql = sprintf('SELECT * FROM *PREFIX*%s WHERE circle_id = ?', self::TABLENAME);

			return $this->findEntity($sql, [$circleid]);
		} catch (DoesNotExistException $dnee) {
			return null;
		}
	}

	public function create(Member $member, &$iError = '') {

		if ($iError === '') {
			$iError = new iError();
		}

		$sql = sprintf(
			'INSERT INTO *PREFIX*%s (circle_id, user_id, level, status, creation) VALUES (?, ?, ?, ?, NOW())',
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

