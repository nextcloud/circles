<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


namespace OCA\Circles;


use OC\Files\Cache\Cache;
use OC\Share20\Exception\InvalidShare;
use OC\Share20\Share;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleProviderRequest;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;
use OCP\Share\IShareProvider;
use OCP\Util;


class ShareByCircleProvider extends CircleProviderRequest implements IShareProvider {

	/** @var ILogger */
	private $logger;

	/** @var ISecureRandom */
	private $secureRandom;

	/** @var IUserManager */
	private $userManager;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MembersRequest */
	private $membersRequest;


	/**
	 * DefaultShareProvider constructor.
	 *
	 * @param IDBConnection $connection
	 * @param ISecureRandom $secureRandom
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param IL10N $l10n
	 * @param ILogger $logger
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		IDBConnection $connection, ISecureRandom $secureRandom, IUserManager $userManager,
		IRootFolder $rootFolder, IL10N $l10n, ILogger $logger, IURLGenerator $urlGenerator
	) {
		$app = new Application();
		$container = $app->getContainer();
		$configService = $container->query(ConfigService::class);
		$miscService = $container->query(MiscService::class);

		parent::__construct($l10n, $connection, $configService, $miscService);

		$this->secureRandom = $secureRandom;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->circlesRequest = $container->query(CirclesRequest::class);
		$this->membersRequest = $container->query(MembersRequest::class);
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
		$circle = null;
		$shareId = null;
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

			$share->setToken(substr(bin2hex(openssl_random_pseudo_bytes(24)), 1, 15));
			$shareId = $this->createShare($share);

			$circle =
				$this->circlesRequest->getCircle($share->getSharedWith(), $share->getSharedby());
			$circle->getHigherViewer()
				   ->hasToBeMember();

			Circles::shareToCircle(
				$circle->getUniqueId(), 'files', '',
				['id' => $shareId, 'share' => $this->shareObjectToArray($share)],
				'\OCA\Circles\Circles\FileSharingBroadcaster'
			);

			if ($this->configService->isAuditEnabled()){
				Util::emitHook('OCP\Share', 'post_share',['shareWith'=> $circle->getName(),'fileTarget'=> $share->getTarget()]);
			}			

			return $this->getShareById($shareId);
		} catch (\Exception $e) {
			if ($this->getShareById($shareId) && $this->configService->isAuditEnabled()){
				Util::emitHook('OCP\Share', 'post_share',['shareWith'=> $circle->getName(),'fileTarget'=> $share->getTarget()]);
			}			
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

		$app = new Application();
		$container = $app->getContainer();
		$circle = $container->query(CirclesService::class)->detailsCircle($share->getSharedWith());

		if ($this->configService->isAuditEnabled()){
			Util::emitHook('OCP\Share', 'post_unshare',[
				'fileTarget'=> $share->getTarget(),
				'shareType' => $share->getShareType(),
				'shareWith'=> $circle->getName()
			]);
		}
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

		try {
			$shareWith = $this->circlesRequest->getCircle($share->getSharedWith(),$userId)->getName();
		} catch (\Exception $e) {
			$shareWith = $share->getSharedWith();
		}

		if ($this->configService->isAuditEnabled()){
			Util::emitHook('OCP\Share', 'post_unshare',[
				'fileTarget'=> $share->getTarget(),
				'shareType' => $share->getShareType(),
				'shareWith'=> $shareWith
			]);
		}
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
				$this->miscService->getDisplayName($data['circle_owner'])
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
		$qb = $this->getBaseSelectSql();
		$this->limitToFiles($qb, [$path->getId()]);
		$cursor = $qb->execute();

		$shares = [];
		while ($data = $cursor->fetch()) {
			$shares[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $shares;
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
	 * @return IShare[]
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset) {

		$shares = $this->getSharedWithCircleMembers($userId, $shareType, $node, $limit, $offset);

		return $shares;
	}


	/**
	 * @param string $userId
	 * @param $shareType
	 * @param Node $node
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return IShare[]
	 */
	private function getSharedWithCircleMembers($userId, $shareType, $node, $limit, $offset) {

		$qb = $this->getCompleteSelectSql();
		$this->linkToFileCache($qb, $userId);
		$this->limitToPage($qb, $limit, $offset);

		if ($node !== null) {
			$this->limitToFiles($qb, [$node->getId()]);
		}

		$this->linkToMember($qb, $userId, $this->configService->isLinkedGroupsAllowed());

		$this->leftJoinShareInitiator($qb);
		$cursor = $qb->execute();

		$shares = [];
		while ($data = $cursor->fetch()) {

			if ($data['initiator_circle_level'] < Member::LEVEL_MEMBER
				&& ($data['initiator_group_level'] < Member::LEVEL_MEMBER
					|| !$this->configService->isLinkedGroupsAllowed())
			) {
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
	 * Get a share by token
	 *
	 * @param string $token
	 *
	 * @return IShare
	 * @throws ShareNotFound
	 * @deprecated - use local querybuilder lib instead
	 */
	public function getShareByToken($token) {
		$qb = $this->dbConnection->getQueryBuilder();

		$cursor = $qb->select('*')
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
							->eq('token', $qb->createNamedParameter($token))
					 )
					 ->execute();

		$data = $cursor->fetch();

		if ($data === false) {
			throw new ShareNotFound('Share not found', $this->l10n->t('Could not find share'));
		}

		try {
			$share = $this->createShareObject($data);
		} catch (InvalidShare $e) {
			throw new ShareNotFound('Share not found', $this->l10n->t('Could not find share'));
		}

		return $share;
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
	 * @return IShare
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
	private function assignShareObjectPropertiesFromParent(IShare &$share, $data) {
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
						  $this->miscService->getDisplayName($data['circle_owner'])
					  )
				  );
		}
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
		$message_t = $this->l10n->t($message, array($share_src));
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
	 * @param IQueryBuilder $qb
	 *
	 * @return array
	 */
	private function parseAccessListResult(IQueryBuilder $qb) {

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


	/**
	 * @param IShare $share
	 *
	 * @return array
	 */
	private function shareObjectToArray(IShare $share) {
		return [
			'sharedWith'  => $share->getSharedWith(),
			'sharedBy'    => $share->getSharedBy(),
			'nodeId'      => $share->getNodeId(),
			'shareOwner'  => $share->getShareOwner(),
			'permissions' => $share->getPermissions(),
			'token'       => $share->getToken(),
			'password'    => $share->getPassword()
		];
	}
}
