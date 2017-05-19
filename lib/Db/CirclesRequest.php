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
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Frame;
use OCA\Circles\Service\MiscService;
use OCP\IDBConnection;

class CirclesRequest extends CirclesRequestBuilder {

	/** @var MiscService */
	private $miscService;

	/**
	 * CirclesRequest constructor.
	 *
	 * @param L10N $l10n
	 * @param IDBConnection $connection
	 * @param MiscService $miscService
	 */
	public function __construct(L10N $l10n, IDBConnection $connection, MiscService $miscService) {
		$this->l10n = $l10n;
		$this->dbConnection = $connection;
		$this->miscService = $miscService;
	}


	/**
	 * @param int $circleId
	 * @param int $userId
	 *
	 * @return Circle
	 */
	public function getDetails($circleId, $userId = '') {
		$qb = $this->getCirclesSelectSql();

		$this->limitToId($qb, $circleId);
		if ($userId !== '')
			$this->limitToUserId($qb, $userId);

//		$this->leftjoinOwner($qb);
//		$this->buildWithMemberLevel($qb, 'u.level', $level);
//		$this->buildWithCircleId($qb, 'c.id', $circleId);
//		$this->buildWithOrXTypes($qb, $userId, $type, $name, $circleId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$entry = $this->parseCirclesSelectSql($data);

		return $entry;
	}


	public function createShare(Frame $share) {

		$qb = $this->getSharesInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($share->getCircleId()))
		   ->setValue('source', $qb->createNamedParameter($share->getSource()))
		   ->setValue('type', $qb->createNamedParameter($share->getType()))
		   ->setValue('author', $qb->createNamedParameter($share->getAuthor()))
		   ->setValue('sharer', $qb->createNamedParameter($share->getSharer()))
		   ->setValue('payload', $qb->createNamedParameter($share->getPayload(true)));

		$qb->execute();
	}


	/**
	 * @param $circleId
	 * @param int $level
	 *
	 * @return Member[]
	 */
	public function getMembers($circleId, $level = Member::LEVEL_MEMBER) {
		$qb = $this->getMembersSelectSql();
		$this->limitToMemberLevel($qb, $level);

		$this->joinCircles($qb, 'm.circle_id');
		$this->limitToCircle($qb, $circleId);

		$qb->selectAlias('c.name', 'circle_name');

		$users = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$member = $this->parseMembersSelectSql($data);
			if ($member !== null) {
				$users[] = $member;
			}
		}
		$cursor->closeCursor();

		return $users;
	}


}