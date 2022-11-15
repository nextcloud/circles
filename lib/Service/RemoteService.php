<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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

namespace OCA\Circles\Service;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Tools\ActivityPub\NCSignature;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Model\SimpleDataStore;

/**
 * Class RemoteService
 *
 * @package OCA\Circles\Service
 */
class RemoteService extends NCSignature {
	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var RemoteStreamService */
	private $remoteStreamService;

	/** @var MembershipService */
	private $membershipService;

	/** @var ShareService */
	private $shareService;


	/**
	 * RemoteService constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param RemoteStreamService $remoteStreamService
	 * @param MembershipService $membershipService
	 * @param ShareService $shareService
	 */
	public function __construct(
		CircleRequest $circleRequest, MemberRequest $memberRequest, RemoteStreamService $remoteStreamService,
		MembershipService $membershipService, ShareService $shareService
	) {
		$this->setup('app', 'circles');

		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->remoteStreamService = $remoteStreamService;
		$this->membershipService = $membershipService;
		$this->shareService = $shareService;
	}


	/**
	 * @param string $instance
	 * @param array $data
	 *
	 * @return Circle[]
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 */
	public function getCirclesFromInstance(string $instance, array $data = []): array {
		$result = $this->remoteStreamService->resultRequestRemoteInstance(
			$instance,
			RemoteInstance::CIRCLES,
			Request::TYPE_GET,
			new SimpleDataStore($data)
		);

		$circles = [];
		foreach ($result as $item) {
			try {
				$circle = new Circle();
				$circle->import($item);
				$circles[] = $circle;
			} catch (InvalidItemException $e) {
			}
		}

		return $circles;
	}


	/**
	 * @param string $circleId
	 * @param string $instance
	 * @param array $data
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 * @throws InvalidItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws FederatedItemException
	 */
	public function getCircleFromInstance(string $circleId, string $instance, array $data = []): Circle {
		// TODO: check that $instance is not Local !!
		$result = $this->remoteStreamService->resultRequestRemoteInstance(
			$instance,
			RemoteInstance::CIRCLE,
			Request::TYPE_GET,
			new SimpleDataStore($data),
			['circleId' => $circleId]
		);

		if (empty($result)) {
			throw new CircleNotFoundException();
		}

		$circle = new Circle();
		$circle->import($result);

		return $circle;
	}


	/**
	 * @param string $circleId
	 * @param string $instance
	 * @param array $data
	 *
	 * @return Member[]
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function getMembersFromInstance(string $circleId, string $instance, array $data = []): array {
		$result = $this->remoteStreamService->resultRequestRemoteInstance(
			$instance,
			RemoteInstance::MEMBERS,
			Request::TYPE_GET,
			new SimpleDataStore($data),
			['circleId' => $circleId]
		);

		$members = [];
		foreach ($result as $item) {
			try {
				$member = new Member();
				$member->import($item);
				$members[] = $member;
			} catch (InvalidItemException $e) {
			}
		}

		return $members;
	}


	/**
	 * @param string $circleId
	 * @param string $instance
	 * @param array $data
	 *
	 * @return Member[]
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function getInheritedFromInstance(string $circleId, string $instance, array $data = []): array {
		$result = $this->remoteStreamService->resultRequestRemoteInstance(
			$instance,
			RemoteInstance::INHERITED,
			Request::TYPE_GET,
			new SimpleDataStore($data),
			['circleId' => $circleId]
		);

		$members = [];
		foreach ($result as $item) {
			try {
				$member = new Member();
				$member->import($item);
				$members[] = $member;
			} catch (InvalidItemException $e) {
			}
		}

		return $members;
	}


	/**
	 * @param string $circleId
	 * @param string $instance
	 * @param array $data
	 *
	 * @return Membership[]
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function getMembershipsFromInstance(string $circleId, string $instance, array $data = []): array {
		$result = $this->remoteStreamService->resultRequestRemoteInstance(
			$instance,
			RemoteInstance::MEMBERSHIPS,
			Request::TYPE_GET,
			new SimpleDataStore($data),
			['circleId' => $circleId]
		);

		$members = [];
		foreach ($result as $item) {
			try {
				$member = new Membership();
				$member->import($item);
				$members[] = $member;
			} catch (InvalidItemException $e) {
			}
		}

		return $members;
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws CircleNotFoundException
	 * @throws InvalidIdException
	 * @throws InvalidItemException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws RemoteInstanceException
	 */
	public function syncCircle(Circle $circle): void {
//		if ($this->configService->isLocalInstance($circle->getInstance())) {
//			$this->syncLocalCircle($circle);
//		} else {
		$this->syncRemoteCircle($circle->getSingleId(), $circle->getInstance());
//		}
	}


	/**
	 * @param Circle $circle
	 */
	private function syncLocalCircle(Circle $circle): void {
	}


	/**
	 * @param string $circleId
	 * @param string $instance
	 *
	 * @throws InvalidItemException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws CircleNotFoundException
	 * @throws InvalidIdException
	 * @throws RemoteInstanceException
	 */
	public function syncRemoteCircle(string $circleId, string $instance): void {
		$loop = 0;
		$knownInstance = [];
		while (true) {
			$loop++;
			if ($loop > 10 || in_array($instance, $knownInstance)) {
				throw new CircleNotFoundException(
					'circle not found after browsing ' . implode(', ', $knownInstance)
				);
			}
			$knownInstance[] = $instance;

			$circle = $this->getCircleFromInstance($circleId, $instance);
			if ($circle->getInstance() === $instance) {
				break;
			}

			$instance = $circle->getInstance();
		}

		$this->circleRequest->insertOrUpdate($circle);
		$this->memberRequest->insertOrUpdate($circle->getOwner());

		$this->syncRemoteMembers($circle);
		$this->membershipService->onUpdate($circle->getSingleId());

		$this->shareService->syncRemoteShares($circle);
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws RemoteInstanceException
	 */
	public function syncRemoteMembers(Circle $circle) {
		$members = $this->getMembersFromInstance($circle->getSingleId(), $circle->getInstance());
		foreach ($members as $member) {
			try {
				$this->memberRequest->insertOrUpdate($member);
			} catch (InvalidIdException $e) {
			}
		}

		$this->membershipService->onUpdate($circle->getSingleId());
	}


	/**
	 * @param string $userId
	 * @param string $instance
	 * @param int $type
	 *
	 * @return FederatedUser
	 * @throws FederatedUserNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws FederatedUserException
	 * @throws FederatedItemException
	 */
	public function getFederatedUserFromInstance(
		string $userId,
		string $instance,
		int $type = Member::TYPE_USER
	): FederatedUser {
		$result = $this->remoteStreamService->resultRequestRemoteInstance(
			$instance,
			RemoteInstance::MEMBER,
			Request::TYPE_GET,
			null,
			['type' => Member::$TYPE[$type], 'userId' => $userId]
		);

		if (empty($result)) {
			throw new FederatedUserNotFoundException();
		}

		$federatedUser = new FederatedUser();
		try {
			$federatedUser->import($result);
		} catch (InvalidItemException $e) {
			throw new FederatedUserException('incorrect federated user returned from instance');
		}
		if ($federatedUser->getInstance() !== $instance) {
			throw new FederatedUserException('incorrect instance on returned federated user');
		}

		return $federatedUser;
	}
}
