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
			echo $e->getMessage();
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

		foreach ($this->coreRequestBuilder->getAvailablePath($base) as $path => $prefix) {
			$this->importBasedOnPath($model, $data, $path, $prefix);
		}
	}


	private function importBasedOnPath(ManagedModel $model, array $data, string $path, string $prefix) {
		if ($model instanceof Circle) {
			switch ($path) {
				case CoreRequestBuilder::OWNER;
					$this->importOwnerFromDatabase($model, $data, $prefix);
					break;

				case CoreRequestBuilder::INITIATOR;
					$this->importInitiatorFromDatabase($model, $data, $prefix);
					break;
			}
		}

		if ($model instanceof Member) {
			switch ($path) {
				case CoreRequestBuilder::CIRCLE;
					$this->importCircleFromDatabase($model, $data, $prefix);
					break;

				case CoreRequestBuilder::BASED_ON;
					$this->importBasedOnFromDatabase($model, $data, $prefix);
					break;

				case CoreRequestBuilder::INHERITED_BY;
					$this->importInheritedByFromDatabase($model, $data, $prefix);
					break;

				case CoreRequestBuilder::INHERITANCE_FROM;
					$this->importInheritanceFromFromDatabase($model, $data, $prefix);
					break;
			}
		}

		if ($model instanceof FederatedUser) {
			switch ($path) {
				case CoreRequestBuilder::MEMBERSHIPS;
					$this->importMembershipFromDatabase($model, $data, $prefix);
					break;
			}
		}

		if ($model instanceof ShareWrapper) {
			switch ($path) {
				case CoreRequestBuilder::CIRCLE;
					try {
						$circle = new Circle();
						$circle->importFromDatabase($data, $prefix);
						$model->setCircle($circle);
					} catch (CircleNotFoundException $e) {
					}
					break;

				case CoreRequestBuilder::INITIATOR;
					try {
						$initiator = new Member();
						$initiator->importFromDatabase($data, $prefix);
						$model->setInheritedBy($initiator);
					} catch (MemberNotFoundException $e) {
					}
					break;

				case CoreRequestBuilder::INHERITED_BY;
					try {
						$inheritedBy = new Member();
						$inheritedBy->importFromDatabase($data, $prefix);
						$model->setInheritedBy($inheritedBy);
					} catch (MemberNotFoundException $e) {
					}
					break;

				case CoreRequestBuilder::FILE_CACHE;
					try {
						$fileCache = new FileCacheWrapper();
						$fileCache->importFromDatabase($data, $prefix);
						$model->setFileCache($fileCache);
					} catch (FileCacheNotFoundException $e) {
					}
					break;
			}
		}

	}


	/**
	 * @param Member $member
	 * @param array $data
	 * @param string $prefix
	 */
	public function importCircleFromDatabase(Member $member, array $data, string $prefix) {
		try {
			$circle = new Circle();
			$circle->importFromDatabase($data, $prefix);
			$member->setCircle($circle);
		} catch (CircleNotFoundException $e) {
		}
	}


	/**
	 * @param Member $member
	 * @param array $data
	 * @param string $prefix
	 */
	public function importBasedOnFromDatabase(Member $member, array $data, string $prefix) {
		try {
			$circle = new Circle();
			$circle->importFromDatabase($data, $prefix);
			$member->setBasedOn($circle);
		} catch (CircleNotFoundException $e) {
		}
	}


	/**
	 * @param Member $member
	 * @param array $data
	 * @param string $prefix
	 */
	public function importInheritedByFromDatabase(Member $member, array $data, string $prefix) {
		try {
			$inheritedBy = new FederatedUser();
			$inheritedBy->importFromDatabase($data, $prefix);
			$member->setInheritedBy($inheritedBy);
		} catch (FederatedUserNotFoundException $e) {
		}
	}


	/**
	 * @param Member $member
	 * @param array $data
	 * @param string $prefix
	 */
	public function importInheritanceFromFromDatabase(Member $member, array $data, string $prefix) {
		try {
			$inheritanceFrom = new Member();
			$inheritanceFrom->importFromDatabase($data, $prefix);
			$member->setInheritanceFrom($inheritanceFrom);
		} catch (MemberNotFoundException $e) {
		}
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param array $data
	 * @param string $prefix
	 */
	public function importMembershipFromDatabase(FederatedUser $federatedUser, array $data, string $prefix) {
		try {
			$membership = new Membership();
			$membership->importFromDatabase($data, $prefix);
			$federatedUser->setLink($membership);
		} catch (MembershipNotFoundException $e) {
		}
	}


	/**
	 * @param Circle $circle
	 * @param array $data
	 * @param string $prefix
	 */
	public function importOwnerFromDatabase(Circle $circle, array $data, string $prefix): void {
		try {
			$owner = new Member();
			$owner->importFromDatabase($data, $prefix);
			$circle->setOwner($owner);
		} catch (MemberNotFoundException $e) {
		}
	}


	/**
	 * @param Circle $circle
	 * @param array $data
	 * @param string $prefix
	 */
	public function importInitiatorFromDatabase(Circle $circle, array $data, string $prefix): void {
		try {
			$initiator = new Member();
			$initiator->importFromDatabase($data, $prefix);
			$circle->setInitiator($initiator);
		} catch (MemberNotFoundException $e) {
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

