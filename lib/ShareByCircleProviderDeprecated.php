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


namespace OCA\Circles;

use OCA\Circles\Tools\Model\SimpleDataStore;
use Exception;
use OC;
use OC\Files\Cache\Cache;
use OC\Share20\Exception\InvalidShare;
use OC\Share20\Share;
use OC\User\NoUserException;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleProviderRequest;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Db\TokensRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\GSUpstreamService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\TimezoneService;
use OCP\AppFramework\QueryException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;
use OCP\Share\IShareProvider;

/**
 * Class ShareByCircleProvider
 *
 * @package OCA\Circles
 */
class ShareByCircleProviderDeprecated extends CircleProviderRequest implements IShareProvider {


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

	/** @var MembersService */
	private $membersService;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;

	/** @var TokensRequest */
	private $tokensRequest;

	/** @var GSUpstreamService */
	private $gsUpstreamService;


	/**
	 * ShareByCircleProvider constructor.
	 *
	 * @param IDBConnection $connection
	 * @param ISecureRandom $secureRandom
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param IL10N $l10n
	 * @param ILogger $logger
	 * @param IURLGenerator $urlGenerator
	 *
	 * @throws QueryException
	 */
	public function __construct(
		IDBConnection $connection, ISecureRandom $secureRandom, IUserManager $userManager,
		IRootFolder $rootFolder, IL10N $l10n, ILogger $logger, IURLGenerator $urlGenerator
	) {
		// kept but should not be loaded
		exit();
		$app = \OC::$server->query(Application::class);
		$container = $app->getContainer();
		$configService = $container->query(ConfigService::class);
		$miscService = $container->query(MiscService::class);
		$timezoneService = $container->query(TimezoneService::class);

		parent::__construct($l10n, $connection, $configService, $timezoneService, $miscService);

		$this->secureRandom = $secureRandom;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->membersService = $container->query(MembersService::class);
		$this->circlesRequest = $container->query(DeprecatedCirclesRequest::class);
		$this->membersRequest = $container->query(DeprecatedMembersRequest::class);
		$this->gsUpstreamService = $container->query(GSUpstreamService::class);
		$this->tokensRequest = $container->query(TokensRequest::class);
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
	 * @throws Exception
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

			$share->setToken($this->token(15));

			$this->createShare($share);

			$circle =
				$this->circlesRequest->getCircle($share->getSharedWith(), $share->getSharedby());
			$circle->getHigherViewer()
				   ->hasToBeMember();

			Circles::shareToCircle(
				$circle->getUniqueId(), 'files', '',
				['id' => $share->getId(), 'share' => $this->shareObjectToArray($share)],
				'\OCA\Circles\Circles\FileSharingBroadcaster'
			);

			return $this->getShareById($share->getId());
		} catch (Exception $e) {
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
	 *
	 * @throws Exception
	 */
	public function delete(IShare $share) {
		$qb = $this->getBaseDeleteSql();
		$this->limitToShareAndChildren($qb, $share->getId());
		$qb->execute();

		try {
			$circle = $this->circlesRequest->forceGetCircle($share->getSharedWith());
			$event = new GSEvent(GSEvent::FILE_UNSHARE, true);
			$event->setDeprecatedCircle($circle);

			$store = new SimpleDataStore();
			$store->sArray(
				'share', [
					'id' => $share->getId()
				]
			);
			$event->setData($store);

			$this->gsUpstreamService->newEvent($event);
		} catch (CircleDoesNotExistException $e) {
		}

		$this->tokensRequest->removeTokenByShareId($share->getId());
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
	 * @throws NotFoundException
	 */
	private function createShare($share) {
		$this->miscService->log(
			'Creating share (1/4) - type: ' . $share->getShareType() . ' - token: '
			. $share->getToken() . ' - type: ' . $share->getShareType() . ' - with: '
			. $share->getSharedWith() . ' - permissions: ' . $share->getPermissions(), 0
		);

		$qb = $this->getBaseInsertSql($share);
		$this->miscService->log('Share creation (2/4) : ' . json_encode($qb->getSQL()), 0);

		$result = $qb->execute();
		$this->miscService->log('Share creation result (3/4) : ' . json_encode($result), 0);

		$id = $qb->getLastInsertId();
		$this->miscService->log('Share created ID (4/4) : ' . $id, 0);

		try {
			$share->setId($id);
		} catch (IllegalIDChangeException $e) {
		}
	}


	/**
	 * Create a child and returns its ID
	 *
	 * @param string $userId
	 * @param IShare $share
	 *
	 * @return int
	 * @throws NotFoundException
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
	 * @param bool $shallow Whether the method should stop at the first level, or look into sub-folders.
	 *
	 * @return Share[]
	 */
	public function getSharesInFolder($userId, Folder $node, $reshares, $shallow = true) {
		\OC::$server->getLogger()->log(3, 'deprecated>getSharesInFolder');
		return [];
//
//		$qb = $this->getBaseSelectSql();
//		$this->limitToShareOwner($qb, $userId, true);
//		$cursor = $qb->execute();
//
//		$shares = [];
//		while ($data = $cursor->fetch()) {
//			$shares[$data['file_source']][] = $this->createShareObject($data);
//		}
//		$cursor->closeCursor();
//
//		return $shares;
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
	 * @throws NoUserException
	 * @throws NotFoundException
	 * @throws InvalidPathException
	 */
	public function getSharesBy($userId, $shareType, $node, $reShares, $limit, $offset) {
		$qb = $this->getBaseSelectSql();

		if ($node === null) {
			$this->limitToShareOwner($qb, $userId, $reShares);
		} else {
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
	 * @throws NoUserException
	 */
	private function editShareEntry($data) {
		$name = $data['circle_name'];
		if ($data['circle_alt_name'] !== '') {
			$name = $data['circle_alt_name'];
		}

		$data['share_with'] =
			sprintf(
				'%s (%s, %s) [%s]', $name,
				$this->l10n->t(DeprecatedCircle::TypeLongString($data['circle_type'])),
				$this->miscService->getDisplayName($data['circle_owner'], true), $data['share_with']
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
		\OC::$server->getLogger()->log(3, 'deprecated>getShareById');
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
	 * @throws InvalidPathException
	 * @throws NotFoundException *
	 */
	public function getSharesByPath(Node $path) {
		\OC::$server->getLogger()->log(3, 'deprecated>getSharesByPath');
		return [];
//		$qb = $this->getBaseSelectSql();
//		$this->limitToFiles($qb, [$path->getId()]);
//
//		$cursor = $qb->execute();
//
//		$shares = [];
//		while ($data = $cursor->fetch()) {
//			$shares[] = $this->createShareObject($data);
//		}
//		$cursor->closeCursor();
//
//		return $shares;
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
	 * @throws Exceptions\GSStatusException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset) {
//		\OC::$server->getLogger()->log(3, 'deprecated>getSharedWith');
		return $this->getSharedWithCircleMembers($userId, $shareType, $node, $limit, $offset);
	}


	/**
	 * @param string $userId
	 * @param $shareType
	 * @param Node $node
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return IShare[]
	 * @throws Exceptions\GSStatusException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	private function getSharedWithCircleMembers($userId, $shareType, $node, $limit, $offset) {
		$qb = $this->getCompleteSelectSql();
		$this->linkToFileCache($qb, $userId);
		$this->limitToPage($qb, $limit, $offset);

		if ($node !== null) {
			$this->limitToFiles($qb, [$node->getId()]);
		}

		$this->linkToMember($qb, $userId, false, 'c');

		$shares = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
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
		\OC::$server->getLogger()->log(3, 'deprecated>getShareByToken');
		$qb = $this->dbConnection->getQueryBuilder();

		$this->miscService->log("Opening share by token '#" . $token . "'", 0);

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
			$this->miscService->log('data is false - checking personal token', 0);
			try {
				$data = $this->getShareByPersonalToken($token);
			} catch (Exception $e) {
				$this->miscService->log("Share '#" . $token . "' not found.", 0);
				throw new ShareNotFound('Share not found', $this->l10n->t('Could not find share'));
			}
		}

		try {
			$share = $this->createShareObject($data);
		} catch (InvalidShare $e) {
			$this->miscService->log(
				"Share Object '#" . $token . "' not created. " . json_encode($data), 0
			);
			throw new ShareNotFound('Share not found', $this->l10n->t('Could not find share'));
		}

		$share->setStatus(Ishare::STATUS_ACCEPTED);

		return $share;
	}


	/**
	 * @param string $token
	 *
	 * @return array
	 * @throws ShareNotFound
	 */
	private function getShareByPersonalToken($token) {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb = $qb->select('s.*')
				 ->selectAlias('ct.password', 'personal_password')
				 ->selectAlias('ct.circle_id', 'personal_circle_id')
				 ->selectAlias('ct.user_id', 'personal_user_id')
				 ->selectAlias('ct.member_id', 'personal_member_id')
				 ->from('share', 's')
				 ->from('circle_tokens', 'ct')
				 ->where(
					 $qb->expr()
						->eq(
							's.share_type',
							$qb->createNamedParameter(\OCP\Share::SHARE_TYPE_CIRCLE)
						)
				 )
				 ->andWhere(
					 $qb->expr()
						->eq('ct.token', $qb->createNamedParameter($token))
				 )
				 ->andWhere(
					 $qb->expr()
						->eq('ct.share_id', 's.id')
				 );
		$cursor = $qb->execute();

		$data = $cursor->fetch();
		if ($data === false) {
			throw new ShareNotFound('personal check not found');
		}

		$member = null;
		try {
			$member = $this->membersService->getMemberById($data['personal_member_id']);
			if (!$member->isLevel(DeprecatedMember::LEVEL_MEMBER)) {
				throw new Exception();
			}
		} catch (Exception $e) {
			throw new ShareNotFound('invalid token');
		}

		return $data;
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
	 * @throws IllegalIDChangeException
	 */
	private function createShareObject($data) {
		$share = new Share($this->rootFolder, $this->userManager);
		$share->setId((int)$data['id'])
			  ->setPermissions((int)$data['permissions'])
			  ->setNodeType($data['item_type']);

		if (($password = $this->get('personal_password', $data, '')) !== '') {
			$share->setPassword($this->get('personal_password', $data, ''));
		} elseif (($password = $this->get('password', $data, '')) !== '') {
			$share->setPassword($this->get('password', $data, ''));
		}

		$share->setNodeId((int)$data['file_source'])
			  ->setTarget($data['file_target']);

		$this->assignShareObjectSharesProperties($share, $data);
		$this->assignShareObjectPropertiesFromParent($share, $data);

		$share->setProviderId($this->identifier());
		$share->setStatus(Ishare::STATUS_ACCEPTED);

		return $share;
	}


	/**
	 * @param IShare $share
	 * @param $data
	 */
	private function assignShareObjectPropertiesFromParent(IShare $share, $data) {
		if (isset($data['f_permissions'])) {
			$entryData = $data;
			$entryData['permissions'] = $entryData['f_permissions'];
			$entryData['parent'] = $entryData['f_parent'];
			$share->setNodeCacheEntry(
				Cache::cacheEntryFromData(
					$entryData,
					OC::$server->getMimeTypeLoader()
				)
			);
		}
	}


	/**
	 * @param IShare $share
	 * @param $data
	 *
	 * @throws NoUserException
	 */
	private function assignShareObjectSharesProperties(IShare $share, $data) {
		$shareTime = new \DateTime();
		$shareTime->setTimestamp((int)$data['stime']);

		$share->setShareTime($shareTime);
		$share->setSharedWith($data['share_with'])
			  ->setSharedBy($data['uid_initiator'])
			  ->setShareOwner($data['uid_owner'])
			  ->setShareType((int)$data['share_type']);

		if (array_key_exists('circle_type', $data)
			&& method_exists($share, 'setSharedWithDisplayName')) {
			$name = $data['circle_name'];
			if ($data['circle_alt_name'] !== '') {
				$name = $data['circle_alt_name'];
			}

			$share->setSharedWithAvatar(CirclesService::getCircleIcon($data['circle_type']))
				  ->setSharedWithDisplayName(
					  sprintf(
						  '%s (%s, %s)', $name,
						  $this->l10n->t(DeprecatedCircle::TypeLongString($data['circle_type'])),
						  $this->miscService->getDisplayName($data['circle_owner'], true)
					  )
				  );
		}
	}


	/**
	 * @param IShare $share
	 *
	 * @return Exception
	 * @throws NotFoundException
	 */
	private function errorShareAlreadyExist($share) {
		$share_src = $share->getNode()
						   ->getName();

		$message = 'Sharing %s failed, this item is already shared with this circle';
		$message_t = $this->l10n->t($message, [$share_src]);
		$this->logger->debug(
			sprintf($message, $share_src, $share->getSharedWith()), ['app' => 'circles']
		);

		return new Exception($message_t);
	}


	/**
	 * Get the access list to the array of provided nodes.
	 *
	 * @param Node[] $nodes The list of nodes to get access for
	 * @param bool $currentAccess If current access is required (like for removed shares that might
	 *     get revived later)
	 *
	 * @return array
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @see IManager::getAccessList() for sample docs
	 *
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
	 * Restore a share for a given recipient. The implementation could be provider independant.
	 *
	 * @param IShare $share
	 * @param string $recipient
	 *
	 * @return IShare The restored share object
	 *
	 * @since 14.0.0
	 */
	public function restore(IShare $share, string $recipient): IShare {
		return $share;
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
					'node_id' => $row['file_source'],
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
	 * @throws NotFoundException
	 */
	private function shareObjectToArray(IShare $share) {
		return [
			'id' => $share->getId(),
			'sharedWith' => $share->getSharedWith(),
			'sharedBy' => $share->getSharedBy(),
			'nodeId' => $share->getNodeId(),
			'shareOwner' => $share->getShareOwner(),
			'permissions' => $share->getPermissions(),
			'token' => $share->getToken(),
			'password' => ($share->getPassword() === null) ? '' : $share->getPassword()
		];
	}


	/**
	 * @param string $k
	 * @param array $arr
	 * @param string $default
	 *
	 * @return string
	 */
	protected function get($k, array $arr, string $default = ''): string {
		if ($arr === null) {
			return $default;
		}

		if (!array_key_exists($k, $arr)) {
			$subs = explode('.', $k, 2);
			if (sizeof($subs) > 1) {
				if (!array_key_exists($subs[0], $arr)) {
					return $default;
				}

				$r = $arr[$subs[0]];
				if (!is_array($r)) {
					return $default;
				}

				return $this->get($subs[1], $r, $default);
			} else {
				return $default;
			}
		}

		if ($arr[$k] === null || (!is_string($arr[$k]) && (!is_int($arr[$k])))) {
			return $default;
		}

		return (string)$arr[$k];
	}


	/**
	 * @inheritDoc
	 */
	public function getAllShares(): iterable {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select('*')
		   ->from('share')
		   ->where(
			   $qb->expr()
				  ->orX(
					  $qb->expr()
						 ->eq('share_type', $qb->createNamedParameter(IShare::TYPE_CIRCLE))
				  )
		   );

		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			try {
				yield $this->createShareObject($data);
			} catch (IllegalIDChangeException $e) {
			};
		}
		$cursor->closeCursor();
	}


	/**
	 * @param int $length
	 *
	 * @return string
	 */
	protected function token(int $length = 15): string {
		$chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';

		$str = '';
		$max = strlen($chars);
		for ($i = 0; $i < $length; $i++) {
			try {
				$str .= $chars[random_int(0, $max - 1)];
			} catch (Exception $e) {
			}
		}

		return $str;
	}
}
