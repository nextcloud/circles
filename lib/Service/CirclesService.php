<?php
/**
 * Circles - bring cloud-users closer
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

namespace OCA\Circles\Service;


use \OCA\Circles\Model\Circle;
use \OCA\Circles\Model\iError;
use \OCA\Circles\Model\Member;
use OCP\IL10N;

class CirclesService {

	private $userId;
	private $l10n;
	private $configService;
	private $databaseService;
	private $miscService;

	public function __construct(
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		DatabaseService $databaseService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->databaseService = $databaseService;
		$this->miscService = $miscService;
	}


	public function createCircle($name, $type) {

		self::convertTypeStringToBitValue($type);

		if (!$this->configService->isCircleAllowed((int)$type)) {
			$iError = new iError();
			$iError->setCode(iError::CIRCLE_CREATION_TYPE_DISABLED)
				   ->setMessage("The creation of this type of circle is not allowed");

			return [
				'name'   => $name,
				'type'   => $type,
				'status' => 0,
				'error'  => $iError->toArray()
			];
		}

		$iError = new iError();

		$owner = new Member();
		$owner->setUserId($this->userId)
			  ->setStatus(Member::STATUS_MEMBER);

		$circle = new Circle();
		$circle->setName($name)
			   ->setType($type)
			   ->setMembers([$owner]);

		if ($this->databaseService->getCirclesMapper()
								  ->create($circle, $owner, $iError) === true
		) {
			if ($this->databaseService->getMembersMapper()
									  ->create($owner, $iError) === true
			) {
				return [
					'name'   => $name,
					'type'   => $type,
					'status' => 1,
					'error'  => ''
				];

			} else {
				$this->databaseService->getCirclesMapper()
									  ->destroy($circle, $iError);
			}
		}

		return [
			'name'   => $name,
			'type'   => $type,
			'status' => 0,
			'error'  => $iError->toArray()
		];
	}


	public function listCircles($type) {

		self::convertTypeStringToBitValue($type);

		if (!$this->configService->isCircleAllowed((int)$type)) {
			$iError = new iError();
			$iError->setCode(iError::CIRCLE_CREATION_TYPE_DISABLED)
				   ->setMessage("The listing of this type of circle is not allowed");

			return [
				'type'   => $type,
				'status' => 0,
				'error'  => $iError->toArray()
			];
		}

		$iError = new iError();

		$user = new Member();
		$user->setUserId($this->userId);

		$data = $this->databaseService->getCirclesMapper()
									  ->findCirclesByUser($this->userId, $type, 0);

		return [
			'type'   => $type,
			'data'   => $data,
			'status' => 1,
			'error'  => $iError->toArray()
		];
	}


	public function detailsCircle($circleid) {

		$iError = new iError();

		$circle = $this->databaseService->getCirclesMapper()
										->getDetailsFromCircle($this->userId, $circleid, $iError);

		if ($circle->getUser()
				   ->getLevel() >= Member::LEVEL_MEMBER
		) {
			$members = $this->databaseService->getMembersMapper()
											 ->getMembersFromCircle(
												 $circleid, $iError
											 );
			$circle->setMembers($members);
		}

		return [
			'circle_id' => $circleid,
			'details'   => $circle,
			'status'    => 1,
			'error'     => $iError->toArray()
		];

	}


	public static function convertTypeStringToBitValue(&$type) {
		if (strtolower($type) === 'personal') {
			$type = Circle::CIRCLES_PERSONAL;
		}
		if (strtolower($type) === 'hidden') {
			$type = Circle::CIRCLES_HIDDEN;
		}
		if (strtolower($type) === 'private') {
			$type = Circle::CIRCLES_PRIVATE;
		}
		if (strtolower($type) === 'public') {
			$type = Circle::CIRCLES_PUBLIC;
		}
		if (strtolower($type) === 'all') {
			$type = Circle::CIRCLES_ALL;
		}
	}

}