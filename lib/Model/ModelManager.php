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


use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\FileCacheNotFoundException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IMemberships;
use OCA\Circles\Service\ConfigService;

/**
 * Class ModelManager
 *
 * @package OCA\Circles\Model
 */
class ModelManager {


	/** @var CoreRequestBuilder */
	private $coreRequestBuilder;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var ConfigService */
	private $configService;


	/** @var bool */
	private $fullDetails = false;


	/**
	 * ModelManager constructor.
	 *
	 * @param CoreRequestBuilder $coreRequestBuilder
	 * @param MemberRequest $memberRequest
	 * @param MembershipRequest $membershipRequest
	 * @param ConfigService $configService
	 */
	public function __construct(
		CoreRequestBuilder $coreRequestBuilder, MemberRequest $memberRequest,
		MembershipRequest $membershipRequest, ConfigService $configService
	) {
		$this->coreRequestBuilder = $coreRequestBuilder;
		$this->memberRequest = $memberRequest;
		$this->membershipRequest = $membershipRequest;
		$this->configService = $configService;
	}


	/**
	 * @return ConfigService
	 */
	public function getConfigService(): ConfigService {
		return $this->configService;
	}


	/**
	 * @param IMemberships $member
	 */
	public function getMembers(IMemberships $member): void {
		try {
			$member->setMembers($this->memberRequest->getMembers($member->getSingleId()));
		} catch (RequestBuilderException $e) {
			// TODO: debug log
		}
	}


	/**
	 * @param IMemberships $item
	 * @param bool $detailed
	 */
	public function getInheritedMembers(IMemberships $item, bool $detailed = false): void {
		try {
			$item->setInheritedMembers(
				$this->memberRequest->getInheritedMembers($item->getSingleId(), $detailed),
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
	 * @param Circle $circle
	 */
	public function memberOf(Circle $circle) {
//		$members = $this->memberService->getMembers($circle->getSingleId());
//		$circle->setMemberOf([]);
	}


	/**
	 * @param ManagedModel $model
	 * @param array $data
	 * @param string $base
	 */
	public function manageImportFromDatabase(ManagedModel $model, array $data, string $base): void {
		if ($model instanceof Circle) {
			if ($base === '') {
				$base = CoreRequestBuilder::CIRCLE;
			}
		}

		if ($model instanceof Member) {
			if ($base === '') {
				$base = CoreRequestBuilder::MEMBER;
			}
		}

		if ($model instanceof ShareWrapper) {
			if ($base === '') {
				$base = CoreRequestBuilder::SHARE;
			}
		}

		if ($model instanceof Mount) {
			if ($base === '') {
				$base = CoreRequestBuilder::MOUNT;
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
			case CoreRequestBuilder::OWNER;
				try {
					$owner = new Member();
					$owner->importFromDatabase($data, $prefix);
					$circle->setOwner($owner);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreRequestBuilder::INITIATOR;
				try {
					$initiator = new Member();
					$initiator->importFromDatabase($data, $prefix);
					$circle->setInitiator($initiator);
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
			case CoreRequestBuilder::CIRCLE;
				try {
					$circle = new Circle();
					$circle->importFromDatabase($data, $prefix);
					$member->setCircle($circle);
				} catch (CircleNotFoundException $e) {
				}
				break;

			case CoreRequestBuilder::BASED_ON;
				try {
					$circle = new Circle();
					$circle->importFromDatabase($data, $prefix);
					$member->setBasedOn($circle);
				} catch (CircleNotFoundException $e) {
				}
				break;

			case CoreRequestBuilder::INHERITED_BY;
				try {
					$inheritedBy = new FederatedUser();
					$inheritedBy->importFromDatabase($data, $prefix);
					$member->setInheritedBy($inheritedBy);
				} catch (FederatedUserNotFoundException $e) {
				}
				break;

			case CoreRequestBuilder::INHERITANCE_FROM;
				try {
					$inheritanceFrom = new Member();
					$inheritanceFrom->importFromDatabase($data, $prefix);
					$member->setInheritanceFrom($inheritanceFrom);
				} catch (MemberNotFoundException $e) {
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
			case CoreRequestBuilder::MEMBERSHIPS;
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
			case CoreRequestBuilder::CIRCLE;
				try {
					$circle = new Circle();
					$circle->importFromDatabase($data, $prefix);
					$shareWrapper->setCircle($circle);
				} catch (CircleNotFoundException $e) {
				}
				break;

			case CoreRequestBuilder::INITIATOR;
				try {
					$initiator = new Member();
					$initiator->importFromDatabase($data, $prefix);
					$shareWrapper->setInheritedBy($initiator);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreRequestBuilder::INHERITED_BY;
				try {
					$inheritedBy = new Member();
					$inheritedBy->importFromDatabase($data, $prefix);
					$shareWrapper->setInheritedBy($inheritedBy);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreRequestBuilder::FILE_CACHE;
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
			case CoreRequestBuilder::MEMBER;
				try {
					$member = new Member();
					$member->importFromDatabase($data, $prefix);
					$mount->setMember($member);
				} catch (MemberNotFoundException $e) {
				}
				break;

			case CoreRequestBuilder::INITIATOR;
				try {
					$initiator = new Member();
					$initiator->importFromDatabase($data, $prefix);
					$mount->setInitiator($initiator);
				} catch (MemberNotFoundException $e) {
					\OC::$server->getLogger()->log(3, '### ' . $e->getMessage());
				}
				break;
		}
	}

	/**
	 * @return string
	 */
	public function getLocalInstance(): string {
		return $this->configService->getFrontalInstance();
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

