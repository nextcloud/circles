<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
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

namespace OCA\Teams;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Teams\Exceptions\MemberExists;
use OCP\IDBConnection;
use OCP\ILogger;
use OCA\Teams\Exceptions\TeamExists;

class DatabaseHandler {

	const PENDING = 'pending';
	const ACCEPTED = 'accepted';
	const DECLINED = 'declined';

	/** @var IDBConnection */
	private $db;
	/** @var ILogger */
	private $logger;

	public function __construct(IDBConnection $dbConn, ILogger $logger) {
		$this->db = $dbConn;
		$this->logger = $logger;
	}

	/**
	 * Get all teams a user is admin of
	 *
	 * @param string $uid ID of the user
	 * @return array an array of numeric teams ids with their name:
	 * 			[
	 * 				['id' => 1, 'name' => 'team1'],
	 * 				['id' => 2, 'name' => 'team2']
	 * 			]
	 * @throws \Doctrine\DBAL\Exception\DriverException
	 */
	public function getTeamsByAdmin($uid) {
		$qb = $this->db->getQueryBuilder();
		$cursor = $qb->select(['id', 'name'])
			->from('teams_teams')
			->where($qb->expr()->eq('owner', $qb->createNamedParameter($uid)))
			->orderBy('name', 'ASC')
			->execute();
		$teams = [];
		while ($row = $cursor->fetch()) {
			$teams[] = [
				'id' => (int)$row['id'],
				'name' => $row['name'],
			];
		}
		$cursor->closeCursor();
		return $teams;
	}

	/**
	 * Get all teams a user is member of
	 *
	 * @param string $uid ID of the user
	 * @return array an array of numeric team ids with their name, owner and status:
	 * 			[
	 * 				['id' => 1, 'name' => 'team1', 'owner' => 'mark', 'status' => 'accepted'],
	 * 				['id' => 2, 'name' => 'team2', 'owner' => 'mark', 'status' => '']
	 * 			]
	 * @throws \Doctrine\DBAL\Exception\DriverException
	 */
	public function getTeamsByMember($uid) {
		$qb = $this->db->getQueryBuilder();
		$qb->select(['t.id', 't.name', 't.owner', 'm.status'])
			->from('teams_teams', 't')
			->join('t', 'teams_members', 'm', 't.id = m.team_id')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)))
			->orderBy('name', 'ASC');
		\OC::$server->getLogger()->error($qb->getSQL());
		$cursor = $qb->execute();
		$teams = [];
		while ($row = $cursor->fetch()) {
			\OC::$server->getLogger()->error(json_encode($row));
			$teams[] = [
				'id' => (int)$row['id'],
				'name' => $row['name'],
				'owner' => $row['owner'],
				'status' => $row['status'],
			];
		}
		$cursor->closeCursor();
		return $teams;
	}

	/**
	 * Creates a new team
	 *
	 * @param string $name name
	 * @param string $owner user_id of owner
	 * @return int team id
	 * @throws \OCA\Teams\Exceptions\TeamExists if entry exists already
	 */
	public function createTeam($name, $owner) {
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->insert('teams_teams')
				->setValue('name', $qb->createNamedParameter($name))
				->setValue('owner', $qb->createNamedParameter($owner))
				->execute();
			return $qb->getLastInsertId();
		} catch (UniqueConstraintViolationException $e) {
			throw new TeamExists();
		}
	}

	/**
	 * Updates the name of a team
	 *
	 * @param int $teamId team id
	 * @param string $owner user_id of owner
	 * @param string $name name
	 * @return int affected rows
	 * @throws \OCA\Teams\Exceptions\TeamExists if entry exists already
	 */
	public function updateTeam($teamId, $owner, $name) {
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->update('teams_teams')
				->setValue('name', $qb->createNamedParameter($name))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($teamId)))
				->andWhere($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
			return $qb->execute();
		} catch (UniqueConstraintViolationException $e) {
			throw new TeamExists();
		}
	}

	/**
	 * Deletes a team
	 *
	 * @param int $teamId team id
	 * @param string $owner user_id of owner
	 * @return int affected rows
	 */
	public function deleteTeam($teamId, $owner) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('teams_teams')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($teamId)))
			->andWhere($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
		return $qb->execute();
	}

	/**
	 * Deletes a team
	 *
	 * @param int $teamId team id
	 * @param string $memberId id of the member
	 * @return int affected rows
	 */
	public function removeMember($teamId, $memberId) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('teams_members')
			->where($qb->expr()->eq('team_id', $qb->createNamedParameter($teamId)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($memberId)));
		return $qb->execute();
	}

	/**
	 * Adds a member to a team
	 *
	 * @param int $teamId team id
	 * @param string $memberId id of the member
	 * @throws \OCA\Teams\Exceptions\MemberExists if entry exists already
	 */
	public function addMember($teamId, $memberId) {
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->insert('teams_members')
				->setValue('team_id', $qb->createNamedParameter($teamId))
				->setValue('user_id', $qb->createNamedParameter($memberId))
				->setValue('status', $qb->createNamedParameter(self::PENDING))
				->execute();
		} catch (UniqueConstraintViolationException $e) {
			throw new MemberExists();
		}
	}

	/**
	 * Get all teams a user is admin of
	 *
	 * @param string $teamId ID of the team
	 * @param string $owner ID of the owner
	 * @return boolean
	 */
	public function isOwner($teamId, $owner) {
		$qb = $this->db->getQueryBuilder();
		$cursor = $qb->select(['id', 'name'])
			->from('teams_teams')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($teamId)))
			->where($qb->expr()->eq('owner', $qb->createNamedParameter($owner)))
			->execute();
		try {
			$result = $cursor->fetchAll();
			$cursor->closeCursor();
			if (is_array($result) && count($result) > 0) {
				return true;
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	/**
	 * Get all teams a user is admin of
	 *
	 * @param string $teamId ID of the team
	 * @param string $userId ID of the member
	 * @return boolean
	 */
	public function isMember($teamId, $userId) {
		$qb = $this->db->getQueryBuilder();
		$cursor = $qb->select(['id', 'name'])
			->from('teams_members')
			->where($qb->expr()->eq('team_id', $qb->createNamedParameter($teamId)))
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->execute();
		try {
			$result = $cursor->fetchAll();
			$cursor->closeCursor();
			if (is_array($result) && count($result) > 0) {
				return true;
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	/**
	 * Get all teams a user is member of
	 *
	 * @param string $teamId ID of the user
	 * @return array an array of users and their status:
	 * 			[
	 * 				['user_id' => 'mark', 'status' => 'accepted'],
	 * 				['user_id' => 'maik', 'status' => 'pending']
	 * 			]
	 */
	public function getMembers($teamId) {
		$qb = $this->db->getQueryBuilder();
		$cursor = $qb->select(['user_id', 'status'])
			->from('teams_members')
			->where($qb->expr()->eq('team_id', $qb->createNamedParameter($teamId)))
			->orderBy('user_id')
			->execute();
		$members = [];
		while ($row = $cursor->fetch()) {
			$members[] = [
				'user_id' => $row['user_id'],
				'status' => $row['status'],
			];
		}
		$cursor->closeCursor();
		return $members;
	}

}