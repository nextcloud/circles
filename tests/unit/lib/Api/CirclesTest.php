<?php
/**
 * Circles - Bring cloud-users closer together.
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

namespace OCA\Circles\Tests\Api;

use Exception;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\Exceptions\CircleTypeNotValid;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Tests\Env;


class CirclesTest extends \PHPUnit_Framework_TestCase {

	const NAME_PUBLIC_CIRCLE1 = '_circleNamePublic1';
	const NAME_HIDDEN_CIRCLE1 = '_circleNameHidden1';
	const NAME_PRIVATE_CIRCLE1 = '_circleNamePrivate1';
	const NAME_PERSONAL_CIRCLE1 = '_circleNamePersonal1';

	const NAME_PUBLIC_CIRCLE2 = '_circleNamePublic2';
	const NAME_HIDDEN_CIRCLE2 = '_circleNameHidden2';
	const NAME_PRIVATE_CIRCLE2 = '_circleNamePrivate2';
	const NAME_PERSONAL_CIRCLE2 = '_circleNamePersonal2';


	/** @var Circle[] */
	private $circles;

	protected function setUp() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		$this->circles = array();
		try {
			$this->circles = [
				'Public'   =>
					Circles::createCircle(Circle::CIRCLES_PUBLIC, self::NAME_PUBLIC_CIRCLE1),
				'Hidden'   =>
					Circles::createCircle(Circle::CIRCLES_HIDDEN, self::NAME_HIDDEN_CIRCLE1),
				'Private'  =>
					Circles::createCircle(Circle::CIRCLES_PRIVATE, self::NAME_PRIVATE_CIRCLE1),
				'Personal' =>
					Circles::createCircle(Circle::CIRCLES_PERSONAL, self::NAME_PERSONAL_CIRCLE1)
			];

		} catch (Exception $e) {
			throw $e;
		}

		Env::logout();
	}


	protected function tearDown() {
		Env::setUser(Env::ENV_TEST_OWNER1);
		try {
			foreach ($this->circles AS $circle) {
				Circles::destroyCircle($circle->getId());
			}
		} catch (Exception $e) {
			throw $e;
		}

		Env::logout();
	}


	public function testAddMemberAndLevelToCircles() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		try {
			$this->generateSimpleCircleWithAllLevel($this->circles['Public']->getId());
			$this->generateSimpleCircleWithAllLevel($this->circles['Private']->getId());
			$this->generateSimpleCircleWithAllLevel($this->circles['Hidden']->getId());
		} catch (Exception $e) {
			throw $e;
		}

		Env::logout();
	}


	public function testAddMemberAndLevelToPersonalCircle() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		try {
			$this->generateSimpleCircleWithAllLevel($this->circles['Personal']->getId());
			$this->assertSame(true, false, 'should return an exception');
		} catch (CircleTypeNotValid $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should have returned a CircleTypeNotValid');
		}

		Env::logout();
	}


	public function testCreateCircleWithDuplicate() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		$circleNames = [
			self::NAME_PUBLIC_CIRCLE1,
			self::NAME_HIDDEN_CIRCLE1,
			self::NAME_PRIVATE_CIRCLE1,
			self::NAME_PERSONAL_CIRCLE1
		];

		for ($i = 0; $i < sizeof(Env::listCircleTypes()); $i++) {
			for ($j = 0; $j < sizeof($circleNames); $j++) {
				try {
					Circles::createCircle(Env::listCircleTypes()[$i], $circleNames[$j]);
					$this->assertSame(true, false, 'should return an exception');
				} catch (Exception $e) {
				}
			}
		}
	}

	public function testCreateCircleWithDuplicateFromOthers() {
		Env::setUser(Env::ENV_TEST_OWNER2);

		$circleNames = [
			self::NAME_PUBLIC_CIRCLE1,
			self::NAME_HIDDEN_CIRCLE1,
			self::NAME_PRIVATE_CIRCLE1,
			self::NAME_PERSONAL_CIRCLE1
		];

		for ($i = 0; $i < sizeof(Env::listCircleTypes()); $i++) {
			for ($j = 0; $j < sizeof($circleNames); $j++) {
				if (Env::listCircleTypes()[$i] === Circle::CIRCLES_PERSONAL) {
					try {
						Circles::createCircle(Env::listCircleTypes()[$i], $circleNames[$j]);
					} catch (Exception $e) {
						throw new $e;
					}
				} else {
					try {
						Circles::createCircle(Env::listCircleTypes()[$i], $circleNames[$j]);
						$this->assertSame(true, false, 'should return an exception');
					} catch (Exception $e) {
					}
				}
			}
		}
	}


	public function testRemoveUser() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		$circleNames = [
			self::NAME_PUBLIC_CIRCLE1,
			self::NAME_HIDDEN_CIRCLE1,
			self::NAME_PRIVATE_CIRCLE1,
			self::NAME_PERSONAL_CIRCLE1
		];

		for ($i = 0; $i < sizeof(Env::listCircleTypes()); $i++) {
			for ($j = 0; $j < sizeof($circleNames); $j++) {
				try {
					Circles::createCircle(Env::listCircleTypes()[$i], $circleNames[$j]);
					$this->assertSame(true, false, 'should return an exception');
				} catch (Exception $e) {
				}
			}
		}

	}


	protected function generateSimpleCircleWithAllLevel($circleId) {

		Circles::addMember($circleId, Env::ENV_TEST_ADMIN1);
		Circles::levelMember($circleId, Env::ENV_TEST_ADMIN1, Member::LEVEL_ADMIN);

		Circles::addMember($circleId, Env::ENV_TEST_MODERATOR1);
		Circles::levelMember($circleId, Env::ENV_TEST_MODERATOR1, Member::LEVEL_MODERATOR);

		Circles::addMember($circleId, Env::ENV_TEST_MEMBER1);
		Circles::levelMember($circleId, Env::ENV_TEST_MEMBER1, Member::LEVEL_MEMBER);
	}


//
//	public function testSearch() {
//		Env::setUser(Env::ENV_TEST_USER1);
////		$result = Sharees::search('sea');
////		$this->assertSame(self::CIRCLE_NAME, $result['circles'][0]['label']);
////		$result = Sharees::search('_search');
////		$this->assertSame(self::CIRCLE_NAME, $result['exact']['circles'][0]['label']);
//		Env::logout();
//	}


//
//	const CIRCLE_BASENAME = '_test_';
//
//	/** @var array<int> */
//	private $circleTypes = [];
//
//	/** @var array<Circle> */
//	private $circles;


//	protected function setUp() {
//		$this->circleTypes = [1, 2, 4, 8];
//		$this->circles = $this->createCirclesAs(Env::ENV_TEST_OWNER1);
//	}
//
//
//	protected function tearDown() {
//		try {
//			$this->deleteCirclesAs(Env::ENV_TEST_USER1);
//			$this->assertSame(true, false, 'should return an exception');
//		} catch (MemberDoesNotExistException $e) {
//		} catch (MemberIsNotOwnerException $e) {
//			// TODO: add test on Api/joinCircle
//		} catch (\Exception $e) {
//			$this->assertSame(true, false, 'Should return a valid Exception');
//		}
//
//		$this->assertSame(true, $this->deleteCirclesAs(Env::ENV_TEST_OWNER1));
//	}
//
//
//	public function createCirclesAs($user) {
//		Env::setUser($user);
//		$circles = [];
//		foreach ($this->circleTypes AS $type) {
//			$name = self::CIRCLE_BASENAME . $type;
//			$circles[] = Circles::createCircle($type, $name);
//		}
//		Env::logout();
//
//		return $circles;
//	}
//
//
//	public function deleteCirclesAs($user) {
//		Env::setUser($user);
//		try {
//			foreach ($this->circles AS $circle) {
//				Circles::destroyCircle($circle->getId());
//			}
//		} catch (\Exception $e) {
//			throw $e;
//		}
//		Env::logout();
//
//		return true;
//	}
//
//
//	public function testCirclesAPI() {
//
//		$fullList = $this->listCirclesAs(Env::ENV_TEST_OWNER1);
//		$this->assertCount(4, $fullList);
//
//		foreach ($fullList AS $circle) {
//			$details = $this->detailsCircleAs(Env::ENV_TEST_OWNER1, $circle);
//			$this->assertSame(
//				$details->getOwner()
//						->getUserId(), Env::ENV_TEST_OWNER1
//			);
//		}
//
//		$list = $this->listCirclesAs(Env::ENV_TEST_USER1);
//		$this->assertCount(2, $list);
//
//		// test list hidden with/without full name
//		Env::setUser(Env::ENV_TEST_USER1);
//		$this->assertCount(0, Circles::listCircles(Circle::CIRCLES_HIDDEN, self::CIRCLE_BASENAME));
//		$this->assertCount(
//			1, Circles::listCircles(
//			Circle::CIRCLES_HIDDEN, self::CIRCLE_BASENAME . Circle::CIRCLES_HIDDEN
//		)
//		);
//		Env::logout();
//
//
//		foreach ($fullList AS $circle) {
//			switch ($circle->getType()) {
//				case Circle::CIRCLES_PERSONAL:
//					$this->assertNull($this->detailsCircleAs(Env::ENV_TEST_USER1, $circle));
//					break;
//
//				case Circle::CIRCLES_HIDDEN:
//				case Circle::CIRCLES_PRIVATE:
//				case Circle::CIRCLES_PUBLIC:
//					$details = $this->detailsCircleAs(Env::ENV_TEST_OWNER1, $circle);
//					$this->assertSame(
//						$details->getOwner()
//								->getUserId(), Env::ENV_TEST_OWNER1
//					);
//					break;
//			}
//		}
//	}
//
//
//	public function listCirclesAs($user, $name = '') {
//		Env::setUser($user);
//		$list = Circles::listCircles(Circle::CIRCLES_ALL, $name);
//		Env::logout();
//
//		return $list;
//	}
//
//
//	public function detailsCircleAs($user, $circle) {
//		Env::setUser($user);
//		try {
//			$list = Circles::detailsCircle($circle->getId());
//		} catch (CircleDoesNotExistException $e) {
//			$list = null;
//		}
//		Env::logout();
//
//		return $list;
//	}
}
