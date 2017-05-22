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


use OCA\Circles\Controller\NavigationController;
use OCA\Circles\Tests\Env;

class NavigationControllerTest extends \PHPUnit_Framework_TestCase {


	/** @var array<Circle> */
	private $navController;

	protected function setUp() {

		Env::setUser(Env::ENV_TEST_USER1);
		$this->navController = new NavigationController(


			'circles', $this->getMockBuilder('\OCP\IRequest')
							->getMock(), Env::ENV_TEST_USER1, $this->getMockBuilder('\OCP\IL10N')
																   ->getMock(),
			$this->getMockBuilder('\OCA\Circles\Service\ConfigService')
				 ->disableOriginalConstructor()
				 ->getMock(), $this->getMockBuilder('\OCA\Circles\Service\CirclesService')
								   ->disableOriginalConstructor()
								   ->getMock(),
			$this->getMockBuilder('\OCA\Circles\Service\MembersService')
				 ->disableOriginalConstructor()
				 ->getMock(), $this->getMockBuilder('\OCA\Circles\Service\SharesService')
								   ->disableOriginalConstructor()
								   ->getMock(),
			$this->getMockBuilder('\OCA\Circles\Service\FederatedService')
				 ->disableOriginalConstructor()
				 ->getMock(),
			$this->getMockBuilder('\OCA\Circles\Service\MiscService')
				 ->disableOriginalConstructor()
				 ->getMock()

		);
	}

	protected function tearDrop() {
		Env::logout();
	}


	public function testNavigate() {
		$this->assertNotNull($this->navController->navigate());
	}

}
