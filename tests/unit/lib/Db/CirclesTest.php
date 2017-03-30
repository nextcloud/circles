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

use OCA\Circles\Db\Circles;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;


/**
 * Class CirclesTest
 *
 * @group DB
 * @package OCA\Circles\Tests\Db
 */
class CirclesTest extends \PHPUnit_Framework_TestCase {


	public function testCircles() {

		$date = date("Y-m-d H:i:s");

		$owner = new Member($this->l10n);
		$owner->setUserId('owner');
		$user = new Member($this->l10n);
		$user->setUserID('user');

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
			)
		)
			  ->setMembers($members);

		$item = new Circles($model);

		$this->assertSame(1, $item->id);
		$this->assertSame('test', $item->name);

		$this->assertSame('description', $item->description);
		$this->assertSame(Circle::CIRCLES_ALL, $item->type);
		$this->assertSame($date, $item->creation);
		$this->assertSame($members, $item->members);
	}


}