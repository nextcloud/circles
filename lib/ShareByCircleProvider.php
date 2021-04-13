<?php

declare(strict_types=1);


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


use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use OC;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\ShareWrapperNotFoundException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\ShareWrapperService;
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


/**
 * Class ShareByCircleProvider
 *
 * @package OCA\Circles
 */
class ShareByCircleProvider implements IShareProvider {


	use TArrayTools;
	use TStringTools;


	const IDENTIFIER = 'ocCircleShare';


	/** @var IUserManager */
	private $userManager;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IL10N */
	private $l10n;

	/** @var ILogger */
	private $logger;

	/** @var IURLGenerator */
	private $urlGenerator;


	/** @var ShareWrapperService */
	private $shareWrapperService;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var EventService */
	private $eventService;


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
	 */
	public function __construct(
		IDBConnection $connection, ISecureRandom $secureRandom, IUserManager $userManager,
		IRootFolder $rootFolder, IL10N $l10n, ILogger $logger, IURLGenerator $urlGenerator
	) {
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;

		$this->federatedUserService = OC::$server->get(FederatedUserService::class);
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
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws IllegalIDChangeException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws ShareNotFound
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
		$circle = $this->circleService->getCircle($share->getSharedWith());

		$initiatorHelper = new MemberHelper($circle->getInitiator());
		$initiatorHelper->mustBeMember();

		$share->setToken($this->token(15));
		$this->shareWrapperService->save($share);

		try {
			$wrappedShare = $this->shareWrapperService->getShareById(
				(int)$share->getId(),
				$this->federatedUserService->getCurrentUser()
			);
		} catch (ShareWrapperNotFoundException $e) {
			throw new ShareNotFound();
		}

		$this->eventService->shareCreated($wrappedShare);

		return $wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
	}


	/**
	 * @param IShare $share
	 *
	 * @return IShare
	 */
	public function update(IShare $share): IShare {
		OC::$server->getLogger()->log(3, 'CSP > update');

		return $share;
	}

	/**
	 * @param IShare $share
	 */
	public function delete(IShare $share): void {
		OC::$server->getLogger()->log(3, 'CSP > delete');
	}

	/**
	 * @param IShare $share
	 * @param string $recipient
	 */
	public function deleteFromSelf(IShare $share, $recipient): void {
		OC::$server->getLogger()->log(3, 'CSP > deleteFromSelf');
	}

	/**
	 * @param IShare $share
	 * @param string $recipient
	 *
	 * @return IShare
	 */
	public function restore(IShare $share, string $recipient): IShare {
		OC::$server->getLogger()->log(3, 'CSP > restore');

		return $share;
	}

	/**
	 * @param IShare $share
	 * @param string $recipient
	 *
	 * @return IShare
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws ShareWrapperNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws IllegalIDChangeException
	 * @throws NotFoundException
	 * @throws RequestBuilderException
	 */
	public function move(IShare $share, $recipient): IShare {
		OC::$server->getLogger()->log(3, 'CSP > move' . $share->getId() . ' ' . $recipient);

		$federatedUser = $this->federatedUserService->getLocalFederatedUser($recipient);
		$child = $this->shareWrapperService->getChild($share, $federatedUser);
		if ($child->getFileTarget() !== $share->getTarget()) {
			$child->setFileTarget($share->getTarget());
			$this->shareWrapperService->update($child);
		}

		$wrappedShare = $this->shareWrapperService->getShareById((int)$share->getId(), $federatedUser);

		return $wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
	}


	/**
	 * @param string $userId
	 * @param Folder $node
	 * @param bool $reshares
	 *
	 * @return array
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws IllegalIDChangeException
	 * @throws InvalidIdException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getSharesInFolder($userId, Folder $node, $reshares): array {
		OC::$server->getLogger()->log(3, 'CSP > getSharesInFolder');

		$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
		$wrappedShares = $this->shareWrapperService->getSharesInFolder(
			$federatedUser,
			(!is_null($node)) ? $node->getId() : 0,
			$reshares
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

		OC::$server->getLogger()->log(3, 'CSP > getSharesBy');

		$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
		$wrappedShares = $this->shareWrapperService->getSharesBy(
			$federatedUser,
			(!is_null($node)) ? $node->getId() : 0,
			$reshares,
			$limit,
			$offset,
			true
		);

		return array_filter(
			array_map(
				function(ShareWrapper $wrapper) {
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
	 */
	public function getShareById($shareId, $recipientId = null): IShare {
		OC::$server->getLogger()->log(3, 'CSP > getShareById');

		if ($recipientId === null) {
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
	 */
	public function getSharesByPath(Node $path): array {
		OC::$server->getLogger()->log(3, 'CSP > getSharesByPath');
		$wrappedShares = $this->shareWrapperService->getSharesByFileId($path->getId());

		return array_filter(
			array_map(
				function(ShareWrapper $wrapper) {
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
		$wrappedShares = $this->shareWrapperService->getSharedWith(
			$federatedUser,
			(!is_null($node)) ? $node->getId() : 0,
			$limit,
			$offset
		);

		return array_filter(
			array_map(
				function(ShareWrapper $wrapper) {
					return $wrapper->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
				}, $wrappedShares
			)
		);
	}


	/**
	 * @param string $token
	 *
	 * @return IShare
	 */
	public function getShareByToken($token): IShare {
		OC::$server->getLogger()->log(3, 'CSP > getShareByToken');
	}


	/**
	 * @param string $uid
	 * @param int $shareType
	 */
	public function userDeleted($uid, $shareType): void {
		OC::$server->getLogger()->log(3, 'CSP > userDeleted');
	}


	/**
	 * @param string $gid
	 */
	public function groupDeleted($gid): void {
		OC::$server->getLogger()->log(3, 'CSP > groupDeleted');
	}


	/**
	 * @param string $uid
	 * @param string $gid
	 */
	public function userDeletedFromGroup($uid, $gid): void {
		OC::$server->getLogger()->log(3, 'CSP > userDeletedFromGroup');
	}


	/**
	 * @param Node[] $nodes
	 * @param bool $currentAccess
	 *
	 * @return array
	 */
	public function getAccessList($nodes, $currentAccess): array {
		OC::$server->getLogger()->log(3, 'CSP > getAccessList');

		return [];
	}


	/**
	 * @return iterable
	 */
	public function getAllShares(): iterable {
		OC::$server->getLogger()->log(3, 'CSP > getAllShares');

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
	}

}

