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


namespace OCA\Circles\Model;

use OCA\Circles\Db\CoreQueryBuilder;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\FileCacheNotFoundException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\IMemberships;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\InterfaceService;
use OCP\IURLGenerator;

/**
 * Class ModelManager
 *
 * @package OCA\Circles\Model
 */
class ModelManager {


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var CoreQueryBuilder */
	private $coreRequestBuilder;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var InterfaceService */
	private $interfaceService;

	/** @var ConfigService */
	private $configService;


	/** @var bool */
	private $fullDetails = false;


	/**
	 * ModelManager constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param CoreQueryBuilder $coreRequestBuilder
	 * @param MemberRequest $memberRequest
	 * @param MembershipRequest $membershipRequest
	 * @param InterfaceService $interfaceService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		CoreQueryBuilder $coreRequestBuilder,
		MemberRequest $memberRequest,
		MembershipRequest $membershipRequest,
		InterfaceService $interfaceService,
		ConfigService $configService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->coreRequestBuilder = $coreRequestBuilder;
		$this->memberRequest = $memberRequest;
		$this->membershipRequest = $membershipRequest;
		$this->interfaceService = $interfaceService;
		$this->configService = $configService;
	}


	/**
	 * @return ConfigService
	 */
	public function getConfigService(): ConfigService {
		return $this->configService;
	}


	/**
	 * @param Circle $circle
	 */
	public function getMembers(Circle $circle): void {
		try {
			$circle->setMembers($this->memberRequest->getMembers($circle->getSingleId()));
		} catch (RequestBuilderException $e) {
			// TODO: debug log
		}
	}


	/**
	 * @param Circle $circle
	 * @param bool $detailed
	 */
	public function getInheritedMembers(Circle $circle, bool $detailed = false): void {
		try {
			$circle->setInheritedMembers(
				$this->memberRequest->getInheritedMembers($circle->getSingleId(), $detailed),
				$detailed
			);
		} catch (RequestBuilderException $e) {
			// TODO: debug log
		}
	}


	/**
	 * @param IMemberships $member
	 */
	public function getMemberships(IMemberships $member): void {
		$memberships = $this->membershipRequest->getMemberships($member->getSingleId());
		$member->setMemberships($memberships);
	}


	/**
	 * @param ManagedModel $model
	 * @param array $data
	 * @param string $base
	 */
	public function manageImportFromDatabase(ManagedModel $model, array $data, string $base): void {
		if ($model instanceof Circle) {
			if ($base === '') {
				$base = CoreQueryBuilder::CIRCLE;
			}
		}

		if ($model instanceof Member) {
			if ($base === '') {
				$base = CoreQueryBuilder::MEMBER;
			}
		}

		if ($model instanceof ShareWrapper) {
			if ($base === '') {
				$base = CoreQueryBuilder::SHARE;
			}
		}

		if ($model instanceof Mount) {
			if ($base === '') {
				$base = CoreQueryBuilder::MOUNT;
			}
		}

		foreach ($this->coreRequestBuilder->getAvailablePath($base) as $path => $prefix) {
			$this->importBasedOnPath($model, $data, $path, $prefix);
		}
	}


	private function importBasedOnPath(ManagedModel $model, array $data, string $path, string $prefix) {
		if ($model instanceof Circle) {
			$this->importIntoCircle($model, $data, $path, $prefix);
		}

		if ($model instanceof Member) {
			$this->importIntoMember($model, $data, $path, $prefix);
		}

		if ($model instanceof FederatedUser) {
			$this->importIntoFederatedUser($model, $data, $path, $prefix);
		}

		if ($model instanceof ShareWrapper) {
			$this->importIntoShareWrapper($model, $data, $path, $prefix);
		}

		if ($model instanceof Mount) {
			$this->importIntoMount($model, $data, $path, $prefix);
		}
	}


	/**
	 * @param Circle $circle
	 * @param array $data
	 * @param string $path
	 * @param string $prefix
	 */
	private function importIntoCircle(Circle $circle, array $data, string $path, string $prefix): void {
		switch ($path) {
			case CoreQueryBuilder::OWNER:
				try {
					$owner = new Member();
					$owner->importFromDatabase($data, $prefix);
					$circle->setOwner($owner);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::INITIATOR:
				try {
					$initiator = new Member();
					$initiator->importFromDatabase($data, $prefix);
					$circle->setInitiator($initiator);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::DIRECT_INITIATOR:
				try {
					$directInitiator = new Member();
					$directInitiator->importFromDatabase($data, $prefix);
					$circle->setDirectInitiator($directInitiator);
				} catch (MemberNotFoundException $e) {
				}
				break;
		}
	}


	/**
	 * @param Member $member
	 * @param array $data
	 * @param string $path
	 * @param string $prefix
	 */
	private function importIntoMember(Member $member, array $data, string $path, string $prefix): void {
		switch ($path) {
			case CoreQueryBuilder::CIRCLE:
				try {
					$circle = new Circle();
					$circle->importFromDatabase($data, $prefix);
					$member->setCircle($circle);
				} catch (CircleNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::BASED_ON:
				try {
					$circle = new Circle();
					$circle->importFromDatabase($data, $prefix);
					$member->setBasedOn($circle);
				} catch (CircleNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::INHERITED_BY:
				try {
					$inheritedBy = new FederatedUser();
					$inheritedBy->importFromDatabase($data, $prefix);
					$member->setInheritedBy($inheritedBy);
				} catch (FederatedUserNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::INVITED_BY:
				try {
					$invitedByCircle = new Circle();
					$invitedByCircle->importFromDatabase($data, $prefix);
					$invitedBy = new FederatedUser();
					$invitedBy->importFromCircle($invitedByCircle);
					$member->setInvitedBy($invitedBy);
				} catch (CircleNotFoundException | OwnerNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::INHERITANCE_FROM:
				try {
					$inheritanceFrom = new Member();
					$inheritanceFrom->importFromDatabase($data, $prefix);
					$member->setInheritanceFrom($inheritanceFrom);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::REMOTE:
				try {
					$remoteInstance = new RemoteInstance();
					$remoteInstance->importFromDatabase($data, $prefix);
					$member->setRemoteInstance($remoteInstance);
				} catch (RemoteNotFoundException $e) {
				}
				break;
		}
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param array $data
	 * @param string $path
	 * @param string $prefix
	 */
	private function importIntoFederatedUser(
		FederatedUser $federatedUser,
		array $data,
		string $path,
		string $prefix
	): void {
		switch ($path) {
			case CoreQueryBuilder::MEMBERSHIPS:
				try {
					$membership = new Membership();
					$membership->importFromDatabase($data, $prefix);
					$federatedUser->setLink($membership);
				} catch (MembershipNotFoundException $e) {
				}
				break;
		}
	}


	/**
	 * @param ShareWrapper $shareWrapper
	 * @param array $data
	 * @param string $path
	 * @param string $prefix
	 */
	private function importIntoShareWrapper(
		ShareWrapper $shareWrapper,
		array $data,
		string $path,
		string $prefix
	): void {
		switch ($path) {
			case CoreQueryBuilder::CIRCLE:
				try {
					$circle = new Circle();
					$circle->importFromDatabase($data, $prefix);
					$shareWrapper->setCircle($circle);
				} catch (CircleNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::INITIATOR:
				try {
					$initiator = new Member();
					$initiator->importFromDatabase($data, $prefix);
					$shareWrapper->setInitiator($initiator);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::INHERITED_BY:
				try {
					$inheritedBy = new Member();
					$inheritedBy->importFromDatabase($data, $prefix);
					$shareWrapper->setInitiator($inheritedBy);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::FILE_CACHE:
				try {
					$fileCache = new FileCacheWrapper();
					$fileCache->importFromDatabase($data, $prefix);
					$shareWrapper->setFileCache($fileCache);
				} catch (FileCacheNotFoundException $e) {
				}
				break;
		}
	}


	/**
	 * @param Mount $mount
	 * @param array $data
	 * @param string $path
	 * @param string $prefix
	 */
	private function importIntoMount(
		Mount $mount,
		array $data,
		string $path,
		string $prefix
	): void {
		switch ($path) {
			case CoreQueryBuilder::MEMBER:
				try {
					$member = new Member();
					$member->importFromDatabase($data, $prefix);
					$mount->setOwner($member);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreQueryBuilder::INITIATOR:
				try {
					$initiator = new Member();
					$initiator->importFromDatabase($data, $prefix);
					$mount->setInitiator($initiator);
				} catch (MemberNotFoundException $e) {
				}
				break;
		}
	}


	/**
	 * @return string
	 */
	public function getLocalInstance(): string {
		return $this->interfaceService->getLocalInstance();
	}

	/**
	 * @param string $instance
	 *
	 * @return bool
	 */
	public function isLocalInstance(string $instance): bool {
		return $this->configService->isLocalInstance($instance);
	}


	/**
	 * @param string $instance
	 *
	 * @return string
	 * @throws UnknownInterfaceException
	 */
	public function fixInstance(string $instance): string {
		if (!$this->interfaceService->hasCurrentInterface()) {
			return $instance;
		}

		if (!$this->configService->isLocalInstance($instance)) {
			return $instance;
		}

		return $this->interfaceService->getCloudInstance();
	}


	/**
	 * @param string $singleId
	 *
	 * @return string
	 */
	public function generateLinkToCircle(string $singleId): string {
		return $this->urlGenerator->linkToRoute(
			$this->configService->getAppValue(
				ConfigService::ROUTE_TO_CIRCLE
			),
			['singleId' => $singleId]
		);
	}


	/**
	 * @param bool $full
	 */
	public function setFullDetails(bool $full): void {
		$this->fullDetails = $full;
	}

	/**
	 * @return bool
	 */
	public function isFullDetails(): bool {
		return $this->fullDetails;
	}
}
