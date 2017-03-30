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


namespace OCA\Circles\Tests\Db;

use OCA\Circles\Db\Members;
use OCA\Circles\Db\CirclesMapper;
use OCA\Circles\Db\MembersMapper;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\TeamExists;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Circle;


/**
 * Class MembersMapperTest
 *
 * @group DB
 * @package OCA\Circles\Tests\Db
 */
class CirclesMapperTest extends \PHPUnit_Framework_TestCase {

	const TEST_CIRCLE_OWNER1 = '_owner1';
	const TEST_CIRCLE_OWNER2 = '_owner2';

	const TEST_CIRCLE_USER1 = '_user1';

	/** @var \OCA\Circles\Db\CirclesMapper|\PHPUnit_Framework_MockObject_MockObject */
	protected $circlesMapper;

	/** @var \OCA\Circles\Db\MembersMapper|\PHPUnit_Framework_MockObject_MockObject */
	protected $membersMapper;

	protected function setUp() {

		$this->circlesMapper = new CirclesMapper(
			\OC::$server->getDatabaseConnection(),
			$this->getMockBuilder('OCA\Circles\Service\MiscService')
				 ->disableOriginalConstructor()
				 ->getMock()
		);

		$this->membersMapper = new MembersMapper(
			\OC::$server->getDatabaseConnection(),
			$this->getMockBuilder('OCA\Circles\Service\MiscService')
				 ->disableOriginalConstructor()
				 ->getMock()
		);
	}


	public function testCircles() {

		$owner1 = new Member($this->l10n);
		$owner1->setUserId(self::TEST_CIRCLE_OWNER1);

		$owner2 = new Member($this->l10n);
		$owner2->setUserId(self::TEST_CIRCLE_OWNER2);

		// creating circles.
		$circles = [];
		$circles[] =
			$this->createCircleTest('__test_personal', Circle::CIRCLES_PERSONAL, $owner1, true);
		$circles[] =
			$this->createCircleTest('__test_private', Circle::CIRCLES_PRIVATE, $owner1, true);
		$circles[] =
			$this->createCircleTest('__test_hidden', Circle::CIRCLES_HIDDEN, $owner1, true);
		$circles[] =
			$this->createCircleTest('__test_public', Circle::CIRCLES_PUBLIC, $owner1, true);

		// checking that we can't create circle with same name
		$this->createCircleTest('__test_private', Circle::CIRCLES_PRIVATE, $owner2, false);
		$this->createCircleTest('__test_hidden', Circle::CIRCLES_PRIVATE, $owner2, false);
		$this->createCircleTest('__test_public', Circle::CIRCLES_PRIVATE, $owner2, false);
		$this->createCircleTest('__test_private', Circle::CIRCLES_HIDDEN, $owner2, false);
		$this->createCircleTest('__test_hidden', Circle::CIRCLES_HIDDEN, $owner2, false);
		$this->createCircleTest('__test_public', Circle::CIRCLES_HIDDEN, $owner2, false);
		$this->createCircleTest('__test_private', Circle::CIRCLES_PUBLIC, $owner2, false);
		$this->createCircleTest('__test_hidden', Circle::CIRCLES_PUBLIC, $owner2, false);
		$this->createCircleTest('__test_public', Circle::CIRCLES_PUBLIC, $owner2, false);


		// counting the circles created from the real owner point of view
		$this->assertCount(
			1, $this->circlesMapper->findCirclesByUser(
			$owner1->getUserId(), Circle::CIRCLES_ALL, '_personal', 0
		)
		);
		$this->assertCount(
			1, $this->circlesMapper->findCirclesByUser(
			$owner1->getUserId(), Circle::CIRCLES_ALL, '_private', 0
		)
		);
		$this->assertCount(
			1, $this->circlesMapper->findCirclesByUser(
			$owner1->getUserId(), Circle::CIRCLES_ALL, '_hidden', 0
		)
		);
		$this->assertCount(
			1, $this->circlesMapper->findCirclesByUser(
			$owner1->getUserId(), Circle::CIRCLES_ALL, '_public', 0
		)
		);

		// counting the circles created from someone else' point of view
		$this->assertCount(
			0, $this->circlesMapper->findCirclesByUser(
			$owner2->getUserId(), Circle::CIRCLES_ALL, '_personal', 0
		)
		);
		$this->assertCount(
			1, $this->circlesMapper->findCirclesByUser(
			$owner2->getUserId(), Circle::CIRCLES_ALL, '_private', 0
		)
		);
		$this->assertCount(
			0, $this->circlesMapper->findCirclesByUser(
			$owner2->getUserId(), Circle::CIRCLES_ALL, '_hidden', 0
		)
		);
		$this->assertCount(
			1, $this->circlesMapper->findCirclesByUser(
			$owner2->getUserId(), Circle::CIRCLES_ALL, '__test_hidden', 0
		)
		);
		$this->assertCount(
			1, $this->circlesMapper->findCirclesByUser(
			$owner2->getUserId(), Circle::CIRCLES_ALL, '_public', 0
		)
		);


		// create user (for hidden circle)
		$user1 = new Member($this->l10n);
		$user1->setUserId(self::TEST_CIRCLE_USER1);
		$user1->setCircleId(
			self::getTestFromCircles('__test_hidden', $circles)
				->getId()
		);
		$user1->setLevel(Member::LEVEL_MEMBER);
		$user1->setStatus(Member::STATUS_MEMBER);

		// checking some access from this user point of view (before adding him to DB)
		$this->assertCount(
			0, $this->circlesMapper->findCirclesByUser(
			$user1->getUserId(), Circle::CIRCLES_ALL, '_hidden', 0
		)
		);

		// add User1 to DB
		$this->membersMapper->add($user1);

		// checking access after adding user (as member) to DB
		$this->assertCount(
			1, $this->circlesMapper->findCirclesByUser(
			$user1->getUserId(), Circle::CIRCLES_ALL, '_hidden', 0
		)
		);


		// create user (for personal circle)
		$user1 = new Member($this->l10n);
		$user1->setUserId(self::TEST_CIRCLE_USER1);
		$user1->setCircleId(
			self::getTestFromCircles('__test_personal', $circles)
				->getId()
		);
		$user1->setLevel(Member::LEVEL_MEMBER);
		$user1->setStatus(Member::STATUS_MEMBER);

		// checking some access from this user point of view (before adding him to DB)
		$this->assertCount(
			0, $this->circlesMapper->findCirclesByUser(
			$user1->getUserId(), Circle::CIRCLES_ALL, '_personal', 0
		)
		);

		// add User1 to DB
		$this->membersMapper->add($user1);

		// checking non access after adding user (as member) to DB
		$this->assertCount(
			0, $this->circlesMapper->findCirclesByUser(
			$user1->getUserId(), Circle::CIRCLES_ALL, '_personal', 0
		)
		);


		//
		// we delete everything
		foreach ($circles AS $circle) {
			$this->membersMapper->removeAllFromCircle($circle->getId());
			$this->circlesMapper->destroy($circle->getId());
		}

	}


	private static function getTestFromCircles($name, $circles) {

		foreach ($circles AS $circle) {
			if ($circle->getName() === $name) {
				return $circle;
			}
		}

		return null;
	}


	private function createCircleTest($name, $type, $owner, $create = true) {

		$circle = new Circle();
		$circle->setName($name);
		$circle->setDescription('description');
		$circle->setType($type);
		$circle->setOwner($owner);

		if ($create === true) {
			$this->circlesMapper->create($circle, $owner);
			$this->membersMapper->add($owner);
		}

		try {
			$this->circlesMapper->create($circle, $owner);
			$this->assertSame(true, false, 'Circle should already exists');
		} catch (CircleAlreadyExistsException $c) {
		} catch (\Exception $e) {
			$this->assertSame(true, false, 'Should returns CircleAlreadyExistsException');
		}

		return $circle;
	}

}