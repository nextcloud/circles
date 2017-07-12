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


use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleProviderRequestBuilder;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
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

class ShareByCircleProvider extends CircleProviderRequestBuilder implements IShareProvider {

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

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var MiscService */
	private $miscService;


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
		IDBConnection $connection, ISecureRandom $secureRandom, IUserManager $userManager,
		IRootFolder $rootFolder, IL10N $l, ILogger $logger, IURLGenerator $urlGenerator
	) {
		$this->dbConnection = $connection;
		$this->secureRandom = $secureRandom;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->l = $l;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;

		$app = new Application();
		$this->circlesRequest = $app->getContainer()
									->query('CirclesRequest');
		$this->membersRequest = $app->getContainer()
									->query('MembersRequest');
		$this->miscService = $app->getContainer()
								 ->query('MiscService');
	}


	/**
	 * Return the identifier of this provider.
	 *
	 * @return string
	 */
	public function identifier() {
		return 'ocCircleShare';
	}


	/**
	 * Create a share if it does not exist already.
	 *
	 * @param IShare $share
	 *
	 * @return IShare The share object
	 * @throws \Exception
	 */
	public function create(IShare $share) {
		try {
			$nodeId = $share->getNode()
							->getId();

			$qb = $this->findShareParentSql($nodeId, $share->getSharedWith());
			$exists = $qb->execute();
			$data = $exists->fetch();
			$exists->closeCursor();

			if ($data !== false) {
				throw $this->errorShareAlreadyExist($share);
			}

			$circle =
				$this->circlesRequest->getCircle($share->getSharedWith(), $share->getSharedby());
			$circle->getHigherViewer()
				   ->hasToBeMember();

			$shareId = $this->createShare($share);

			return $this->getShareById($shareId);
		} catch (\Exception $e) {
			throw $e;
		}
	}


	/**
	 * Update a share
	 * permissions right, owner and initiator
	 *
	 * @param IShare $share
	 *
	 * @return IShare The share object
	 */
	public function update(IShare $share) {

		$qb = $this->getBaseUpdateSql();
		$this->limitToShare($qb, $share->getId());
		$qb->set('permissions', $qb->createNamedParameter($share->getPermissions()))
		   ->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
		   ->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()));
		$qb->execute();

		return $share;
	}


	/**
	 * Delete a share, and it's children
	 *
	 * @param IShare $share
	 */
	public function delete(IShare $share) {

		$qb = $this->getBaseDeleteSql();
		$this->limitToShareAndChildren($qb, $share->getId());

		$qb->execute();
	}


	/**
	 * Unshare a file from self as recipient.
	 * Because every circles share are group shares, we will set permissions to 0
	 *
	 * @param IShare $share
	 * @param string $userId
	 */
	public function deleteFromSelf(IShare $share, $userId) {
		$childId = $this->getShareChildId($share, $userId);

		$qb = $this->getBaseUpdateSql();
		$qb->set('permissions', $qb->createNamedParameter(0));
		$this->limitToShare($qb, $childId);

		$qb->execute();
	}


	/**
	 * Move a share as a recipient.
	 *
	 * @param IShare $share
	 * @param string $userId
	 *
	 * @return IShare
	 *
	 */
	public function move(IShare $share, $userId) {

		$childId = $this->getShareChildId($share, $userId);

		$qb = $this->getBaseUpdateSql();
		$qb->set('file_target', $qb->createNamedParameter($share->getTarget()));
		$this->limitToShare($qb, $childId);
		$qb->execute();

		return $share;
	}


	/**
	 * return the child ID of a share
	 *
	 * @param IShare $share
	 * @param string $userId
	 *
	 * @return bool
	 */
	private function getShareChildId(IShare $share, $userId) {
		$qb = $this->getBaseSelectSql($share->getId());
		$this->limitToShareChildren($qb, $userId, $share->getId());

		$child = $qb->execute();
		$data = $child->fetch();
		$child->closeCursor();

		if ($data === false) {
			return $this->createShareChild($userId, $share);
		}

		return $data['id'];
	}


	/**
	 * Create a child and returns its ID
	 *
	 * @param IShare $share
	 *
	 * @return int
	 */
	private function createShare($share) {
		$qb = $this->getBaseInsertSql($share);
		$qb->execute();
		$id = $qb->getLastInsertId();

		return (int)$id;
	}


	/**
	 * Create a child and returns its ID
	 *
	 * @param string $userId
	 * @param IShare $share
	 *
	 * @return int
	 */
	private function createShareChild($userId, $share) {
		$qb = $this->getBaseInsertSql($share);

		$qb->setValue('parent', $qb->createNamedParameter($share->getId()));
		$qb->setValue('share_with', $qb->createNamedParameter($userId));
		$qb->execute();
		$id = $qb->getLastInsertId();

		return (int)$id;
	}


	/**
	 * Get all shares by the given user in a folder
	 *
	 * @param string $userId
	 * @param Folder $node
	 * @param bool $reshares Also get the shares where $user is the owner instead of just the
	 *     shares where $user is the initiator
	 *
	 * @return Share[]
	 */
	public function getSharesInFolder($userId, Folder $node, $reshares) {
		$qb = $this->getBaseSelectSql();
		$this->limitToShareOwner($qb, $userId, true);
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
	 * @param bool $reShares
	 * @param int $limit The maximum number of shares to be returned, -1 for all shares
	 * @param int $offset
	 *
	 * @return Share[]
	 */
	public function getSharesBy($userId, $shareType, $node, $reShares, $limit, $offset) {
		$qb = $this->getBaseSelectSql();
		$this->limitToShareOwner($qb, $userId, $reShares);

		if ($node !== null) {
			$this->limitToFiles($qb, $node->getId());
		}

		$this->limitToPage($qb, $limit, $offset);
		$cursor = $qb->execute();

		$shares = [];
		while ($data = $cursor->fetch()) {
			$shares[] = $this->createShareObject($this->editShareEntry($data));
		}
		$cursor->closeCursor();

		return $shares;
	}


	/**
	 * returns a better formatted string to display more information about
	 * the circle to the Sharing UI
	 *
	 * @param $data
	 *
	 * @return array<string,string>
	 */
	private function editShareEntry($data) {
		$data['share_with'] =
			sprintf(
				'%s (%s, %s)', $data['circle_name'], Circle::TypeLongString($data['circle_type']),
				\OC::$server->getUserManager()
							->get($data['circle_owner'])
							->getDisplayName()
			);

		return $data;
	}


	/**
	 * Get share by its id
	 *
	 * @param int $shareId
	 * @param string|null $recipientId
	 *
	 * @return Share
	 * @throws ShareNotFound
	 */
	public function getShareById($shareId, $recipientId = null) {
		$qb = $this->getBaseSelectSql();
		$this->limitToShare($qb, $shareId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ShareNotFound();
		}

		return $this->createShareObject($data);
	}


	/**
	 * Get shares for a given path
	 *
	 * @param Node $path
	 *
	 * @return IShare[]|null
	 */
	public function getSharesByPath(Node $path) {
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
	 * @return array|IShare[]
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset) {

		$qb = $this->getCompleteSelectSql();
		$this->linkToMember($qb, $userId);
		$this->linkToFileCache($qb, $userId);
		$this->limitToPage($qb, $limit, $offset);

		if ($node !== null) {
			$this->limitToFiles($qb, [$node->getId()]);
		}

		$this->leftJoinShareInitiator($qb);

		$cursor = $qb->execute();

		$shares = [];
		while ($data = $cursor->fetch()) {
			if ($data['initiator_level'] < Member::LEVEL_MEMBER) {
				continue;
			}

			self::editShareFromParentEntry($data);
			if (self::isAccessibleResult($data)) {
				$shares[] = $this->createShareObject($data);
			}
		}
		$cursor->closeCursor();

		return $shares;
	}


	/**
	 * @param $data
	 */
	private static function editShareFromParentEntry(&$data) {
		if ($data['parent_id'] > 0) {
			$data['permissions'] = $data['parent_perms'];
			$data['file_target'] = $data['parent_target'];
		}
	}


	/**
	 * Get a share by token
	 *
	 * @param string $token
	 *
	 * @return IShare
	 * @throws ShareNotFound
	 */
	public function getShareByToken($token) {
		return null;
	}


	/**
	 * We don't return a thing about children.
	 * The call to this function is deprecated and should be removed in next release of NC.
	 * Also, we get the children in the delete() method.
	 *
	 * @param IShare $parent
	 *
	 * @return array
	 */
	public function getChildren(IShare $parent) {
		return [];
	}


	/**
	 * A user is deleted from the system
	 * So clean up the relevant shares.
	 *
	 * @param string $uid
	 * @param int $shareType
	 */
	public function userDeleted($uid, $shareType) {
		// TODO: Implement userDeleted() method.
	}


	/**
	 * A group is deleted from the system.
	 * We handle our own groups.
	 *
	 * @param string $gid
	 */
	public function groupDeleted($gid) {
		return;
	}


	/**
	 * A user is deleted from a group.
	 * We handle our own groups.
	 *
	 * @param string $uid
	 * @param string $gid
	 */
	public function userDeletedFromGroup($uid, $gid) {
		return;
	}


	/**
	 * Create a share object
	 *
	 * @param array $data
	 *
	 * @return Share
	 */
	private function createShareObject($data) {

		$share = new Share($this->rootFolder, $this->userManager);
		$share->setId((int)$data['id'])
			  ->setPermissions((int)$data['permissions'])
			  ->setNodeType($data['item_type']);

		$share->setNodeId((int)$data['file_source'])
			  ->setTarget($data['file_target']);

		$this->assignShareObjectSharesProperties($share, $data);
		$this->assignShareObjectPropertiesFromParent($share, $data);

		$share->setProviderId($this->identifier());

		return $share;
	}


	/**
	 * @param IShare $share
	 * @param $data
	 */
	private function assignShareObjectPropertiesFromParent(& $share, $data) {
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
	}


	/**
	 * @param IShare $share
	 * @param $data
	 */
	private function assignShareObjectSharesProperties(IShare &$share, $data) {
		$shareTime = new \DateTime();
		$shareTime->setTimestamp((int)$data['stime']);

		$share->setShareTime($shareTime);
		$share->setSharedWith($data['share_with'])
			  ->setSharedBy($data['uid_initiator'])
			  ->setShareOwner($data['uid_owner'])
			  ->setShareType((int)$data['share_type']);

		if (method_exists($share, 'setSharedWithDisplayName')) {
			$share->setSharedWithAvatar(CirclesService::getCircleIcon($data['circle_type']))
				  ->setSharedWithDisplayName(
					  sprintf(
						  '%s (%s, %s)', $data['circle_name'],
						  Circle::TypeLongString($data['circle_type']),
						  \OC::$server->getUserManager()
									  ->get($data['circle_owner'])
									  ->getDisplayName()
					  )
				  );
		}
	}


	/**
	 * Returns whether the given database result can be interpreted as
	 * a share with accessible file (not trashed, not deleted)
	 *
	 * @param $data
	 *F
	 *
	 * @return bool
	 */
	private static function isAccessibleResult($data) {
		if ($data['fileid'] === null) {
			return false;
		}

		return (!(explode('/', $data['path'], 2)[0] !== 'files'
				  && explode(':', $data['storage_string_id'], 2)[0] === 'home'));
	}


	/**
	 * @param IShare $share
	 *
	 * @return \Exception
	 */
	private function errorShareAlreadyExist($share) {
		$share_src = $share->getNode()
						   ->getName();

		$message = 'Sharing %s failed, this item is already shared with this circle';
		$message_t = $this->l->t($message, array($share_src));
		$this->logger->debug(
			sprintf($message, $share_src, $share->getSharedWith()), ['app' => 'circles']
		);

		return new \Exception($message_t);
	}


	/**
	 * Get the access list to the array of provided nodes.
	 *
	 * @see IManager::getAccessList() for sample docs
	 *
	 * @param Node[] $nodes The list of nodes to get access for
	 * @param bool $currentAccess If current access is required (like for removed shares that might
	 *     get revived later)
	 *
	 * @return array
	 * @since 12
	 */
	public function getAccessList($nodes, $currentAccess) {

		$ids = [];
		foreach ($nodes as $node) {
			$ids[] = $node->getId();
		}

		$qb = $this->getAccessListBaseSelectSql();
		$this->limitToFiles($qb, $ids);

		$users = $this->parseAccessListResult($qb);

		if ($currentAccess === false) {
			$users = array_keys($users);
		}

		return ['users' => $users];
	}


	/**
	 * return array regarding getAccessList format.
	 * ie. \OC\Share20\Manager::getAccessList()
	 *
	 * @param $qb
	 *
	 * @return array
	 */
	private function parseAccessListResult($qb) {

		$cursor = $qb->execute();
		$users = [];

		while ($row = $cursor->fetch()) {
			$userId = $row['user_id'];

			if (!key_exists($userId, $users)) {
				$users[$userId] = [
					'node_id'   => $row['file_source'],
					'node_path' => $row['file_target']
				];
			}
		}
		$cursor->closeCursor();

		return $users;
	}
}
