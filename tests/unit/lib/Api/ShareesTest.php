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

use OCA\Circles\Api\Circles;
use OCA\Circles\Api\Sharees;
use OCA\Circles\Model\Circle;
use OCA\Circles\Tests\Env;

class ShareesTest extends \PHPUnit_Framework_TestCase {

	const CIRCLE_NAME = '_search';
	private $circle;

	protected function setUp() {
		Env::setUser(Env::ENV_TEST_OWNER1);
		$this->circle = Circles::createCircle(Circle::CIRCLES_PUBLIC, self::CIRCLE_NAME);
		Circles::addMember($this->circle->getId(), Env::ENV_TEST_USER1);
		Env::logout();
	}

	protected function tearDown() {
		Env::setUser(Env::ENV_TEST_OWNER1);
		Circles::deleteCircle($this->circle->getId());
		Env::logout();
	}

	public function testSearch() {
		Env::setUser(Env::ENV_TEST_USER1);
		$result = Sharees::search('sea');
		$this->assertSame(self::CIRCLE_NAME, $result['circles'][0]['label']);
		$result = Sharees::search('_search');
		$this->assertSame(self::CIRCLE_NAME, $result['exact']['circles'][0]['label']);
		Env::logout();
	}


}
