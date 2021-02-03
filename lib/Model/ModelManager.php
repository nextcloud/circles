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
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MemberService;

/**
 * Class ModelManager
 *
 * @package OCA\Circles\Model
 */
class ModelManager {


	const TYPES_SHORT = 1;
	const TYPES_LONG = 2;


	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;

	/** @var MembershipRequest */
	private $membershipRequest;


	/** @var bool */
	private $fullDetails = false;


	/**
	 * ModelManager constructor.
	 *
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 * @param MembershipRequest $membershipRequest
	 */
	public function __construct(
		MemberService $memberService, ConfigService $configService, MembershipRequest $membershipRequest
	) {
		$this->memberService = $memberService;
		$this->configService = $configService;
		$this->membershipRequest = $membershipRequest;
	}


	/**
	 * @param Circle $circle
	 */
	public function getMembers(Circle $circle): void {
		$members = $this->memberService->getMembers($circle->getId());
		$circle->setMembers($members);
	}


	/**
	 * @param FederatedUser $federatedUser
	 */
	public function getMemberships(FederatedUser $federatedUser): void {
		$memberships = $this->membershipRequest->getMemberships($federatedUser->getSingleId());
		$federatedUser->setMemberships($memberships);
	}


	/**
	 * @param Circle $circle
	 */
	public function memberOf(Circle $circle) {
		//$members = $this->memberService->getMembers($circle->getId());
		$circle->setMemberOf([]);
	}


	/**
	 * @param Member $member
	 * @param array $data
	 */
	public function importCircleFromDatabase(Member $member, array $data) {
		try {
			$circle = new Circle();
			$circle->importFromDatabase($data, CoreQueryBuilder::PREFIX_CIRCLE);
			$member->setCircle($circle);
		} catch (CircleNotFoundException $e) {
		}
	}

	/**
	 * @param Circle $circle
	 * @param array $data
	 */
	public function importOwnerFromDatabase(Circle $circle, array $data): void {
		try {
			$owner = new Member();
			$owner->importFromDatabase($data, CoreQueryBuilder::PREFIX_OWNER);
			$circle->setOwner($owner);
		} catch (MemberNotFoundException $e) {
		}
	}


	/**
	 * @param Circle $circle
	 * @param array $data
	 */
	public function importInitiatorFromDatabase(Circle $circle, array $data): void {
		try {
			$initiator = new Member();
			$initiator->importFromDatabase($data, CoreQueryBuilder::PREFIX_INITIATOR);
			$circle->setInitiator($initiator);
		} catch (MemberNotFoundException $e) {
		}
	}


	/**
	 * @param Circle $circle
	 * @param int $display
	 *
	 * @return array
	 */
	public function getCircleTypes(Circle $circle, int $display = self::TYPES_LONG): array {
		$types = [];
		foreach (array_keys(Circle::$DEF) as $def) {
			if ($circle->isConfig($def)) {
				list($short, $long) = explode('|', Circle::$DEF[$def]);
				switch ($display) {

					case self::TYPES_SHORT:
						$types[] = $short;
						break;

					case self::TYPES_LONG:
						$types[] = $long;
						break;
				}
			}
		}

		return $types;
	}


	/**
	 * @return string
	 */
	public function getLocalInstance(): string {
		return $this->configService->getLocalInstance();
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

