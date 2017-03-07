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

namespace OCA\Circles\Tests\AppInfo;


use OCA\Circles\AppInfo\Application;
use OCA\Circles\Controller\BaseController;
use OCA\Circles\Db\CirclesMapper;
use OCA\Circles\Db\MembersMapper;
use OCA\Circles\Service\DatabaseService;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\Controller;


/**
 * Class ApplicationTest
 *
 * @group DB
 * @package OCA\Circles\Tests\AppInfo
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase {

	/** @var \OCA\Circles\AppInfo\Application */
	protected $app;

	/** @var \OCP\AppFramework\IAppContainer */
	protected $container;

	protected function setUp() {

		$this->app = new Application();
		$this->container = $this->app->getContainer();
	}

	public function testContainerAppName() {
		$this->app = new Application();
		$this->assertEquals('circles', $this->container->getAppName());
	}


	public function queryData() {
		return array(
			//	array(IL10N::class),

			// controller
			array('NavigationController', BaseController::class),
			array('CirclesController', BaseController::class),
			array('MembersController', BaseController::class),

			// mapper
			array('CirclesMapper', CirclesMapper::class),
			array('MembersMapper', MembersMapper::class),

			// service
			array('DatabaseService', DatabaseService::class),
			array('CirclesService', CirclesService::class),
			array('MembersService', MembersService::class),
			array('ConfigService', ConfigService::class),
			array('MiscService', MiscService::class),
			array('CirclesService', CirclesService::class),
		);
	}

	/**
	 * @dataProvider queryData
	 *
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQuery($service, $expected = null) {
		if ($expected === null) {
			$expected = $service;
		}

		$this->assertTrue($this->container->query($service) instanceof $expected);
	}
}