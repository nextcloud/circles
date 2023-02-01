<?php

declare(strict_types=1);


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

use Exception;
use OC;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\ContactAddressBookNotFoundException;
use OCA\Circles\Exceptions\ContactFormatException;
use OCA\Circles\Exceptions\ContactNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\ShareWrapperNotFoundException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\Files\FileShare;
use OCA\Circles\FederatedItems\Files\FileUnshare;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Probes\DataProbe;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\ShareWrapperService;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
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
use OCP\Share\Exceptions\AlreadySharedException;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;
use OCP\Share\IShareProvider;
use Psr\Log\LoggerInterface;

/**
 * Class ShareByCircleProvider
 *
 * @package OCA\Circles
 */
class ShareByCircleProvider implements IShareProvider {
	use TArrayTools;
	use TStringTools;
	use TNCLogger;


	public const IDENTIFIER = 'ocCircleShare';


	private IUserManager $userManager;
	private IRootFolder $rootFolder;
	private IL10N $l10n;
	private LoggerInterface $logger;
	private IURLGenerator $urlGenerator;
	private ShareWrapperService $shareWrapperService;
	private FederatedUserService $federatedUserService;
	private FederatedEventService $federatedEventService;
	private CircleService $circleService;
	private EventService $eventService;

	public function __construct(
		IDBConnection $connection,
		ISecureRandom $secureRandom,
		IUserManager $userManager,
		IRootFolder $rootFolder,
		IL10N $l10n,
		ILogger $logger,
		IURLGenerator $urlGenerator
	) {
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->l10n = $l10n;
		$this->logger = OC::$server->get(LoggerInterface::class);
		$this->urlGenerator = $urlGenerator;

		$this->federatedUserService = OC::$server->get(FederatedUserService::class);
		$this->federatedEventService = OC::$server->get(FederatedEventService::class);
		$this->shareWrapperService = OC::$server->get(ShareWrapperService::class);
		$this->circleService = OC::$server->get(CircleService::class);
		$this->eventService = OC::$server->get(EventService::class);
	}


	/**
	 * @return string
	 */
	public function identifier(): string {
		return self::IDENTIFIER;
	}


	/**
	 * @param IShare $share
	 *
	 * @return IShare
	 * @throws AlreadySharedException
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws IllegalIDChangeException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidIdException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws RequestBuilderException
	 * @throws ShareNotFound
	 * @throws SingleCircleNotFoundException
	 */
	public function create(IShare $share): IShare {
		if ($share->getShareType() !== IShare::TYPE_CIRCLE) {
			return $share;
		}

		$nodeId = $share->getNode()
						->getId();

		try {
			$knowShareWrapper = $this->shareWrapperService->searchShare($share->getSharedWith(), $nodeId);
			throw new AlreadySharedException(
				$this->l10n->t('This item is already shared with this circle'),
				$knowShareWrapper->getShare($this->rootFolder, $this->userManager, $this->urlGenerator)
			);
		} catch (ShareWrapperNotFoundException $e) {
		}

		$this->federatedUserService->initCurrentUser();
		$circleProbe = new CircleProbe();
		$dataProbe = new DataProbe();
		$dataProbe->add(DataProbe::OWNER)
				  ->add(DataProbe::INITIATOR, [DataProbe::BASED_ON]);

		$circle = $this->circleService->probeCircle($share->getSharedWith(), $circleProbe, $dataProbe);
		$share->setToken($this->token(15));
		$owner = $circle->getInitiator();
		$this->shareWrapperService->save($share);

		try {
			$wrappedShare = $this->shareWrapperService->getShareById((int)$share->getId());
			$wrappedShare->setOwner($owner);
		} catch (ShareWrapperNotFoundException $e) {
			throw new ShareNotFound();
		}

		$event = new FederatedEvent(FileShare::class);
		$event->setCircle($circle);
		$event->getParams()->sObj('wrappedShare', $wrappedShare);

		$this->federatedEventService->newEvent($event);
		$this->eventService->localShareCreated($wrappedShare);

		return $wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
	}


	/**
	 * @param IShare $share
	 *
	 * @return IShare
	 * @throws IllegalIDChangeException
	 * @throws ShareWrapperNotFoundException
	 * @throws RequestBuilderException
	 */
	public function update(IShare $share): IShare {
		$wrappedShare = $this->shareWrapperService->getShareById((int)$share->getId());
		$wrappedShare->setPermissions($share->getPermissions())
					 ->setShareOwner($share->getShareOwner())
					 ->setSharedBy($share->getSharedBy());

		$this->shareWrapperService->update($wrappedShare);

		return $wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
	}

	/**
	 * @param IShare $share
	 *
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotConfirmedException
	 * @throws InvalidIdException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function delete(IShare $share): void {
		if ($share->getShareType() !== IShare::TYPE_CIRCLE) {
			return;
		}

		$this->federatedUserService->initCurrentUser();
		try {
			$wrappedShare = $this->shareWrapperService->getShareById((int)$share->getId());
		} catch (ShareWrapperNotFoundException $e) {
			return;
		}

		$this->shareWrapperService->delete($wrappedShare);

		try {
			$circle = $this->circleService->getCircle($share->getSharedWith());
		} catch (CircleNotFoundException $e) {
			return;
		} catch (InitiatorNotFoundException $e) {
			// force the unshare ?
			return;
		}

		$event = new FederatedEvent(FileUnshare::class);
		$event->setCircle($circle)
			  ->getParams()->sObj('wrappedShare', $wrappedShare);

		$this->federatedEventService->newEvent($event);
		$this->eventService->localShareDeleted($wrappedShare);
	}

	/**
	 * @param IShare $share
	 * @param string $recipient
	 *
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws NotFoundException
	 * @throws RequestBuilderException
	 * @throws ShareWrapperNotFoundException
	 * @throws SingleCircleNotFoundException
	 */
	public function deleteFromSelf(IShare $share, $recipient): void {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($recipient);
		$child = $this->shareWrapperService->getChild($share, $federatedUser);
		$this->debug('Shares::move()', ['federatedUser' => $federatedUser, 'child' => $child]);

		if ($child->getPermissions() > 0) {
			$child->setPermissions(0);
			$this->shareWrapperService->update($child);
		}
	}


	/**
	 * @param IShare $share
	 * @param string $recipient
	 *
	 * @return IShare
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws IllegalIDChangeException
	 * @throws InvalidIdException
	 * @throws NotFoundException
	 * @throws RequestBuilderException
	 * @throws ShareWrapperNotFoundException
	 * @throws SingleCircleNotFoundException
	 */
	public function move(IShare $share, $recipient): IShare {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($recipient);
		$child = $this->shareWrapperService->getChild($share, $federatedUser);
		$this->debug('Shares::move()', ['federatedUser' => $federatedUser, 'child' => $child]);

		if ($child->getFileTarget() !== $share->getTarget()) {
			$child->setFileTarget($share->getTarget());
			$this->shareWrapperService->update($child);
		}

		$wrappedShare = $this->shareWrapperService->getShareById((int)$share->getId(), $federatedUser);

		return $wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
	}


	/**
	 * @param IShare $share
	 * @param string $recipient
	 *
	 * @return IShare
	 */
	public function restore(IShare $share, string $recipient): IShare {
		$orig = $this->shareWrapperService->getShareById((int)$share->getId());

		$federatedUser = $this->federatedUserService->getLocalFederatedUser($recipient);
		$child = $this->shareWrapperService->getChild($share, $federatedUser);
		$this->debug('Shares::restore()', ['federatedUser' => $federatedUser, 'child' => $child]);

		if ($child->getPermissions() !== $orig->getPermissions()) {
			$child->setPermissions($orig->getPermissions());
			$this->shareWrapperService->update($child);
		}

		$wrappedShare = $this->shareWrapperService->getShareById((int)$share->getId(), $federatedUser);

		return $wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
	}


	/**
	 * @param string $userId
	 * @param Folder $node
	 * @param bool $reshares
	 * @param bool $shallow Whether the method should stop at the first level, or look into sub-folders.
	 *
	 * @return array
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws IllegalIDChangeException
	 * @throws InvalidIdException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	public function getSharesInFolder($userId, Folder $node, $reshares, $shallow = true): array {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
		$wrappedShares = $this->shareWrapperService->getSharesInFolder(
			$federatedUser,
			$node,
			$reshares,
			$shallow
		);

		$result = [];
		foreach ($wrappedShares as $wrappedShare) {
			if (!array_key_exists($wrappedShare->getFileSource(), $result)) {
				$result[$wrappedShare->getFileSource()] = [];
			}
			if ($wrappedShare->getFileCache()->isAccessible()) {
				$result[$wrappedShare->getFileSource()][] =
					$wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
			}
		}

		return $result;
	}


	/**
	 * @param string $userId
	 * @param int $shareType
	 * @param Node|null $node
	 * @param bool $reshares
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return IShare[]
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws IllegalIDChangeException
	 * @throws InvalidIdException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	public function getSharesBy($userId, $shareType, $node, $reshares, $limit, $offset): array {
		if ($shareType !== IShare::TYPE_CIRCLE) {
			return [];
		}

		$nodeId = (!is_null($node)) ? $node->getId() : 0;

		try {
			$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId, false);
		} catch (Exception $e) {
			$this->e($e, ['userId' => $userId, 'shareType' => $shareType, 'nodeId' => $nodeId]);

			return [];
		}

		$wrappedShares = $this->shareWrapperService->getSharesBy(
			$federatedUser,
			$nodeId,
			$reshares,
			$limit,
			$offset,
			true
		);

		return array_filter(
			array_map(
				function (ShareWrapper $wrapper) {
					return $wrapper->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
				}, $wrappedShares
			)
		);
	}


	/**
	 * @param string $shareId
	 * @param string|null $recipientId
	 *
	 * @return IShare
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws IllegalIDChangeException
	 * @throws InvalidIdException
	 * @throws ShareNotFound
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getShareById($shareId, $recipientId = null): IShare {
		if (is_null($recipientId)) {
			$federatedUser = null;
		} else {
			$federatedUser = $this->federatedUserService->getLocalFederatedUser($recipientId);
		}

		try {
			$wrappedShare = $this->shareWrapperService->getShareById((int)$shareId, $federatedUser);
		} catch (ShareWrapperNotFoundException $e) {
			throw new  ShareNotFound();
		}

		return $wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
	}


	/**
	 * @param Node $path
	 *
	 * @return IShare[]
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws IllegalIDChangeException
	 * @throws RequestBuilderException
	 */
	public function getSharesByPath(Node $path): array {
		$wrappedShares = $this->shareWrapperService->getSharesByFileId($path->getId());

		return array_filter(
			array_map(
				function (ShareWrapper $wrapper) {
					return $wrapper->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
				}, $wrappedShares
			)
		);
	}


	/**
	 * @param string $userId
	 * @param int $shareType
	 * @param Node|null $node
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return IShare[]
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws IllegalIDChangeException
	 * @throws InvalidIdException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset): array {
		if ($shareType !== IShare::TYPE_CIRCLE) {
			return [];
		}

		$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
		$probe = new CircleProbe();
		$probe->includePersonalCircles()
			  ->includeSystemCircles()
			  ->mustBeMember()
			  ->setItemsLimit((int)$limit)
			  ->setItemsOffset((int)$offset);

		$wrappedShares = $this->shareWrapperService->getSharedWith(
			$federatedUser,
			(!is_null($node)) ? $node->getId() : 0,
			$probe
		);

		return array_filter(
			array_map(
				function (ShareWrapper $wrapper) {
					return $wrapper->getShare(
						$this->rootFolder, $this->userManager, $this->urlGenerator, true
					);
				}, $wrappedShares
			)
		);
	}


	/**
	 * @param string $token
	 *
	 * @return IShare
	 * @throws IllegalIDChangeException
	 * @throws RequestBuilderException
	 * @throws ShareNotFound
	 */
	public function getShareByToken($token): IShare {
		if (is_null($token)) {
			throw new ShareNotFound();
		}

		try {
			$wrappedShare = $this->shareWrapperService->getShareByToken($token);
		} catch (ShareWrapperNotFoundException $e) {
			throw new ShareNotFound();
		}

		$share = $wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
		if ($share->getPassword() !== '') {
			$this->logger->notice('share is protected by a password, hash: ' . $share->getPassword());
		}

		return $share;
	}


	public function formatShare(IShare $share): array {
		$this->federatedUserService->initCurrentUser();
		$circleProbe = new CircleProbe();
		$dataProbe = new DataProbe();

		$result = ['share_with' => $share->getSharedWith()];
		try {
			$circle = $this->circleService->probeCircle($share->getSharedWith(), $circleProbe, $dataProbe);
			$result['share_with_displayname'] = $circle->getDisplayName();
		} catch (Exception $e) {
			$this->logger->warning(
				'Circle not found while probeCircle',
				[
					'sharedWith' => $share->getSharedWith(),
					'exception' => $e
				]
			);
		}

		return $result;
	}


	/**
	 * @param string $uid
	 * @param int $shareType
	 */
	public function userDeleted($uid, $shareType): void {
	}


	/**
	 * @param string $gid
	 */
	public function groupDeleted($gid): void {
	}


	/**
	 * @param string $uid
	 * @param string $gid
	 */
	public function userDeletedFromGroup($uid, $gid): void {
	}


	/**
	 * @param Node[] $nodes
	 * @param bool $currentAccess
	 *
	 * @return array
	 */
	public function getAccessList($nodes, $currentAccess): array {
		return [];
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
	public function getChildren(IShare $parent): array {
		return [];
	}


	/**
	 * @return iterable
	 */
	public function getAllShares(): iterable {
//		$qb = $this->dbConnection->getQueryBuilder();
//
//		$qb->select(' * ')
//		   ->from('share')
//		   ->where(
//			   $qb->expr()
//				  ->orX(
//					  $qb->expr()
//						 ->eq('share_type', $qb->createNamedParameter(IShare::TYPE_CIRCLE))
//				  )
//		   );
//
//		$cursor = $qb->execute();
//		while ($data = $cursor->fetch()) {
//			try {
//				yield $this->createShareObject($data);
//			} catch (IllegalIDChangeException $e) {
//			};
//		}
//		$cursor->closeCursor();
		return [];
	}
}
