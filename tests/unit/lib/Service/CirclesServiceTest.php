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

namespace OCA\Circles\Tests\Service;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsOwnerException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\MembersService;


/**
 * Class CirclesServiceTest
 *
 * @group DB
 * @package OCA\Circles\Tests\Service
 */
class CirclesServiceTest extends \PHPUnit_Framework_TestCase {

	const TEST_MEMBER_1 = '_test_1';
	const TEST_MEMBER_2 = '_test_2';

	/** @var \OCA\Circles\Service\CirclesService|\PHPUnit_Framework_MockObject_MockObject */
	protected $circlesService1;

	/** @var \OCA\Circles\Service\CirclesService|\PHPUnit_Framework_MockObject_MockObject */
	protected $circlesService2;

	/** @var \OCA\Circles\Service\MembersService|\PHPUnit_Framework_MockObject_MockObject */
	protected $membersService1;

	/** @var \OCA\Circles\AppInfo\Application */
	protected $app1;

	/** @var \OCA\Circles\AppInfo\Application */
	protected $app2;


	protected function setUp() {

		$this->app1 = new Application();
		$this->app2 = new Application();

		$this->circlesService1 = new CirclesService(
			self::TEST_MEMBER_1,
			$this->getMockBuilder('OCP\IL10N')
				 ->disableOriginalConstructor()
				 ->getMock(),
			$this->app1->getContainer()
					   ->query('ConfigService'),
			$this->app1->getContainer()
					   ->query('DatabaseService'),
			$this->getMockBuilder('OCA\Circles\Service\MiscService')
				 ->disableOriginalConstructor()
				 ->getMock()
		);

		$this->circlesService2 = new CirclesService(
			self::TEST_MEMBER_2,
			$this->getMockBuilder('OCP\IL10N')
				 ->disableOriginalConstructor()
				 ->getMock(),
			$this->app2->getContainer()
					   ->query('ConfigService'),
			$this->app2->getContainer()
					   ->query('DatabaseService'),
			$this->getMockBuilder('OCA\Circles\Service\MiscService')
				 ->disableOriginalConstructor()
				 ->getMock()
		);


		$mockUserManager = $this->getMockBuilder('OCP\IUserManager')
								->disableOriginalConstructor()
								->getMock();

		$mockUserManager->method('userExists')
						->willReturn(true);

		$this->membersService1 = new MembersService(
			self::TEST_MEMBER_1,
			$this->getMockBuilder('OCP\IL10N')
				 ->disableOriginalConstructor()
				 ->getMock(),
			$mockUserManager,
			$this->app2->getContainer()
					   ->query('ConfigService'),
			$this->app2->getContainer()
					   ->query('DatabaseService'),
			$this->getMockBuilder('OCA\Circles\Service\MiscService')
				 ->disableOriginalConstructor()
				 ->getMock()
		);
	}

	/**
	 *
	 */
	public function testIt() {

		$circles = [];

		$circles[] =
			$this->circlesService1->createCircle(Circle::CIRCLES_PERSONAL, '__test_personal');
		$circles[] =
			$this->circlesService1->createCircle(Circle::CIRCLES_PRIVATE, '__test_private');
		$circles[] = $this->circlesService1->createCircle(Circle::CIRCLES_HIDDEN, '__test_hidden');
		$circles[] = $this->circlesService1->createCircle(Circle::CIRCLES_PUBLIC, '__test_public');

		$this->assertCount(
			1, $this->circlesService1->listCircles(Circle::CIRCLES_PERSONAL, '_personal')
		);
		$this->assertCount(
			1, $this->circlesService1->listCircles(Circle::CIRCLES_PRIVATE, '_private')
		);
		$this->assertCount(
			1, $this->circlesService1->listCircles(Circle::CIRCLES_HIDDEN, '_hidden')
		);
		$this->assertCount(
			1, $this->circlesService1->listCircles(Circle::CIRCLES_PUBLIC, '_public')
		);

		$this->assertCount(
			0, $this->circlesService2->listCircles(Circle::CIRCLES_PERSONAL, '_personal')
		);
		$this->assertCount(
			1, $this->circlesService2->listCircles(Circle::CIRCLES_PRIVATE, '_private')
		);
		$this->assertCount(
			0, $this->circlesService2->listCircles(Circle::CIRCLES_HIDDEN, '_hidden')
		);
		$this->assertCount(
			1, $this->circlesService2->listCircles(Circle::CIRCLES_HIDDEN, '__test_hidden')
		);
		$this->assertCount(
			1, $this->circlesService2->listCircles(Circle::CIRCLES_PUBLIC, '_public')
		);


		// testing over-creating circle
		$this->createCircleShouldNotBeCreated(Circle::CIRCLES_PRIVATE, '__test_hidden');
		$this->createCircleShouldNotBeCreated(Circle::CIRCLES_PRIVATE, '__test_public');
		$this->createCircleShouldNotBeCreated(Circle::CIRCLES_PRIVATE, '__test_private');
		$this->createCircleShouldNotBeCreated(Circle::CIRCLES_HIDDEN, '__test_hidden');
		$this->createCircleShouldNotBeCreated(Circle::CIRCLES_HIDDEN, '__test_public');
		$this->createCircleShouldNotBeCreated(Circle::CIRCLES_HIDDEN, '__test_private');
		$this->createCircleShouldNotBeCreated(Circle::CIRCLES_PUBLIC, '__test_hidden');
		$this->createCircleShouldNotBeCreated(Circle::CIRCLES_PUBLIC, '__test_public');
		$this->createCircleShouldNotBeCreated(Circle::CIRCLES_PUBLIC, '__test_private');

		// test creating an identical personal circle (and remove it)
		$this->circlesService2->removeCircle(
			$this->circlesService2->createCircle(Circle::CIRCLES_PERSONAL, '__test_personal')->getId()
		);


		foreach ($circles as $circle) {
			$details1 = $this->circlesService1->detailsCircle($circle->getId());
			$this->assertCount(1, $details1->getMembers());


			// First check on the circle. Can we get details as a non-member
			switch ($circle->getType()) {
				case Circle::CIRCLES_PERSONAL:
					try {
						$this->circlesService2->detailsCircle($circle->getId());
						$this->assertSame(true, false, 'detailsCircle should not be accessible');
					} catch (CircleDoesNotExistException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns CircleDoesNotExistException'
						);
					}
					break;

				case Circle::CIRCLES_HIDDEN:
				case Circle::CIRCLES_PRIVATE:
				case Circle::CIRCLES_PUBLIC:
					$details2 = $this->circlesService2->detailsCircle($circle->getId());
					$this->assertSame(
						self::TEST_MEMBER_1,
						$details2->getOwner()
								 ->getUserId()
					);
					$this->assertSame(null, $details2->getMembers());
					break;
			}


			// testing a re-join on the circle (should fail)
			try {
				$this->circlesService1->joinCircle($circle->getId());
				$this->assertSame(
					true, false, 'joinCircle should returns MemberAlreadyExistsException'
				);
			} catch (MemberAlreadyExistsException $c) {
			} catch (\Exception $e) {
				$this->assertSame(
					true, false, 'Should returns MemberAlreadyExistsException'
				);
			}


			// User2 joining a circle, and leave it
			switch ($circle->getType()) {


				case Circle::CIRCLES_PERSONAL:

					try {
						$this->circlesService2->joinCircle($circle->getId());
						$this->assertSame(
							true, false, 'joinCircle should returns CircleDoesNotExistException'
						);
					} catch (CircleDoesNotExistException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns CircleDoesNotExistException'
						);
					}
					break;

				// joining is easy
				case Circle::CIRCLES_HIDDEN:
				case Circle::CIRCLES_PUBLIC:
					$this->circlesService2->joinCircle($circle->getId());

					// checking we can get members from the circle once joined
					$this->assertCount(
						2, $this->circlesService1->detailsCircle($circle->getId())
												 ->getMembers()
					);
					$this->assertCount(
						2, $this->circlesService2->detailsCircle($circle->getId())
												 ->getMembers()
					);

					// re-join should fail
					try {
						$this->circlesService2->joinCircle($circle->getId());
						$this->assertSame(
							true, false, 'joinCircle should returns MemberAlreadyExistsException'
						);
					} catch (MemberAlreadyExistsException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns MemberAlreadyExistsException'
						);
					}


					// owner should not being able to leave
					try {
						$this->circlesService1->leaveCircle($circle->getId());
						$this->assertSame(
							true, false, 'leaveCircle should returns MemberIsOwnerException'
						);
					} catch (MemberIsOwnerException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns MemberIsOwnerException'
						);
					}


					// now, leaving and check that we left
					$this->circlesService2->leaveCircle($circle->getId());

					$this->assertCount(
						1, $this->circlesService1->detailsCircle($circle->getId())
												 ->getMembers()
					);
					$this->assertSame(
						null, $this->circlesService2->detailsCircle($circle->getId())
													->getMembers()
					);


					// let's see if can leave again:
					try {
						$this->circlesService2->leaveCircle($circle->getId());
						$this->assertSame(
							true, false, 'leaveCircle should returns MemberDoesNotExistException'
						);
					} catch (MemberDoesNotExistException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns MemberDoesNotExistException'
						);
					}


					// re-join should not fail
					$this->circlesService2->joinCircle($circle->getId());

					// checking we can get members from the circle once joined
					$this->assertCount(
						2, $this->circlesService1->detailsCircle($circle->getId())
												 ->getMembers()
					);
					$this->assertCount(
						2, $this->circlesService2->detailsCircle($circle->getId())
												 ->getMembers()
					);

					// re-re-join should now fail
					try {
						$this->circlesService2->joinCircle($circle->getId());
						$this->assertSame(
							true, false, 'joinCircle should returns MemberAlreadyExistsException'
						);
					} catch (MemberAlreadyExistsException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns MemberAlreadyExistsException'
						);
					}
					break;


				// joining needs a confirmation
				case Circle::CIRCLES_PRIVATE:

					// join/leaving 3 times in a row
					for ($i = 0; $i < 3; $i++) {

						// right now, only owner is member
						$this->assertSame(
							null, $this->circlesService2->detailsCircle($circle->getId())
														->getMembers()
						);
						$this->assertCount(
							1, $this->circlesService1->detailsCircle($circle->getId())
													 ->getMembers()
						);

						// request join circle
						$this->circlesService2->joinCircle($circle->getId());

						$this->assertSame(
							null, $this->circlesService2->detailsCircle($circle->getId())
														->getMembers()
						);
						$this->assertCount(
							2, $this->circlesService1->detailsCircle($circle->getId())
													 ->getMembers()
						);

						// sending confirmation:
						$this->membersService1->addMember($circle->getId(), self::TEST_MEMBER_2);

						// now, user2 should be able to access details
						$this->assertCount(
							2, $this->circlesService2->detailsCircle($circle->getId())
													 ->getMembers()
						);

						// leaving and check that we left
						$this->circlesService2->leaveCircle($circle->getId());
					}

					break;
			}
		}


		// listing circles after those join session
		$this->assertCount(
			0, $this->circlesService2->listCircles(Circle::CIRCLES_PERSONAL, '_personal')
		);
		$this->assertCount(
			1, $this->circlesService2->listCircles(Circle::CIRCLES_PRIVATE, '_private')
		);
		$this->assertCount(
			1, $this->circlesService2->listCircles(Circle::CIRCLES_HIDDEN, '_hidden')
		);
		$this->assertCount(
			1, $this->circlesService2->listCircles(Circle::CIRCLES_HIDDEN, '__test_hidden')
		);
		$this->assertCount(
			1, $this->circlesService2->listCircles(Circle::CIRCLES_PUBLIC, '_public')
		);

		foreach ($circles as $circle) {
			$this->circlesService1->removeCircle($circle->getId());

			// Can we leave a non-existing circle ?
			try {
				$this->circlesService1->leaveCircle($circle->getId());
				$this->circlesService2->leaveCircle($circle->getId());
				$this->assertSame(
					true, false, 'leaveCircle should returns CircleDoesNotExistException'
				);
			} catch (CircleDoesNotExistException $c) {
			} catch (\Exception $e) {
				$this->assertSame(
					true, false, 'Should returns CircleDoesNotExistException'
				);
			}
		}

	}


	public function createCircleShouldNotBeCreated($type, $name) {

		try {
			$this->circlesService1->createCircle($type, $name);
			$this->assertSame(true, false, 'Circle should not be created');
		} catch (CircleAlreadyExistsException $c) {
		} catch (\Exception $e) {
			$this->assertSame(true, false, 'Should returns CircleAlreadyExistsException');
		}

		try {
			$this->circlesService2->createCircle($type, $name);
			$this->assertSame(true, false, 'Circle should not be created');
		} catch (CircleAlreadyExistsException $c) {
		} catch (\Exception $e) {
			$this->assertSame(true, false, 'Should returns CircleAlreadyExistsException');
		}
	}
}