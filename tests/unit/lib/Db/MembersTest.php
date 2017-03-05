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


namespace OCA\Circles\Tests\Db;

use OCA\Circles\Db\Members;
use OCA\Circles\Model\Member;


/**
 * Class MembersTest
 *
 * @group DB
 * @package OCA\Circles\Tests\Db
 */
class MembersTest extends \PHPUnit_Framework_TestCase {

	public function testMembers() {

		$date = date("Y-m-d H:i:s");

		$model = Member::fromArray(
			array(
				'circle_id' => 1,
				'user_id'   => 'test',
				'level'     => Member::LEVEL_OWNER,
				'status'    => Member::STATUS_MEMBER,
				'note'      => 'note test',
				'joined'    => $date,
			)
		);

		$item = new Members($model);

		$this->assertSame(1, $item->circleId);
		$this->assertSame('test', $item->userId);
		$this->assertSame(Member::LEVEL_OWNER, $item->level);
		$this->assertSame(Member::STATUS_MEMBER, $item->status);
		$this->assertSame('note test', $item->note);
		$this->assertSame($date, $item->joined);
	}

}