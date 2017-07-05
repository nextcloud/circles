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


use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use OCA\Circles\Model\Member;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Share;
use OCP\Share\IShare;

class CircleProviderRequestBuilder {


	/** @var IDBConnection */
	protected $dbConnection;


	/**
	 * returns the SQL request to get a specific share from the fileId and circleId
	 *
	 * @param int $fileId
	 * @param int $circleId
	 *
	 * @return IQueryBuilder
	 */
	protected function findShareParentSql($fileId, $circleId) {

		$qb = $this->getBaseSelectSql();
		$this->limitToShareParent($qb);
		$this->limitToCircle($qb, $circleId);
		$this->limitToFiles($qb, $fileId);

		return $qb;
	}


	/**
	 * Limit the request to a Circle.
	 *
	 * @param IQueryBuilder $qb
	 * @param integer $circleId
	 */
	protected function limitToCircle(& $qb, $circleId) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? 's.' : '';

		$qb->andWhere($expr->eq($pf . 'share_with', $qb->createNamedParameter($circleId)));
	}


	/**
	 * Limit the request to the Share by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param $shareId
	 */
	protected function limitToShare(& $qb, $shareId) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? 's.' : '';

		$qb->andWhere($expr->eq($pf . 'id', $qb->createNamedParameter($shareId)));
	}


	/**
	 * Limit the request to the top share (no children)
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToShareParent(& $qb) {
		$expr = $qb->expr();

		$qb->andWhere($expr->isNull('parent'));
	}


	/**
	 * limit the request to the children of a share
	 *
	 * @param IQueryBuilder $qb
	 * @param $userId
	 * @param int $parentId
	 */
	protected function limitToShareChildren(& $qb, $userId, $parentId = -1) {
		$expr = $qb->expr();
		$qb->andWhere($expr->eq('share_with', $qb->createNamedParameter($userId)));

		if ($parentId > -1) {
			$qb->andWhere($expr->eq('parent', $qb->createNamedParameter($parentId)));
		} else {
			$qb->andWhere($expr->isNotNull('parent'));
		}
	}


	/**
	 * limit the request to the share itself AND its children.
	 * perfect if you want to delete everything related to a share
	 *
	 * @param IQueryBuilder $qb
	 * @param $circleId
	 */
	protected function limitToShareAndChildren(& $qb, $circleId) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? 's.' : '';

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->andWhere(
			$expr->orX(
				$expr->eq($pf . 'parent', $qb->createNamedParameter($circleId)),
				$expr->eq($pf . 'id', $qb->createNamedParameter($circleId))
			)
		);
	}


	/**
	 * limit the request to a fileId.
	 *
	 * @param IQueryBuilder $qb
	 * @param $fileId
	 */
	protected function limitToFiles(& $qb, $files) {

		if (!is_array($files)) {
			$files = array($files);
		}

		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? 's.' : '';
		$qb->andWhere(
			$expr->in(
				$pf . 'file_source',
				$qb->createNamedParameter($files, IQueryBuilder::PARAM_INT_ARRAY)
			)
		);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int $limit
	 * @param int $offset
	 */
	protected function limitToPage(& $qb, $limit = -1, $offset = 0) {
		if ($limit !== -1) {
			$qb->setMaxResults($limit);
		}

		$qb->setFirstResult($offset);
	}

	/**
	 * limit the request to a userId
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 * @param bool $reShares
	 */
	protected function limitToShareOwner(& $qb, $userId, $reShares = false) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? 's.' : '';

		if ($reShares === false) {
			$qb->andWhere($expr->eq($pf . 'uid_initiator', $qb->createNamedParameter($userId)));
		} else {
			/** @noinspection PhpMethodParametersCountMismatchInspection */
			$qb->andWhere(
				$expr->orX(
					$expr->eq($pf . 'uid_owner', $qb->createNamedParameter($userId)),
					$expr->eq($pf . 'uid_initiator', $qb->createNamedParameter($userId))
				)
			);
		}
	}


	/**
	 * link circle field
	 *
	 * @deprecated
	 *
	 * @param IQueryBuilder $qb
	 * @param integer $shareId
	 */
	// TODO - put this as a leftjoin
	protected function linkCircleField(& $qb, $shareId = -1) {
		$expr = $qb->expr();

		// TODO - Remove this in 12.0.1
		if ($qb->getConnection()
			   ->getDatabasePlatform() instanceof PostgreSqlPlatform
		) {
			$tmpOrX = $expr->eq('s.share_with', $qb->createFunction('CAST(c.id AS TEXT)'));
		} else {
			$tmpOrX =
				$expr->eq('s.share_with', $expr->castColumn('c.id', IQueryBuilder::PARAM_STR));
		}

		$qb->from(CirclesMapper::TABLENAME, 'c');

		if ($shareId === -1) {
			$qb->andWhere($tmpOrX);

			return;
		}

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->andWhere(
			$expr->orX(
				$tmpOrX,
				$expr->eq('s.parent', $qb->createNamedParameter($shareId))
			)
		);
		//->orderBy('c.circle_name');
	}


	/**
	 * @param IQueryBuilder $qb
	 */
	protected function linkToCircleOwner(& $qb) {
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->leftJoin(
			'c', 'circles_members', 'mo', $expr->andX(
			$expr->eq('c.id', 'mo.circle_id'),
			$expr->eq('mo.level', $qb->createNamedParameter(Member::LEVEL_OWNER))
		)
		);
	}


	/**
	 * Link to member (userId) of circle
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	protected function linkToMember(& $qb, $userId) {
		$expr = $qb->expr();

		// TODO - Remove this in 12.0.1
		if ($qb->getConnection()
			   ->getDatabasePlatform() instanceof PostgreSqlPlatform
		) {
			$tmpAndX = $expr->eq('s.share_with', $qb->createFunction('CAST(m.circle_id AS TEXT)'));
		} else {
			$tmpAndX = $expr->eq(
				's.share_with', $expr->castColumn('m.circle_id', IQueryBuilder::PARAM_STR)
			);
		}

		$qb->from(MembersMapper::TABLENAME, 'm')
		   ->andWhere($tmpAndX)
		   ->andWhere($expr->eq('m.user_id', $qb->createNamedParameter($userId)))
		   ->andWhere($expr->gte('m.level', $qb->createNamedParameter(Member::LEVEL_MEMBER)));

	}


	/**
	 * Link to all members of circle
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function joinCircleMembers(& $qb) {
		$expr = $qb->expr();

		$qb->from(MembersMapper::TABLENAME, 'm');

		// TODO - Remove this in 12.0.1
		if ($qb->getConnection()
			   ->getDatabasePlatform() instanceof PostgreSqlPlatform
		) {
			$qb->andWhere(
				$expr->eq('s.share_with', $qb->createFunction('CAST(m.circle_id AS TEXT)'))
			);
		} else {

			$qb->andWhere(
				$expr->eq(
					's.share_with', $expr->castColumn('m.circle_id', IQueryBuilder::PARAM_STR)
				)
			);
		}
	}


	/**
	 * Link to storage/filecache
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	protected function linkToFileCache(& $qb, $userId) {
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->leftJoin('s', 'filecache', 'f', $expr->eq('s.file_source', 'f.fileid'))
		   ->leftJoin('f', 'storages', 'st', $expr->eq('f.storage', 'st.numeric_id'))
		   ->leftJoin(
			   's', 'share', 's2', $expr->andX(
			   $expr->eq('s.id', 's2.parent'),
			   $expr->eq('s2.share_with', $qb->createNamedParameter($userId))
		   )
		   );

		$qb->selectAlias('s2.id', 'parent_id');
		$qb->selectAlias('s2.file_target', 'parent_target');
		$qb->selectAlias('s2.permissions', 'parent_perms');

	}


	/**
	 * add share to the database and return the ID
	 *
	 * @param IShare $share
	 *
	 * @return IQueryBuilder
	 */
	protected function getBaseInsertSql($share) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert('share')
		   ->setValue('share_type', $qb->createNamedParameter(Share::SHARE_TYPE_CIRCLE))
		   ->setValue('item_type', $qb->createNamedParameter($share->getNodeType()))
		   ->setValue('item_source', $qb->createNamedParameter($share->getNodeId()))
		   ->setValue('file_source', $qb->createNamedParameter($share->getNodeId()))
		   ->setValue('file_target', $qb->createNamedParameter($share->getTarget()))
		   ->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()))
		   ->setValue('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
		   ->setValue('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
		   ->setValue('permissions', $qb->createNamedParameter($share->getPermissions()))
		   ->setValue('token', $qb->createNamedParameter($share->getToken()))
		   ->setValue('stime', $qb->createFunction('UNIX_TIMESTAMP()'));

		return $qb;
	}


	/**
	 * generate and return a base sql request.
	 *
	 * @param int $shareId
	 *
	 * @return IQueryBuilder
	 */
	protected function getBaseSelectSql($shareId = -1) {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			's.id', 's.share_type', 's.share_with', 's.uid_owner', 's.uid_initiator',
			's.parent', 's.item_type', 's.item_source', 's.item_target', 's.file_source',
			's.file_target', 's.permissions', 's.stime', 's.accepted', 's.expiration',
			's.token', 's.mail_send', 'c.type AS circle_type', 'c.name AS circle_name',
			'mo.user_id AS circle_owner'
		);
		$this->linkToCircleOwner($qb);
		$this->joinShare($qb);

		// TODO: Left-join circle and REMOVE this line
		$this->linkCircleField($qb, $shareId);

		return $qb;
	}


	/**
	 * Generate and return a base sql request
	 * This one should be used to retrieve a complete list of users (ie. access list).
	 *
	 * @return IQueryBuilder
	 */
	protected function getAccessListBaseSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'm.user_id', 's.file_source', 's.file_target'
		);
		$this->joinCircleMembers($qb);
		$this->joinShare($qb);

		return $qb;
	}


	protected function getCompleteSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			's.*', 'f.fileid', 'f.path', 'f.permissions AS f_permissions', 'f.storage',
			'f.path_hash', 'f.parent AS f_parent', 'f.name', 'f.mimetype', 'f.mimepart',
			'f.size', 'f.mtime', 'f.storage_mtime', 'f.encrypted', 'f.unencrypted_size',
			'f.etag', 'f.checksum', 'c.type AS circle_type', 'c.name AS circle_name',
			'mo.user_id AS circle_owner'
		)
		   ->selectAlias('st.id', 'storage_string_id');

		$this->linkToCircleOwner($qb);
		$this->joinShare($qb);
		$this->linkCircleField($qb);


		return $qb;
	}


	/**
	 * @param IQueryBuilder $qb
	 */
	private function joinShare(& $qb) {
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->from('share', 's')
		   ->where($expr->eq('s.share_type', $qb->createNamedParameter(Share::SHARE_TYPE_CIRCLE)))
		   ->andWhere(
			   $expr->orX(
				   $expr->eq('s.item_type', $qb->createNamedParameter('file')),
				   $expr->eq('s.item_type', $qb->createNamedParameter('folder'))
			   )
		   );
	}


	/**
	 * generate and return a base sql request.
	 *
	 * @return \OCP\DB\QueryBuilder\IQueryBuilder
	 */
	protected function getBaseDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		$qb->delete('share')
		   ->where($expr->eq('share_type', $qb->createNamedParameter(Share::SHARE_TYPE_CIRCLE)));

		return $qb;
	}


	/**
	 * generate and return a base sql request.
	 *
	 * @return \OCP\DB\QueryBuilder\IQueryBuilder
	 */
	protected function getBaseUpdateSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		$qb->update('share')
		   ->where($expr->eq('share_type', $qb->createNamedParameter(Share::SHARE_TYPE_CIRCLE)));

		return $qb;
	}
}
