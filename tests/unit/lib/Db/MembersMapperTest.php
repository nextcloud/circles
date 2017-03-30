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

use OCA\Circles\Db\MembersMapper;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Model\Member;


/**
 * Class MembersMapperTest
 *
 * @group DB
 * @package OCA\Circles\Tests\Db
 */
class MembersMapperTest extends \PHPUnit_Framework_TestCase {

	const TEST_MEMBER_1 = '_test_1';
	const TEST_MEMBER_2 = '_test_2';

	const TEST_CIRCLE_ID = 9999998;

	/** @var \OCA\Circles\Db\MembersMapper|PHPUnit_Framework_MockObject_MockObject */
	protected $membersMapper;

	protected function setUp() {
//		$this->db = \OC::$server->getDatabaseConnection();
//		$this->miscService = $this->getMockBuilder('OCA\Circles\Service\MiscService')
//								  ->disableOriginalConstructor()
//								  ->getMock();

		$this->membersMapper = new MembersMapper(
			\OC::$server->getDatabaseConnection(),
			$this->getMockBuilder('OCA\Circles\Service\MiscService')
				 ->disableOriginalConstructor()
				 ->getMock()
		);
	}


	public function testMembers() {

		$member1 = new Member($this->l10n);
		$member1->fromArray(
			array(
				'circle_id' => self::TEST_CIRCLE_ID,
				'user_id'   => self::TEST_MEMBER_1,
				'level'     => Member::LEVEL_MEMBER,
				'status'    => Member::STATUS_MEMBER,
				'note'      => 'test note',
			)
		);

		$member2 = new Member($this->l10n);
		$member2->fromArray(
			array(
				'circle_id' => self::TEST_CIRCLE_ID,
				'user_id'   => self::TEST_MEMBER_2,
				'level'     => Member::LEVEL_OWNER,
				'status'    => Member::STATUS_MEMBER,
				'note'      => 'test note',
			)
		);


		$this->membersMapper->remove($member1);
		$this->membersMapper->remove($member2);

		$this->membersMapper->add($member1);
		$this->membersMapper->add($member2);

		try {
			$this->membersMapper->add($member1);
			$this->assertSame(true, false, 'Member should already exists');
		} catch (MemberAlreadyExistsException $m) {
		} catch (\Exception $e) {
			$this->assertSame(true, false, 'Should returns MemberAlreadyExistsException');
		}


		//
		// get result from database (as moderator)
		$result1 =
			$this->membersMapper->getMemberFromCircle(
				self::TEST_CIRCLE_ID, $member1->getUserId(), true
			);

		$this->assertSame($result1->getCircleId(), $member1->getCircleId());
		$this->assertSame($result1->getUserID(), $member1->getUserID());
		$this->assertSame($result1->getLevel(), $member1->getLevel());
		$this->assertSame($result1->getStatus(), $member1->getStatus());
		$this->assertSame($result1->getNote(), $member1->getNote());

		// non-moderator should not get access to note
		$result1 =
			$this->membersMapper->getMemberFromCircle(
				self::TEST_CIRCLE_ID, $member1->getUserId(), false
			);
		$this->assertSame('', $result1->getNote());


		//
		// list of members from a circle (moderator)
		$membersList = $this->membersMapper->getMembersFromCircle(self::TEST_CIRCLE_ID, $member2);
		$this->assertCount(2, $membersList);
		if (sizeof($membersList) > 0) {
			$this->assertSame('test note', $membersList[0]->getNote());
		}

		// list of members from a circle (mom-moderator)
		$membersList = $this->membersMapper->getMembersFromCircle(self::TEST_CIRCLE_ID, $member1);
		$this->assertCount(2, $membersList);
		if (sizeof($membersList) > 0) {
			$this->assertSame('', $membersList[0]->getNote());
		}


		//
		// Update members
		$member1->setLevel(Member::LEVEL_NONE);
		$member1->setStatus(Member::STATUS_NONMEMBER);
		$this->membersMapper->editMember($member1);

		$result1 =
			$this->membersMapper->getMemberFromCircle(
				self::TEST_CIRCLE_ID, $member1->getUserId()
			);

		// verify
		$this->assertSame($result1->getCircleId(), $member1->getCircleId());
		$this->assertSame($result1->getUserID(), $member1->getUserID());
		$this->assertSame($result1->getLevel(), Member::LEVEL_NONE);
		$this->assertSame($result1->getStatus(), Member::STATUS_NONMEMBER);

		$this->assertCount(
			1, $this->membersMapper->getMembersFromCircle(self::TEST_CIRCLE_ID, $member2)
		);

		$this->membersMapper->remove($member1);
		$this->membersMapper->remove($member2);

		//$this->getMemberFromCircle('test');
	}

}