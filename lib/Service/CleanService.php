<?php


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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


namespace OCA\Circles\Service;


use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedRequestBuilder;
use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Db\SharesRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Model\DeprecatedCircle;
use OCP\IDBConnection;

/**
 * Class CleanService
 *
 * @package OCA\Circles\Service
 */
class CleanService {


	/** @var IDBConnection */
	private $dbConnection;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;

	/** @var SharesRequest */
	private $sharesRequest;


	/**
	 * Clean constructor.
	 *
	 * @param IDBConnection $connection
	 * @param DeprecatedCirclesRequest $circlesRequest
	 * @param DeprecatedMembersRequest $membersRequest
	 * @param SharesRequest $sharesRequest
	 */
	public function __construct(
		IDBConnection $connection, DeprecatedCirclesRequest $circlesRequest, DeprecatedMembersRequest $membersRequest,
		SharesRequest $sharesRequest
	) {
		$this->dbConnection = $connection;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->sharesRequest = $sharesRequest;
	}


	public function clean() {
		$this->fixUserType();
		$this->removeCirclesWithNoOwner();
		$this->removeMembersWithNoCircles();
		$this->removeDeprecatedShares();
	}


	public function fixUserType() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(DeprecatedRequestBuilder::TABLE_MEMBERS)
		   ->set('user_type', $qb->createNamedParameter(1))
		   ->where(
			   $qb->expr()
				  ->eq('user_type', $qb->createNamedParameter(0))
		   );

		$qb->execute();
	}


	public function removeCirclesWithNoOwner() {
		$circles = $this->circlesRequest->forceGetCircles();

		foreach ($circles as $circle) {
			if ($circle->getOwner()
					   ->getUserId() === null) {
				$this->circlesRequest->destroyCircle($circle->getUniqueId());
			}
		}
	}


	public function removeMembersWithNoCircles() {
		$members = $this->membersRequest->forceGetAllMembers();

		foreach ($members as $member) {
			try {
				$this->circlesRequest->forceGetCircle($member->getCircleId());
			} catch (CircleDoesNotExistException $e) {
				$this->membersRequest->removeMember($member);
			}
		}
	}


	public function removeDeprecatedShares() {
		$circles = array_map(
			function(DeprecatedCircle $circle) {
				return $circle->getUniqueId();
			}, $this->circlesRequest->forceGetCircles()
		);

		$shares = array_unique(
			array_map(
				function($share) {
					return $share['share_with'];
				}, $this->sharesRequest->getShares()
			)
		);

		foreach ($shares as $share) {
			if (!in_array($share, $circles)) {
				$this->sharesRequest->removeSharesToCircleId($share);
			}
		}
	}

}


