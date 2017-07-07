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


use Doctrine\DBAL\Query\QueryBuilder;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;
use OCP\DB\QueryBuilder\IQueryBuilder;

class CirclesRequestBuilder extends CoreRequestBuilder {

	/**
	 * Join the Circles table
	 *
	 * @param IQueryBuilder $qb
	 * @param string $field
	 */
	protected function joinCircles(& $qb, $field) {
		$expr = $qb->expr();

		$qb->from(self::TABLE_CIRCLES, 'c')
		   ->andWhere($expr->eq('c.id', $field));
	}


	/**
	 * add a request to the members list, using the current user ID.
	 * will returns level and stuff.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	protected function leftJoinUserIdAsViewer(IQueryBuilder & $qb, $userId) {

		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = $this->default_select_alias . '.';

		$qb->selectAlias('u.user_id', 'viewer_userid');
		$qb->selectAlias('u.level', 'viewer_level');
		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->leftJoin(
			$this->default_select_alias, CoreRequestBuilder::TABLE_MEMBERS, 'u',
			$expr->andX(
				$expr->eq($pf . 'id', 'u.circle_id'),
				$expr->eq('u.user_id', $qb->createNamedParameter($userId))
			)
		);
	}

	/**
	 * @param IQueryBuilder $qb
	 *
	 * @deprecated
	 * never used in fact.
	 */
	protected function leftJoinOwner(IQueryBuilder & $qb) {

		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = $this->default_select_alias . '.';

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->leftJoin(
			$this->default_select_alias, MembersMapper::TABLENAME, 'o',
			$expr->andX(
				$expr->eq($pf . 'id', 'o.circle_id'),
				$expr->eq('o.level', $qb->createNamedParameter(Member::LEVEL_OWNER))
			)
		);
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getLinksSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('id', 'status', 'address', 'token', 'circle_id', 'unique_id', 'creation')
		   ->from('circles_links', 's');

		$this->default_select_alias = 's';

		return $qb;
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'circle_id', 'source', 'type', 'author', 'cloud_id', 'payload', 'creation', 'headers',
			'unique_id'
		)
		   ->from('circles_shares', 's');

		$this->default_select_alias = 's';

		return $qb;
	}

	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert('circles_shares')
		   ->setValue('creation', $qb->createFunction('NOW()'));

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Shares
	 *
	 * @param string $uniqueId
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesUpdateSql($uniqueId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update('circles_shares')
		   ->where(
			   $qb->expr()
				  ->eq('unique_id', $qb->createNamedParameter((string)$uniqueId))
		   );

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Shares
	 *
	 * @param int $circleId
	 *
	 * @return IQueryBuilder
	 */
	protected function getCirclesUpdateSql($circleId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update('circles_circles')
		   ->where(
			   $qb->expr()
				  ->eq('id', $qb->createNamedParameter($circleId))
		   );

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getMembersSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('m.user_id', 'm.circle_id', 'm.level', 'm.status', 'm.joined')
		   ->from('circles_members', 'm');

		$this->default_select_alias = 'm';

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getCirclesSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'c.id', 'c.unique_id', 'c.name', 'c.description', 'c.settings', 'c.type', 'c.creation'
		)
		   ->from('circles_circles', 'c');
		$this->default_select_alias = 'c';

		return $qb;
	}

	/**
	 * @param array $data
	 *
	 * @return Member
	 */
	protected function parseMembersSelectSql(array $data) {
		$member = new Member($this->l10n);
		$member->setUserId($data['user_id']);
		$member->setCircleId($data['circle_id']);
		$member->setLevel($data['level']);
		$member->setStatus($data['status']);
		$member->setJoined($data['joined']);

		return $member;
	}


	/**
	 * @param array $data
	 *
	 * @return Circle
	 */
	protected function parseCirclesSelectSql($data) {
		if ($data === false || $data === null) {
			return null;
		}

		$circle = new Circle($this->l10n);
		$circle->setId($data['id']);
		$circle->setUniqueId($data['unique_id']);
		$circle->setName($data['name']);
		$circle->setDescription($data['description']);
		$circle->setSettings($data['settings']);
		$circle->setType($data['type']);
		$circle->setCreation($data['creation']);

		if (key_exists('viewer_level', $data)) {
			$user = new Member($this->l10n);
			$user->setUserId($data['viewer_userid']);
			$user->setLevel($data['viewer_level']);
			$circle->setViewer($user);
		}

		return $circle;
	}


	/**
	 * @param array $data
	 *
	 * @return SharingFrame
	 */
	protected function parseSharesSelectSql($data) {
		if ($data === false || $data === null) {
			return null;
		}

		$frame = new SharingFrame($data['source'], $data['type']);
		$frame->setCircleId($data['circle_id']);
		$frame->setAuthor($data['author']);
		$frame->setCloudId($data['cloud_id']);
		$frame->setPayload(json_decode($data['payload'], true));
		$frame->setCreation($data['creation']);
		$frame->setHeaders(json_decode($data['headers'], true));
		$frame->setUniqueId($data['unique_id']);

		return $frame;
	}


	/**
	 * @param array $data
	 *
	 * @return FederatedLink
	 */
	public function parseLinksSelectSql($data) {
		if ($data === false || $data === null) {
			return null;
		}

		$link = new FederatedLink();
		$link->setId($data['id'])
			 ->setUniqueId($data['unique_id'])
			 ->setStatus($data['status'])
			 ->setCreation($data['creation'])
			 ->setAddress($data['address'])
			 ->setToken($data['token'])
			 ->setCircleId($data['circle_id']);

		return $link;
	}


}