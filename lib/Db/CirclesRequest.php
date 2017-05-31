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
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;
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
	 * @param string $userId
	 *
	 * @return Circle
	 */
	public function getDetails($circleId, $userId = '') {
		$qb = $this->getCirclesSelectSql();

		$this->limitToId($qb, $circleId);
		if ($userId !== '') {
			$this->leftJoinUserIdAsMember($qb, $userId);
		}

//		$this->leftjoinOwner($qb);
//		$this->buildWithMemberLevel($qb, 'u.level', $level);
//		$this->buildWithCircleId($qb, 'c.id', $circleId);
//		$this->buildWithOrXTypes($qb, $userId, $type, $name, $circleId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$entry = $this->parseCirclesSelectSql($data);

		return $entry;
	}


	/**
	 * saveFrame()
	 *
	 * Insert a new entry in the database to save the SharingFrame.
	 *
	 * @param SharingFrame $frame
	 */
	public function saveFrame(SharingFrame $frame) {

		$qb = $this->getSharesInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($frame->getCircleId()))
		   ->setValue('source', $qb->createNamedParameter($frame->getSource()))
		   ->setValue('type', $qb->createNamedParameter($frame->getType()))
		   ->setValue('headers', $qb->createNamedParameter($frame->getHeaders(true)))
		   ->setValue('author', $qb->createNamedParameter($frame->getAuthor()))
		   ->setValue('cloud_id', $qb->createNamedParameter($frame->getCloudId()))
		   ->setValue('unique_id', $qb->createNamedParameter($frame->getUniqueId()))
		   ->setValue('payload', $qb->createNamedParameter($frame->getPayload(true)));

		$qb->execute();
	}


	public function updateFrame(SharingFrame $frame) {
		$qb = $this->getSharesUpdateSql($frame->getUniqueId());
		$qb->set('circle_id', $qb->createNamedParameter($frame->getCircleId()))
		   ->set('source', $qb->createNamedParameter($frame->getSource()))
		   ->set('type', $qb->createNamedParameter($frame->getType()))
		   ->set('headers', $qb->createNamedParameter($frame->getHeaders(true)))
		   ->set('author', $qb->createNamedParameter($frame->getAuthor()))
		   ->set('cloud_id', $qb->createNamedParameter($frame->getCloudId()))
		   ->set('unique_id', $qb->createNamedParameter($frame->getUniqueId()))
		   ->set('payload', $qb->createNamedParameter($frame->getPayload(true)));

		$qb->execute();
	}


	/**
	 * @param string $uniqueId
	 *
	 * @return Circle
	 */
	public function getCircle($uniqueId) {
		$qb = $this->getCirclesSelectSql();
		$this->limitToUniqueId($qb, (string)$uniqueId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$entry = $this->parseCirclesSelectSql($data);

		return $entry;
	}


	/**
	 * @param int $circleId
	 * @param string $uniqueId
	 *
	 * @return SharingFrame
	 */
	public function getFrame($circleId, $uniqueId) {
		$qb = $this->getSharesSelectSql();
		$this->limitToUniqueId($qb, (string)$uniqueId);
		$this->limitToCircleId($qb, (int)$circleId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$entry = $this->parseSharesSelectSql($data);

		return $entry;
	}


	/**
	 * return the FederatedLink identified by a remote Circle UniqueId and the Token of the link
	 *
	 * @param string $token
	 * @param string $uniqueId
	 *
	 * @return FederatedLink
	 */
	public function getLinkFromToken($token, $uniqueId) {
		$qb = $this->getLinksSelectSql();
		$this->limitToUniqueId($qb, (string)$uniqueId);
		$this->limitToToken($qb, (string)$token);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$entry = $this->parseLinksSelectSql($data);

		return $entry;


	}

	/**
	 * @param integer $circleId
	 * @param int $level
	 *
	 * @return Member[]
	 */
	public function getMembers($circleId, $level = Member::LEVEL_MEMBER) {
		$qb = $this->getMembersSelectSql();
		$this->limitToMemberLevel($qb, $level);

		$this->joinCircles($qb, 'm.circle_id');
		$this->limitToCircleId($qb, $circleId);

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