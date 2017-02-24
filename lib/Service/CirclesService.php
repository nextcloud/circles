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


	public function createCircle($type, $name, &$iError = '') {

		$iError = new iError();

		self::convertTypeStringToBitValue($type);

		if (!$this->configService->isCircleAllowed((int)$type)) {
			$iError->setCode(iError::CIRCLE_CREATION_TYPE_DISABLED)
				   ->setMessage("The creation of this type of circle is not allowed");

			return null;
		}

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
									  ->add($owner, $iError) === true
			) {
				return $circle;
			} else {
				$this->databaseService->getCirclesMapper()
									  ->destroy($circle, $iError);
			}
		}

		return null;
	}


	public function listCircles($type, $name = '', $level = 0, &$iError = '') {

		self::convertTypeStringToBitValue($type);

		if (!$this->configService->isCircleAllowed((int)$type)) {
			$iError = new iError();
			$iError->setCode(iError::CIRCLE_CREATION_TYPE_DISABLED)
				   ->setMessage("The listing of this type of circle is not allowed");

			return null;
		}

		$result = $this->databaseService->getCirclesMapper()
										->findCirclesByUser($this->userId, $type, '', $level);

		$data = [];
		foreach ($result as $item) {
			if ($name === '' || stripos($item->getName(), $name) !== false) {
				$data[] = $item;
			}
		}

		return $data;
	}


	public function detailsCircle($circleid, &$iError) {

		$iError = new iError();

		$circle = $this->databaseService->getCirclesMapper()
										->getDetailsFromCircle($circleid, $this->userId, $iError);

		if ($circle !== null) {

			if ($circle->getUser()
					   ->getLevel() >= Member::LEVEL_MEMBER
			) {
				$members = $this->databaseService->getMembersMapper()
												 ->getMembersFromCircle(
													 $circleid, ($circle->getUser()
																		->getLevel()
																 >= Member::LEVEL_MODERATOR),
													 $iError
												 );
				$circle->setMembers($members);
			}
		}

		return $circle;
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