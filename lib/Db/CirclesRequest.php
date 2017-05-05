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


use OCA\Circles\Db\CirclesRequestBuilder;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Share;
use OCA\Circles\Service\MiscService;
use OCP\IDBConnection;

class CirclesRequest extends CirclesRequestBuilder {

	/** @var MiscService */
	private $miscService;

	/**
	 * CirclesRequest constructor.
	 *
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection, MiscService $miscService) {
		$this->dbConnection = $connection;
		$this->miscService = $miscService;
	}


	public function createShare(Share $share) {

		$qb = $this->getSharesInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($share->getCircleId()))
		   ->setValue('source', $qb->createNamedParameter($share->getSource()))
		   ->setValue('type', $qb->createNamedParameter($share->getType()))
		   ->setValue('author', $qb->createNamedParameter($share->getAuthor()))
		   ->setValue('sharer', $qb->createNamedParameter($share->getSharer()))
		   ->setValue('item', $qb->createNamedParameter($share->getItem(true)))
		   ->setValue('creation', $qb->createFunction('NOW()'));

		$qb->execute();
	}


	public function getAudience($circleId) {
		$qb = $this->getMembersSelectSql(Member::LEVEL_MEMBER);
		$this->limitToCircle($qb, $circleId);

		$cursor = $qb->execute();

		$users = [];
		while ($data = $cursor->fetch()) {
			$users[] = $this->parseMembersSelectSql($data);
		}
		$cursor->closeCursor();

		return $users;
	}


}