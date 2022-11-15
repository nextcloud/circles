<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 * @author Daniel Tygel <dtygel@eita.org.br>
 *
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
use OC;
use OCA\Circles\Model\DeprecatedMember;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\NotFoundException;
use OCP\Share\IShare;

/**
 * @deprecated
 * Class CircleProviderRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class CircleProviderRequestBuilder extends DeprecatedRequestBuilder {
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
		$this->limitToCircles($qb, [$circleId]);
		$this->limitToFiles($qb, $fileId);

		return $qb;
	}


	/**
	 * Limit the request to the given Circles.
	 *
	 * @param IQueryBuilder $qb
	 * @param array $circleUniqueIds
	 */
	protected function limitToCircles(IQueryBuilder $qb, $circleUniqueIds) {
		if (!is_array($circleUniqueIds)) {
			$circleUniqueIds = [$circleUniqueIds];
		}

		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? 's.' : '';
		$qb->andWhere(
			$expr->in(
				$pf . 'share_with',
				$qb->createNamedParameter($circleUniqueIds, IQueryBuilder::PARAM_STR_ARRAY)
			)
		);
	}


	/**
	 * Limit the request to the Share by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param $shareId
	 */
	protected function limitToShare(IQueryBuilder $qb, $shareId) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? 's.' : '';

		$qb->andWhere($expr->eq($pf . 'id', $qb->createNamedParameter($shareId)));
	}


	/**
	 * Limit the request to the top share (no children)
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToShareParent(IQueryBuilder $qb) {
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
	protected function limitToShareChildren(IQueryBuilder $qb, $userId, $parentId = -1) {
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
	protected function limitToShareAndChildren(IQueryBuilder $qb, $circleId) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? 's.' : '';

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
	 * @param $files
	 */
	protected function limitToFiles(IQueryBuilder $qb, $files) {
		if (!is_array($files)) {
			$files = [$files];
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
	protected function limitToPage(IQueryBuilder $qb, $limit = -1, $offset = 0) {
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
	protected function limitToShareOwner(IQueryBuilder $qb, $userId, $reShares = false) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? 's.' : '';

		if ($reShares === false) {
			$qb->andWhere($expr->eq($pf . 'uid_initiator', $qb->createNamedParameter($userId)));
		} else {
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
	 * @param IQueryBuilder $qb
	 * @param int $shareId
	 *
	 * @deprecated
	 *
	 */
	protected function linkCircleField(IQueryBuilder $qb, $shareId = -1) {
		$expr = $qb->expr();

		$qb->from(DeprecatedRequestBuilder::TABLE_CIRCLES, 'c');

		$tmpOrX = $expr->eq('s.share_with', 'c.unique_id');

		if ($shareId === -1) {
			$qb->andWhere($tmpOrX);

			return;
		}

		$qb->andWhere(
			$expr->orX(
				$tmpOrX,
				$expr->eq('s.parent', $qb->createNamedParameter($shareId))
			)
		);
	}


	/**
	 * @param IQueryBuilder $qb
	 */
	protected function linkToCircleOwner(IQueryBuilder $qb) {
		$expr = $qb->expr();

		$qb->selectAlias('mo.user_id', 'circle_owner');
		$qb->leftJoin(
			'c', DeprecatedRequestBuilder::TABLE_MEMBERS, 'mo', $expr->andX(
				$expr->eq('mo.circle_id', 'c.unique_id'),
				$expr->eq('mo.user_type', $qb->createNamedParameter(DeprecatedMember::TYPE_USER)),
				$expr->eq('mo.level', $qb->createNamedParameter(DeprecatedMember::LEVEL_OWNER))
			)
		);
	}


	/**
	 * Link to member (userId) of circle
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 * @param bool $groupMemberAllowed
	 * @param string $aliasCircles
	 */
	protected function linkToMember(
		IQueryBuilder $qb, string $userId, bool $groupMemberAllowed, string $aliasCircles
	) {
		$qb->from(DeprecatedRequestBuilder::TABLE_MEMBERS, 'mcm');
		$expr = $qb->expr();
		$orX = $expr->orX();

		$orX->add($this->exprLinkToMemberAsCircleMember($qb, $userId, 'mcm', $aliasCircles));
		if ($groupMemberAllowed) {
			$orX->add($this->exprLinkToMemberAsGroupMember($qb, $userId, 'mcm', $aliasCircles));
		}

		$qb->andWhere($orX);
	}


	/**
	 * generate CompositeExpression to link to a Member as a Real Circle Member
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 * @param string $aliasM
	 * @param string $aliasC
	 *
	 * @return ICompositeExpression
	 */
	private function exprLinkToMemberAsCircleMember(
		IQueryBuilder $qb, string $userId, string $aliasM, string $aliasC
	): ICompositeExpression {
		$expr = $qb->expr();
		$andX = $expr->andX();

		$andX->add($expr->eq($aliasM . '.user_id', $qb->createNamedParameter($userId)));
		$andX->add($expr->eq($aliasM . '.circle_id', $aliasC . '.unique_id'));
		$andX->add($expr->gte($aliasM . '.level', $qb->createNamedParameter(DeprecatedMember::LEVEL_MEMBER)));
		$andX->add($expr->eq($aliasM . '.instance', $qb->createNamedParameter('')));
		$andX->add($expr->eq($aliasM . '.user_type', $qb->createNamedParameter(DeprecatedMember::TYPE_USER)));

		return $andX;
	}


	/**
	 * generate CompositeExpression to link to a Member as a Group Member (core NC)
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 * @param string $aliasM
	 * @param string $aliasC
	 *
	 * @return ICompositeExpression
	 */
	private function exprLinkToMemberAsGroupMember(
		IQueryBuilder $qb, string $userId, string $aliasM, string $aliasC
	) {
		$expr = $qb->expr();
		$qb->leftJoin(
			$aliasM, self::NC_TABLE_GROUP_USER, 'ncgu',
			$expr->eq('ncgu.uid', $qb->createNamedParameter($userId))
		);

		$andX = $expr->andX();

		$andX->add($expr->eq($aliasM . '.user_id', 'ncgu.gid'));
		$andX->add($expr->eq($aliasM . '.user_type', $qb->createNamedParameter(DeprecatedMember::TYPE_GROUP)));
		$andX->add($expr->eq($aliasM . '.instance', $qb->createNamedParameter('')));
		$andX->add($expr->eq($aliasM . '.circle_id', $aliasC . '.unique_id'));
		$andX->add($expr->gte($aliasM . '.level', $qb->createNamedParameter(DeprecatedMember::LEVEL_MEMBER)));

		return $andX;
	}


	/**
	 * Link to all members of circle
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function joinCircleMembers(IQueryBuilder $qb) {
		$expr = $qb->expr();

		$qb->addSelect('m.user_id')
		   ->from(DeprecatedRequestBuilder::TABLE_MEMBERS, 'm')
		   ->andWhere(
		   	$expr->andX(
		   		$expr->eq('s.share_with', 'm.circle_id'),
		   		$expr->eq('m.user_type', $qb->createNamedParameter(DeprecatedMember::TYPE_USER))
		   	)
		   );
	}


	/**
	 * Link to storage/filecache
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	protected function linkToFileCache(IQueryBuilder $qb, $userId) {
		$expr = $qb->expr();

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
	 * @throws NotFoundException
	 */
	protected function getBaseInsertSql($share) {
		$qb = $this->dbConnection->getQueryBuilder();
		$hasher = OC::$server->getHasher();
		$password = ($share->getPassword() !== null) ? $hasher->hash($share->getPassword()) : '';

		$qb->insert('share')
		   ->setValue('share_type', $qb->createNamedParameter(IShare::TYPE_CIRCLE))
		   ->setValue('item_type', $qb->createNamedParameter($share->getNodeType()))
		   ->setValue('item_source', $qb->createNamedParameter($share->getNodeId()))
		   ->setValue('file_source', $qb->createNamedParameter($share->getNodeId()))
		   ->setValue('file_target', $qb->createNamedParameter($share->getTarget()))
		   ->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()))
		   ->setValue('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
		   ->setValue('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
		   ->setValue('accepted', $qb->createNamedParameter(IShare::STATUS_ACCEPTED))
		   ->setValue('password', $qb->createNamedParameter($password))
		   ->setValue('permissions', $qb->createNamedParameter($share->getPermissions()))
		   ->setValue('token', $qb->createNamedParameter($share->getToken()))
		   ->setValue('stime', (string)$qb->createFunction('UNIX_TIMESTAMP()'));

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

		$qb->select(
			's.id', 's.share_type', 's.share_with', 's.uid_owner', 's.uid_initiator',
			's.parent', 's.item_type', 's.item_source', 's.item_target', 's.permissions', 's.stime',
			's.accepted', 's.expiration', 's.token', 's.mail_send', 'c.type AS circle_type',
			'c.name AS circle_name', 'c.alt_name AS circle_alt_name'
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

		$this->joinCircleMembers($qb);
		$this->joinShare($qb);

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getCompleteSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->selectDistinct('s.id')
		   ->addSelect(
		   	's.*', 'f.fileid', 'f.path', 'f.permissions AS f_permissions', 'f.storage',
		   	'f.path_hash', 'f.parent AS f_parent', 'f.name', 'f.mimetype', 'f.mimepart',
		   	'f.size', 'f.mtime', 'f.storage_mtime', 'f.encrypted', 'f.unencrypted_size',
		   	'f.etag', 'f.checksum', 'c.type AS circle_type', 'c.name AS circle_name',
		   	'c.alt_name AS circle_alt_name'
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
	private function joinShare(IQueryBuilder $qb) {
		$expr = $qb->expr();

		$qb->addSelect('s.file_source', 's.file_target');
		$qb->from('share', 's')
		   ->andWhere($expr->eq('s.share_type', $qb->createNamedParameter(IShare::TYPE_CIRCLE)))
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
	 * @return IQueryBuilder
	 */
	protected function getBaseDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		$qb->delete('share')
		   ->where($expr->eq('share_type', $qb->createNamedParameter(IShare::TYPE_CIRCLE)));

		return $qb;
	}


	/**
	 * generate and return a base sql request.
	 *
	 * @return IQueryBuilder
	 */
	protected function getBaseUpdateSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		$qb->update('share')
		   ->where($expr->eq('share_type', $qb->createNamedParameter(IShare::TYPE_CIRCLE)));

		return $qb;
	}
}
