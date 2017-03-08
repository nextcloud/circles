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


namespace OCA\Circles;


use \OC\Share20\Exception\InvalidShare;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CirclesMapper;
use OCA\Circles\Db\MembersMapper;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\IRootFolder;
use OC\Files\Cache\Cache;
use OC\Share20\Share;
use OCP\Share\IShare;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShareProvider;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;
use OCP\IUserManager;

class ShareByCircleProvider implements IShareProvider {

	private $misc;


	/** @var  IDBConnection */
	private $dbConnection;

	/** @var ILogger */
	private $logger;

	/** @var ISecureRandom */
	private $secureRandom;

	/** @var IUserManager */
	private $userManager;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IL10N */
	private $l;

	/** @var IURLGenerator */
	private $urlGenerator;


	/**
	 * DefaultShareProvider constructor.
	 *
	 * @param IDBConnection $connection
	 * @param ISecureRandom $secureRandom
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param IL10N $l
	 * @param ILogger $logger
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		IDBConnection $connection,
		ISecureRandom $secureRandom,
		IUserManager $userManager,
		IRootFolder $rootFolder,
		IL10N $l,
		ILogger $logger,
		IURLGenerator $urlGenerator
	) {
		$this->dbConnection = $connection;
		$this->secureRandom = $secureRandom;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->l = $l;
		$this->logger = $logger;

		$app = new Application();
		$this->misc = $app->getContainer()
						  ->query('MiscService');

		$this->urlGenerator = $urlGenerator;
	}


	/**
	 * Return the identifier of this provider.
	 *
	 * @return string Containing only [a-zA-Z0-9]
	 */
	public function identifier() {
		return 'ocCircleShare';
	}

	/**
	 * Create a share
	 *
	 * @param \OCP\Share\IShare $share
	 *
	 * @return IShare The share object
	 * @throws \Exception
	 */
	public function create(IShare $share) {
		$this->misc->log("CircleProvider: create");

		$qb = $this->dbConnection->getQueryBuilder();
		$exists = $qb->select('id')
					 ->from('share')
					 ->where(
						 $qb->expr()
							->eq(
								'share_type',
								$qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE)
							)
					 )
					 ->andWhere(
						 $qb->expr()
							->eq('share_with', $qb->createNamedParameter($share->getSharedWith()))
					 )
					 ->andWhere(
						 $qb->expr()
							->eq(
								'file_source', $qb->createNamedParameter(
								$share->getNode()
									  ->getId()
							)
							)
					 )
					 ->andWhere(
						 $qb->expr()
							->isNull('parent')
					 )
					 ->andWhere(
						 $qb->expr()
							->orX(
								$qb->expr()
								   ->eq('item_type', $qb->createNamedParameter('file')),
								$qb->expr()
								   ->eq('item_type', $qb->createNamedParameter('folder'))
							)
					 )
					 ->setMaxResults(1)
					 ->execute();

		$data = $exists->fetch();
		$exists->closeCursor();


		if ($data !== false && sizeof($data) > 0) {
			$message = 'Sharing %s failed, this item is already shared with this circle';
			$message_t = $this->l->t(
				'Sharing %s failed, this item is already shared with this circle', array(
																					 $share->getNode(
																					 )
																						   ->getName(
																						   )
																				 )
			);
			$this->logger->debug(
				sprintf(
					$message, $share->getNode()
									->getName(), $share->getSharedWith()
				), ['app' => 'circles']
			);
			throw new \Exception($message_t);
		}

		$shareId = $this->addShareToDB(
			$share->getNodeId(),
			$share->getNodeType(),
			$share->getSharedWith(),
			$share->getSharedBy(),
			$share->getTarget(),
			$share->getShareOwner(),
			$share->getPermissions(),
			$share->getToken()
		);

		$data = $this->getRawShare($shareId);

		return $this->createShareObject($data);
	}


	/**
	 * Update a share
	 *
	 * @param \OCP\Share\IShare $share
	 *
	 * @return \OCP\Share\IShare The share object
	 */
	public function update(IShare $share) {
		$this->misc->log("CircleProvider: update");

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update('share')
		   ->where(
			   $qb->expr()
				  ->eq('id', $qb->createNamedParameter($share->getId()))
		   )
		   ->set('permissions', $qb->createNamedParameter($share->getPermissions()))
		   ->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
		   ->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
		   ->execute();

		return $share;
	}

	/**
	 * Delete a share
	 *
	 * @param \OCP\Share\IShare $share
	 */
	public function delete(IShare $share) {
		$this->misc->log("CircleProvider: delete");
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete('share')
		   ->where(
			   $qb->expr()
				  ->eq(
					  'id', $qb->createNamedParameter($share->getId())
				  )
		   )
		   ->andWhere(
			   $qb->expr()
				  ->eq(
					  'share_type',
					  $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE)
				  )
		   );
		$qb->execute();
	}

	/**
	 * Unshare a file from self as recipient.
	 * This may require special handling. If a user unshares a group
	 * share from their self then the original group share should still exist.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $recipient UserId of the recipient
	 */
	public function deleteFromSelf(IShare $share, $recipient) {
		$this->misc->log("CircleProvider: deleteFromSelf");
		$share->setPermissions(0);
		$this->move($share, $recipient, true);
	}


	/**
	 * Move a share as a recipient.
	 * This is updating the share target. Thus the mount point of the recipient.
	 * This may require special handling. If a user moves a group share
	 * the target should only be changed for them.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $recipient userId of recipient
	 *
	 * @return \OCP\Share\IShare
	 */
	public function move(IShare $share, $recipient, $unshare = false) {
		$this->misc->log("CircleProvider: move");

		// Check if there is a usergroup share
		$qb = $this->dbConnection->getQueryBuilder();
		$stmt = $qb->select('id', 'parent')
				   ->from('share')
				   ->where(
					   $qb->expr()
						  ->eq(
							  'share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE)
						  )
				   )
				   ->andWhere(
					   $qb->expr()
						  ->eq('share_with', $qb->createNamedParameter($recipient))
				   )
				   ->andWhere(
					   $qb->expr()
						  ->orX(
							  $qb->expr()
								 ->eq('parent', $qb->createNamedParameter($share->getId())),
							  $qb->expr()
								 ->eq('id', $qb->createNamedParameter($share->getId()))
						  )
				   )
				   ->andWhere(
					   $qb->expr()
						  ->orX(
							  $qb->expr()
								 ->eq('item_type', $qb->createNamedParameter('file')),
							  $qb->expr()
								 ->eq('item_type', $qb->createNamedParameter('folder'))
						  )
				   )
				   ->setMaxResults(2)
				   ->orderBy('id')
				   ->execute();

		$parentId = 0;
		while ($data = $stmt->fetch()) {
			if ($data['parent'] === $share->getId()) {
				$parentId = $data['id'];
			}
			if ($data['id'] === $share->getId()) {
				$parentId = $data['id'];
			}
		}
		$stmt->closeCursor();

		if ($parentId === 0) {
			// no parent - create one
			$qb = $this->dbConnection->getQueryBuilder();
			$qb->insert('share')
			   ->values(
				   [
					   'share_type'    => $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE),
					   'share_with'    => $qb->createNamedParameter($recipient),
					   'uid_owner'     => $qb->createNamedParameter($share->getShareOwner()),
					   'uid_initiator' => $qb->createNamedParameter($share->getSharedBy()),
					   'parent'        => $qb->createNamedParameter($share->getId()),
					   'item_type'     => $qb->createNamedParameter(
						   $share->getNode() instanceof File ? 'file' : 'folder'
					   ),
					   'item_source'   => $qb->createNamedParameter(
						   $share->getNode()
								 ->getId()
					   ),
					   'file_source'   => $qb->createNamedParameter(
						   $share->getNode()
								 ->getId()
					   ),
					   'file_target'   => $qb->createNamedParameter($share->getTarget()),
					   'permissions'   => $qb->createNamedParameter($share->getPermissions()),
					   'stime'         => $qb->createNamedParameter(
						   $share->getShareTime()
								 ->getTimestamp()
					   ),
				   ]
			   )
			   ->execute();
		} else {
			// already a parent - update
			$qb = $this->dbConnection->getQueryBuilder();
			$qb->update('share');

			if ($unshare === true) {
				$qb->set('permissions', $qb->createNamedParameter($share->getPermissions()));
			} else {
				$qb->set('file_target', $qb->createNamedParameter($share->getTarget()));
			}

			$qb->where(
				$qb->expr()
				   ->eq('id', $qb->createNamedParameter($parentId))
			);

			if ($unshare !== true) {
				$qb->andWhere(
					$qb->expr()
					   ->gt('permissions', $qb->createNamedParameter(0))
				);
			}

			$qb->execute();
		}

		return $share;

	}

	/**
	 * Get all shares by the given user in a folder
	 *
	 * @param string $userId
	 * @param Folder $node
	 * @param bool $reshares Also get the shares where $user is the owner instead of just the
	 *     shares where $user is the initiator
	 *
	 * @return \OCP\Share\IShare[]
	 */
	public function getSharesInFolder($userId, Folder $node, $reshares) {
		$this->misc->log("CircleProvider: getSharesInFolder");

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select(
			's.id', 's.share_type', 's.share_with', 's.uid_owner', 's.uid_initiator', 's.parent',
			's.item_type', 's.item_source', 's.item_target', 's.file_source', 's.file_target',
			's.permissions', 's.stime', 's.accepted', 's.expiration', 's.token', 's.mail_send'
		)
		   ->from('share', 's')
		   ->from(MembersMapper::TABLENAME, 'm')
		   ->andWhere(
			   $qb->expr()
				  ->orX(
					  $qb->expr()
						 ->eq('s.item_type', $qb->createNamedParameter('file')),
					  $qb->expr()
						 ->eq('s.item_type', $qb->createNamedParameter('folder'))
				  )
		   );

		$qb->andWhere(
			$qb->expr()
			   ->eq('s.share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE))
		)
		   ->andWhere(
			   $qb->expr()
				  ->eq('s.share_with', 'm.circle_id')
		   )
		   ->andWhere(
			   $qb->expr()
				  ->eq('m.user_id', $qb->createNamedParameter($userId))
		   )
		   ->andWhere(
			   $qb->expr()
				  ->gte('m.level', $qb->createNamedParameter(Member::LEVEL_MEMBER))
		   );

//		$qb->andWhere(
//			$qb->expr()
//			->orX(
//				$qb->expr()
//				   ->eq('s.uid_owner', $qb->createNamedParameter($userId)),
//				$qb->expr()
//				   ->eq('s.item_type', $qb->createNamedParameter('folder'))
//			)
//		);

		/**
		 * Reshares for this user are shares where they are the owner.
		 */
		if ($reshares === false) {
			$qb->andWhere(
				$qb->expr()
				   ->eq('uid_initiator', $qb->createNamedParameter($userId))
			);
		} else {
			$qb->andWhere(
				$qb->expr()
				   ->orX(
					   $qb->expr()
						  ->eq('uid_owner', $qb->createNamedParameter($userId)),
					   $qb->expr()
						  ->eq('uid_initiator', $qb->createNamedParameter($userId))
				   )
			);
		}
//
//		$qb->innerJoin('s', 'filecache', 'f', 's.file_source = f.fileid');
//		$qb->andWhere(
//			$qb->expr()
//			   ->eq('f.parent', $qb->createNamedParameter($node->getId()))
//		);

		$qb->orderBy('id');

//		$this->misc->log('sql: ' . $qb->getSQL());
		$cursor = $qb->execute();
		$shares = [];
		while ($data = $cursor->fetch()) {
			$shares[$data['file_source']][] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * Get all shares by the given user
	 *
	 * @param string $userId
	 * @param int $shareType
	 * @param Node|null $node
	 * @param bool $reshares Also get the shares where $user is the owner instead of just the
	 *     shares where $user is the initiator
	 * @param int $limit The maximum number of shares to be returned, -1 for all shares
	 * @param int $offset
	 *
	 * @return \OCP\Share\IShare[]
	 */
	public function getSharesBy($userId, $shareType, $node, $reshares, $limit, $offset) {
		$this->misc->log("CircleProvider: getSharesBy");
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select(
			's.id', 's.share_type', 's.share_with', 's.uid_owner', 's.uid_initiator', 's.parent',
			's.item_type', 's.item_source', 's.item_target', 's.file_source', 's.file_target',
			's.permissions', 's.stime', 's.accepted', 's.expiration', 's.token', 's.mail_send',
			'c.type AS circle_type', 'c.name AS circle_name'
		)
		   ->from('share', 's')
		   ->from(CirclesMapper::TABLENAME, 'c');

		$qb->andWhere(
			$qb->expr()
			   ->eq('s.share_with', 'c.id')
		);

		$qb->andWhere(
			$qb->expr()
			   ->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE))
		);

		/**
		 * Reshares for this user are shares where they are the owner.
		 */
		if ($reshares === false) {
			$qb->andWhere(
				$qb->expr()
				   ->eq('uid_initiator', $qb->createNamedParameter($userId))
			);
		} else {
			$qb->andWhere(
				$qb->expr()
				   ->orX(
					   $qb->expr()
						  ->eq('uid_owner', $qb->createNamedParameter($userId)),
					   $qb->expr()
						  ->eq('uid_initiator', $qb->createNamedParameter($userId))
				   )
			);
		}

		if ($node !== null) {
			$qb->andWhere(
				$qb->expr()
				   ->eq('file_source', $qb->createNamedParameter($node->getId()))
			);
		}

		if ($node !== null) {
			$qb->andWhere(
				$qb->expr()
				   ->eq('s.file_source', $qb->createNamedParameter($node->getId()))
			);
		}

		if ($limit !== -1) {
			$qb->setMaxResults($limit);
		}

		$qb->setFirstResult($offset);
		$qb->orderBy('s.id');

		$cursor = $qb->execute();
		$shares = [];
		while ($data = $cursor->fetch()) {

			$data['share_with'] =
				sprintf(
					'%s (%s)', $data['circle_name'], Circle::TypeLongString($data['circle_type'])
				);
			$shares[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * Get share by id
	 *
	 * @param int $id
	 * @param string|null $recipientId
	 *
	 * @return \OCP\Share\IShare
	 * @throws ShareNotFound
	 */
	public function getShareById($id, $recipientId = null) {
		$this->misc->log("CircleProvider: getShareById");

		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select('*')
		   ->from('share')
		   ->where(
			   $qb->expr()
				  ->eq('id', $qb->createNamedParameter($id))
		   )
		   ->andWhere(
			   $qb->expr()
				  ->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE))
		   );

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ShareNotFound();
		}

		try {
			$share = $this->createShareObject($data);
		} catch (InvalidShare $e) {
			throw new ShareNotFound();
		}

		return $share;
	}


	/**
	 * Get shares for a given path
	 *
	 * @param Node $path
	 *
	 * @return \OCP\Share\IShare[]
	 */
	public function getSharesByPath(Node $path) {
		$this->misc->log("CircleProvider: getSharesByPath");

		// TODO: Implement getSharesByPath() method.
		return null;
	}

	/**
	 * Get shared with the given user
	 *
	 * @param string $userId get shares where this user is the recipient
	 * @param int $shareType
	 * @param Node|null $node
	 * @param int $limit The max number of entries returned, -1 for all
	 * @param int $offset
	 *
	 * @return \OCP\Share\IShare[]
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset) {
		$this->misc->log("CircleProvider: getSharedWith");

		/** @var IShare[] $shares */
		$shares = [];

		//Get shares directly with this user
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select(
			's.*',
			'f.fileid', 'f.path', 'f.permissions AS f_permissions', 'f.storage', 'f.path_hash',
			'f.parent AS f_parent', 'f.name', 'f.mimetype', 'f.mimepart', 'f.size', 'f.mtime',
			'f.storage_mtime', 'f.encrypted', 'f.unencrypted_size', 'f.etag', 'f.checksum',
			's2.id AS parent_id', 's2.file_target AS parent_target',
			's2.permissions AS parent_perms'
		)
		   ->selectAlias('st.id', 'storage_string_id')
		   ->from('share', 's')
		   ->from('circles_members', 'm')
		   ->leftJoin(
			   's', 'filecache', 'f', $qb->expr()
										 ->eq('s.file_source', 'f.fileid')
		   )
		   ->leftJoin(
			   'f', 'storages', 'st', $qb->expr()
										 ->eq('f.storage', 'st.numeric_id')
		   )
		   ->leftJoin(
			   's', 'share', 's2',
			   $qb->expr()
				  ->andX(
					  $qb->expr()
						 ->eq('s.id', 's2.parent'), $qb->expr()
													   ->eq(
														   's2.share_with',
														   $qb->createNamedParameter(
															   $userId
														   )
													   )
				  )
		   );

		$qb->where(
			$qb->expr()
			   ->eq('s.share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE))
		)
		   ->andWhere(
			   $qb->expr()
				  ->eq('m.circle_id', 's.share_with')
		   )
		   ->andWhere(
			   $qb->expr()
				  ->eq('m.user_id', $qb->createNamedParameter($userId))
		   )
		   ->andWhere(
			   $qb->expr()
				  ->gte('m.level', $qb->createNamedParameter(Member::LEVEL_MEMBER))
		   )
		   ->andWhere(
			   $qb->expr()
				  ->orX(
					  $qb->expr()
						 ->eq('s.item_type', $qb->createNamedParameter('file')),
					  $qb->expr()
						 ->eq('s.item_type', $qb->createNamedParameter('folder'))
				  )
		   );

		// Set limit and offset
		if ($limit !== -1) {
			$qb->setMaxResults($limit);
		}
		$qb->setFirstResult($offset);

		$cursor = $qb->execute();

		while ($data = $cursor->fetch()) {
			if ($data['parent_id'] > 0) {
				if ($data['parent_perms'] === '0') {
					continue;
				}

				$data['file_target'] = $data['parent_target'];
			}
			if ($this->isAccessibleResult($data)) {
				$shares[] = $this->createShareObject($data);
			}
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * Get a share by token
	 *
	 * @param string $token
	 *
	 * @return \OCP\Share\IShare
	 * @throws ShareNotFound
	 */
	public function getShareByToken($token) {
		return null;
	}


	public function getChildren(\OCP\Share\IShare $parent) {
		$children = [];

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
		   ->from('share')
		   ->where(
			   $qb->expr()
				  ->eq('parent', $qb->createNamedParameter($parent->getId()))
		   )
		   ->andWhere(
			   $qb->expr()
				  ->in(
					  'share_type',
					  $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE)
				  )
		   )
		   ->andWhere(
			   $qb->expr()
				  ->orX(
					  $qb->expr()
						 ->eq('item_type', $qb->createNamedParameter('file')),
					  $qb->expr()
						 ->eq('item_type', $qb->createNamedParameter('folder'))
				  )
		   )
		   ->orderBy('id');

		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$children[] = $this->createShare($data);
		}
		$cursor->closeCursor();

		return $children;
	}


	/**
	 * A user is deleted from the system
	 * So clean up the relevant shares.
	 *
	 * @param string $uid
	 * @param int $shareType
	 */
	public function userDeleted($uid, $shareType) {
		$this->misc->log("CircleProvider: userDeleted");
		// TODO: Implement userDeleted() method.
	}

	/**
	 * A group is deleted from the system.
	 * We have to clean up all shares to this group.
	 * Providers not handling group shares should just return
	 *
	 * @param string $gid
	 */
	public function groupDeleted($gid) {
		return;
	}

	/**
	 * A user is deleted from a group
	 * We have to clean up all the related user specific group shares
	 * Providers not handling group shares should just return
	 *
	 * @param string $uid
	 * @param string $gid
	 */
	public function userDeletedFromGroup($uid, $gid) {
		return;
	}


	/**
	 * add share to the database and return the ID
	 *
	 * @param int $itemSource
	 * @param string $itemType
	 * @param string $shareWith
	 * @param string $sharedBy
	 * @param string $uidOwner
	 * @param int $permissions
	 * @param string $token
	 *
	 * @return int
	 */
	private function addShareToDB(
		$itemSource, $itemType, $shareWith, $sharedBy, $fileTarget, $uidOwner, $permissions, $token
	) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert('share')
		   ->setValue('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE))
		   ->setValue('item_type', $qb->createNamedParameter($itemType))
		   ->setValue('item_source', $qb->createNamedParameter($itemSource))
		   ->setValue('file_source', $qb->createNamedParameter($itemSource))
		   ->setValue('file_target', $qb->createNamedParameter($fileTarget))
		   ->setValue('share_with', $qb->createNamedParameter($shareWith))
		   ->setValue('uid_owner', $qb->createNamedParameter($uidOwner))
		   ->setValue('uid_initiator', $qb->createNamedParameter($sharedBy))
		   ->setValue('permissions', $qb->createNamedParameter($permissions))
		   ->setValue('token', $qb->createNamedParameter($token))
		   ->setValue('stime', $qb->createNamedParameter(time()));
		$qb->execute();
		$id = $qb->getLastInsertId();

		return (int)$id;
	}


	/**
	 * get database row of a give share
	 *
	 * @param $id
	 *
	 * @return array
	 * @throws ShareNotFound
	 */
	private function getRawShare($id) {

		// Now fetch the inserted share and create a complete share object
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
		   ->from('share')
		   ->where(
			   $qb->expr()
				  ->eq('id', $qb->createNamedParameter($id))
		   );

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ShareNotFound;
		}

		return $data;
	}


	/**
	 * Create a share object from an database row
	 *
	 * @param array $data
	 *
	 * @return IShare
	 * @throws InvalidShare
	 * @throws ShareNotFound
	 */
	private function createShareObject($data) {

		//$this->misc->log(var_export($data, true));
		$share = new Share($this->rootFolder, $this->userManager);
		$share->setId((int)$data['id'])
			  ->setShareType((int)$data['share_type'])
			  ->setPermissions((int)$data['permissions'])
			  ->setTarget($data['file_target'])
			  ->setMailSend((bool)$data['mail_send']);

		$shareTime = new \DateTime();
		$shareTime->setTimestamp((int)$data['stime']);
		$share->setShareTime($shareTime);

		$share->setSharedWith($data['share_with']);
		$share->setSharedBy($data['uid_initiator']);
		$share->setShareOwner($data['uid_owner']);

		$share->setNodeId((int)$data['file_source']);
		$share->setNodeType($data['item_type']);

		if (isset($data['f_permissions'])) {
			$entryData = $data;
			$entryData['permissions'] = $entryData['f_permissions'];
			$entryData['parent'] = $entryData['f_parent'];
			$share->setNodeCacheEntry(
				Cache::cacheEntryFromData(
					$entryData,
					\OC::$server->getMimeTypeLoader()
				)
			);
		}

		$share->setProviderId($this->identifier());

		return $share;
	}


	/**
	 * Returns whether the given database result can be interpreted as
	 * a share with accessible file (not trashed, not deleted)
	 */
	private function isAccessibleResult($data) {
		// exclude shares leading to deleted file entries
		if ($data['fileid'] === null) {
			return false;
		}

		// exclude shares leading to trashbin on home storages
		$pathSections = explode('/', $data['path'], 2);
		// FIXME: would not detect rare md5'd home storage case properly
		if ($pathSections[0] !== 'files'
			&& explode(':', $data['storage_string_id'], 2)[0] === 'home'
		) {
			return false;
		}

		return true;
	}
}
