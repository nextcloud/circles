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

namespace OCA\Circles\Tests\Model;

use OCA\Circles\Model\Circle;
use \OCA\Circles\Model\Member;


/**
 * Class CircleTest
 *
 * @package OCA\Circles\Tests\Model
 */
class CircleTest extends \PHPUnit_Framework_TestCase {

	public function testConst() {

		$this->assertSame(1, Circle::CIRCLES_PERSONAL);
		$this->assertSame(2, Circle::CIRCLES_HIDDEN);
		$this->assertSame(4, Circle::CIRCLES_PRIVATE);
		$this->assertSame(8, Circle::CIRCLES_PUBLIC);

		$this->assertSame(
			Circle::CIRCLES_PERSONAL + Circle::CIRCLES_HIDDEN + Circle::CIRCLES_PRIVATE
			+ Circle::CIRCLES_PUBLIC,
			Circle::CIRCLES_ALL
		);
	}

	public function testModel() {

		$date = date("Y-m-d H:i:s");
		$joined = date("H:i:s Y-m-d");

		$owner = new Member();
		$owner->setUserId('owner');
		$user = new Member();
		$user->setUserID('user');
		$user->setStatus(Member::STATUS_MEMBER);
		$user->setLevel(Member::LEVEL_MEMBER);
		$user->setJoined($joined);

		$members = array($owner, $user);


		$model = new Circle();
		$model->fromArray(
			array(
				'id'          => 1,
				'name'        => 'test',
				'description' => 'description',
				'type'        => Circle::CIRCLES_ALL,
				'creation'    => $date,
				'count'       => sizeof($members),
				'owner'       => $owner->getUserId(),
				'status'      => $user->getStatus(),
				'level'       => $user->getLevel(),
				'joined'      => $user->getJoined()
			)
		)
			  ->setMembers($members);

		$this->assertSame(1, $model->getId());
		$this->assertSame(
			'owner', $model->getOwner()
						   ->getUserId()
		);
		$this->assertSame(
			Member::STATUS_MEMBER, $model->getUser()
										 ->getStatus()
		);
		$this->assertSame(
			Member::LEVEL_MEMBER, $model->getUser()
										->getLevel()
		);
		$this->assertSame(
			$joined, $model->getUser()
						   ->getJoined()
		);
		$this->assertSame('description', $model->getDescription());
		$this->assertSame(Circle::CIRCLES_ALL, $model->getType());
		$this->assertSame($date, $model->getCreation());
		$this->assertSame($members, $model->getMembers());
		//	$this->assertSame(2, $model->getCount());
	}
}
