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


namespace OCA\Circles;


use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\IRootFolder;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShareProvider;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\Share\IShare;
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

		$app = new \OCA\Circles\AppInfo\Application();
		$this->misc = $app->getContainer()
						  ->query('MiscService');

		$this->urlGenerator = $urlGenerator;
	}


	/**
	 * Return the identifier of this provider.
	 *
	 * @return string Containing only [a-zA-Z0-9]
	 * @since 9.0.0
	 */
	public function identifier() {
		return 'ocShareByCircle';
	}

	/**
	 * Create a share
	 *
	 * @param \OCP\Share\IShare $share
	 *
	 * @return \OCP\Share\IShare The share object
	 * @since 9.0.0
	 */
	public function create(IShare $share) {

		$shareWith = $share->getSharedWith();
		/*
		 * Check if file is not already shared with the remote user
		 */
		$alreadyShared = $this->getSharedWith(
			$shareWith, \OCP\Share::SHARE_TYPE_CIRCLE, $share->getNode(), 1, 0
		);
		if (!empty($alreadyShared)) {
			$message = 'Sharing %s failed, this item is already shared with %s';
			$message_t = $this->l->t(
				'Sharing %s failed, this item is already shared with %s', array(
																			$share->getNode()
																				  ->getName(),
																			$shareWith
																		)
			);
			$this->logger->debug(
				sprintf(
					$message, $share->getNode()
									->getName(), $shareWith
				), ['app' => 'circles']
			);
			throw new \Exception($message_t);
		}

		//	$shareId = $this->createMailShare($share);
		//	$this->createActivity($share);
		//	$data = $this->getRawShare($shareId);

		$data = '';

		return $this->createShareObject($data);

	}

	/**
	 * Update a share
	 *
	 * @param \OCP\Share\IShare $share
	 *
	 * @return \OCP\Share\IShare The share object
	 * @since 9.0.0
	 */
	public function update(IShare $share) {
		// TODO: Implement update() method.
	}

	/**
	 * Delete a share
	 *
	 * @param \OCP\Share\IShare $share
	 *
	 * @since 9.0.0
	 */
	public function delete(IShare $share) {
		// TODO: Implement delete() method.
	}

	/**
	 * Unshare a file from self as recipient.
	 * This may require special handling. If a user unshares a group
	 * share from their self then the original group share should still exist.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $recipient UserId of the recipient
	 *
	 * @since 9.0.0
	 */
	public function deleteFromSelf(IShare $share, $recipient) {
		// TODO: Implement deleteFromSelf() method.
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
	 * @since 9.0.0
	 */
	public function move(IShare $share, $recipient) {
		// TODO: Implement move() method.
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
	 * @since 11.0.0
	 */
	public function getSharesInFolder($userId, Folder $node, $reshares) {
		// TODO: Implement getSharesInFolder() method.
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
	 * @since 9.0.0
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
			   ->eq('s.share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE))
		);

		/**
		 * Reshares for this user are shares where they are the owner.
		 */
		if ($reshares === false) {
			//Special case for old shares created via the web UI
			$or1 = $qb->expr()
					  ->andX(
						  $qb->expr()
							 ->eq('s.uid_owner', $qb->createNamedParameter($userId)),
						  $qb->expr()
							 ->isNull('s.uid_initiator')
					  );

			$qb->andWhere(
				$qb->expr()
				   ->orX(
					   $qb->expr()
						  ->eq('s.uid_initiator', $qb->createNamedParameter($userId)),
					   $or1
				   )
			);
		} else {
			$qb->andWhere(
				$qb->expr()
				   ->orX(
					   $qb->expr()
						  ->eq('s.uid_owner', $qb->createNamedParameter($userId)),
					   $qb->expr()
						  ->eq('s.uid_initiator', $qb->createNamedParameter($userId))
				   )
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
			$this->misc->log("___" . var_export($data, true));
			$data['share_with'] =
				sprintf(
					'%s (%s)', $data['circle_name'], Circle::TypeLongSring($data['circle_type'])
				);
//			$data['share_with_displayname'] =
//				sprintf('%s (%s)', $data['name'], Circle::TypeLongSring($data['type']));
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
	 * @since 9.0.0
	 */
	public function getShareById($id, $recipientId = null) {
		// TODO: Implement getShareById() method.
	}

	/**
	 * Get shares for a given path
	 *
	 * @param Node $path
	 *
	 * @return \OCP\Share\IShare[]
	 * @since 9.0.0
	 */
	public function getSharesByPath(Node $path) {
		// TODO: Implement getSharesByPath() method.
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
	 * @since 9.0.0
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset) {
		// TODO: Implement getSharedWith() method.
	}

	/**
	 * Get a share by token
	 *
	 * @param string $token
	 *
	 * @return \OCP\Share\IShare
	 * @throws ShareNotFound
	 * @since 9.0.0
	 */
	public function getShareByToken($token) {
		// TODO: Implement getShareByToken() method.
	}

	/**
	 * A user is deleted from the system
	 * So clean up the relevant shares.
	 *
	 * @param string $uid
	 * @param int $shareType
	 *
	 * @since 9.1.0
	 */
	public function userDeleted($uid, $shareType) {
		// TODO: Implement userDeleted() method.
	}

	/**
	 * A group is deleted from the system.
	 * We have to clean up all shares to this group.
	 * Providers not handling group shares should just return
	 *
	 * @param string $gid
	 *
	 * @since 9.1.0
	 */
	public function groupDeleted($gid) {
		// TODO: Implement groupDeleted() method.
	}

	/**
	 * A user is deleted from a group
	 * We have to clean up all the related user specific group shares
	 * Providers not handling group shares should just return
	 *
	 * @param string $uid
	 * @param string $gid
	 *
	 * @since 9.1.0
	 */
	public function userDeletedFromGroup($uid, $gid) {
		// TODO: Implement userDeletedFromGroup() method.
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
		$itemSource, $itemType, $shareWith, $sharedBy, $uidOwner, $permissions, $token
	) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert('share')
		   ->setValue('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE))
		   ->setValue('item_type', $qb->createNamedParameter($itemType))
		   ->setValue('item_source', $qb->createNamedParameter($itemSource))
		   ->setValue('file_source', $qb->createNamedParameter($itemSource))
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
}
