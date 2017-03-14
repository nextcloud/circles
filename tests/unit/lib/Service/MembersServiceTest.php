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
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\MembersService;


/**
 * Class CirclesServiceTest
 *
 * @group DB
 * @package OCA\Circles\Tests\Service
 */
class MembersServiceTest extends \PHPUnit_Framework_TestCase {

	const TEST_MEMBER_1 = '_test_1';
	const TEST_MEMBER_2 = '_test_2';

	/** @var \OCA\Circles\Service\CirclesService|\PHPUnit_Framework_MockObject_MockObject */
	protected $circlesService1;

	/** @var \OCA\Circles\Service\CirclesService|\PHPUnit_Framework_MockObject_MockObject */
	protected $circlesService2;

	/** @var \OCA\Circles\Service\MembersService|\PHPUnit_Framework_MockObject_MockObject */
	protected $membersService1;

	/** @var \OCA\Circles\Service\MembersService|\PHPUnit_Framework_MockObject_MockObject */
	protected $membersService2;

	/** @var \OCA\Circles\AppInfo\Application */
	protected $app1;

	/** @var \OCA\Circles\AppInfo\Application */
	protected $app2;


	protected function setUp() {

		$this->app1 = new Application();
		$this->app2 = new Application();


		$mockUserManager = $this->getMockBuilder('OCP\IUserManager')
								->disableOriginalConstructor()
								->getMock();

		$mockUserManager->method('userExists')
						->willReturn(true);

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
			$this->app1->getContainer()
					   ->query('ConfigService'),
			$this->app1->getContainer()
					   ->query('DatabaseService'),
			$this->getMockBuilder('OCA\Circles\Service\MiscService')
				 ->disableOriginalConstructor()
				 ->getMock()
		);

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

		$this->membersService2 = new MembersService(
			self::TEST_MEMBER_2,
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


	public function testMemberService() {

		$circles = [];

		$circles[] =
			$this->circlesService1->createCircle(Circle::CIRCLES_PERSONAL, '__test_personal');
		$circles[] =
			$this->circlesService1->createCircle(Circle::CIRCLES_PRIVATE, '__test_private');
		$circles[] = $this->circlesService1->createCircle(Circle::CIRCLES_HIDDEN, '__test_hidden');
		$circles[] = $this->circlesService1->createCircle(Circle::CIRCLES_PUBLIC, '__test_public');


		foreach ($circles as $circle) {

			$details1 = $this->circlesService1->detailsCircle($circle->getId());
			$this->assertCount(1, $details1->getMembers());


			switch ($circle->getType()) {
				case Circle::CIRCLES_PERSONAL:
				case Circle::CIRCLES_PUBLIC:
				case Circle::CIRCLES_HIDDEN:

					// let's try to add a member from the user pov
					try {
						$this->membersService2->addMember($circle->getId(), self::TEST_MEMBER_2);
						$this->assertSame(true, false, 'addMember should not be accessible');
					} catch (MemberDoesNotExistException $c) {
					} catch (CircleDoesNotExistException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false,
							'Should returns MemberDoesNotExistException or CircleDoesNotExistException '
							. $e->getMessage()
						);

					}

					// adding the member from the owner pov
					$this->membersService1->addMember($circle->getId(), self::TEST_MEMBER_2);

					// let's check that we added the member to the circle
					$this->assertCount(
						2, $this->circlesService1->detailsCircle($circle->getId())
												 ->getMembers()
					);

					// adding the member again
					try {
						$this->membersService1->addMember($circle->getId(), self::TEST_MEMBER_2);
						$this->assertSame(true, false, 'addMember should not be accessible');
					} catch (MemberAlreadyExistsException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns MemberAlreadyExistsException'
						);
					}

					// let's try to add a member from the user pov
					try {
						$this->membersService2->addMember($circle->getId(), self::TEST_MEMBER_2);
						$this->assertSame(true, false, 'addMember should not be accessible');
					} catch (MemberIsNotModeratorException $c) {
					} catch (CircleDoesNotExistException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns MemberIsNotModeratorException'
						);
					}


					// remove member (user POV):
					try {
						$this->membersService2->removeMember($circle->getId(), self::TEST_MEMBER_2);
						$this->assertSame(true, false, 'addMember should not be accessible');
					} catch (MemberIsNotModeratorException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns MemberIsNotModeratorException'
						);
					}


					// we remove the member and check that he is not in the member list anymore
					$this->membersService1->removeMember($circle->getId(), self::TEST_MEMBER_2);
					$this->assertCount(
						1, $this->circlesService1->detailsCircle($circle->getId())
												 ->getMembers()
					);


					// we try to remove the member again to get an exception
					try {
						$this->membersService2->removeMember($circle->getId(), self::TEST_MEMBER_2);
						$this->assertSame(true, false, 'removeMember should not be accessible');
					} catch (MemberDoesNotExistException $c) {
					} catch (\Exception $e) {
						$this->assertSame(
							true, false, 'Should returns MemberDoesNotExistException'
						);
					}

					break;

				case Circle::CIRCLES_PRIVATE:

					// let cycle 3 times this operation.
					for ($i = 0; $i < 3; $i++) {

						// we invite user2
						$this->membersService1->addMember($circle->getId(), self::TEST_MEMBER_2);

						// we check that user2 have no right on the circle
						$this->assertCount(
							2, $this->circlesService1->detailsCircle($circle->getId())
													 ->getMembers()
						);
						$this->assertSame(
							null, $this->circlesService2->detailsCircle($circle->getId())
														->getMembers()
						);

						// user2 accept the circle
						$this->circlesService2->joinCircle($circle->getId());

						// user2 can list members
						$this->assertCount(
							2, $this->circlesService2->detailsCircle($circle->getId())
													 ->getMembers()
						);

						// user2 leave the circle
						$this->circlesService2->leaveCircle($circle->getId());
					}

					break;

			}
		}


		foreach ($circles as $circle) {
			$this->circlesService1->removeCircle($circle->getId());
		}
	}

}