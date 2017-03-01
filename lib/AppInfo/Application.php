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

namespace OCA\Circles\AppInfo;

use \OCA\Circles\Controller\NavigationController;
use \OCA\Circles\Controller\CirclesController;
use \OCA\Circles\Controller\MembersController;


use \OCA\Circles\Db\CirclesMapper;
use \OCA\Circles\Db\MembersMapper;
use \OCA\Circles\Service\DatabaseService;
use \OCA\Circles\Service\CirclesService;
use \OCA\Circles\Service\MembersService;
use \OCA\Circles\Service\ConfigService;
use \OCA\Circles\Service\MiscService;
use OCP\AppFramework\App;
use OCP\Util;

class Application extends App {

	/** @var string */
	private $appName;
	/** @var string */
	private $navName;
	/** @var string */
	private $navIcon;


	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct('circles', $params);

		$container = $this->getContainer();
		$this->appName = $container->query('AppName');

		/**
		 * Services
		 */
		$container->registerService(
			'MiscService', function ($c) {
			return new MiscService($c->query('Logger'), $c->query('AppName'));
		}
		);


		$container->registerService(
			'ConfigService', function ($c) {
			return new ConfigService(
				$c->query('AppName'), $c->query('CoreConfig'), $c->query('UserId'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'DatabaseService', function ($c) {
			return new DatabaseService(
				$c->query('CirclesMapper'),
				$c->query('MembersMapper')
			);
		}
		);

		$container->registerService(
			'CirclesService', function ($c) {
			return new CirclesService(
				$c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'),
				$c->query('DatabaseService'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'MembersService', function ($c) {
			return new MembersService(
				$c->query('UserId'), $c->query('L10N'),
				$c->query('UserManager'),
				$c->query('ConfigService'),
				$c->query('DatabaseService'),
				$c->query('MiscService')
			);
		}
		);

//		$container->registerService(
//			'ApiService', function ($c) {
//			return new ApiService(
//				$c->query('ConfigService'), $c->query('FileService'), $c->query('MiscService')
//			);
//		}
//		);


		/**
		 * Controllers
		 */
//		$container->registerService(
//			'SettingsController', function ($c) {
//			return new SettingsController(
//				$c->query('AppName'), $2c->query('Request'), $c->query('ConfigService'),
//				$c->query('MiscService')
//			);
//		}
//		);

		$container->registerService(
			'NavigationController', function ($c) {
			return new NavigationController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'CirclesController', function ($c) {
			return new CirclesController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'),
				$c->query('CirclesService'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'MembersController', function ($c) {
			return new MembersController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'),
				$c->query('MembersService'),
				$c->query('MiscService')
			);
		}
		);

		/**
		 * Mapper
		 */
		$container->registerService(
			'CirclesMapper', function ($c) {
			return new CirclesMapper(
				$c->query('ServerContainer')
				  ->getDatabaseConnection(), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'MembersMapper', function ($c) {
			return new MembersMapper(
				$c->query('ServerContainer')
				  ->getDatabaseConnection(), $c->query('MiscService')
			);
		}
		);


		// Translates
		$container->registerService(
			'L10N', function ($c) {
			return $c->query('ServerContainer')
					 ->getL10N($c->query('AppName'));
		}
		);

		/**
		 * Core
		 */
		$container->registerService(
			'Logger', function ($c) {
			return $c->query('ServerContainer')
					 ->getLogger();
		}
		);
		$container->registerService(
			'CoreConfig', function ($c) {
			return $c->query('ServerContainer')
					 ->getConfig();
		}
		);

		$container->registerService(
			'UserId', function ($c) {
			$user = $c->query('ServerContainer')
					  ->getUserSession()
					  ->getUser();

			return is_null($user) ? '' : $user->getUID();
		}
		);

		$container->registerService(
			'UserManager', function ($c) {
			return $c->query('ServerContainer')
					 ->getUserManager();
		}
		);
	}


	public function registerNavigation() {

		if ($this->getContainer()
				 ->query('ConfigService')
				 ->isTeams()
		) {
			$this->navIcon = 'teams.svg';
			$this->navName = \OC::$server->getL10N($this->appName)
										 ->t('Teams');
		} else {
			$this->navIcon = 'circles.svg';
			$this->navName = \OC::$server->getL10N($this->appName)
										 ->t('Circles');
		}
		$this->getContainer()
			 ->getServer()
			 ->getNavigationManager()
			 ->add(
				 function () {
					 return [
						 'id'    => $this->appName,
						 'order' => 5,
						 'href'  => \OC::$server->getURLGenerator()
												->linkToRoute('circles.Navigation.navigate'),
						 'icon'  => \OC::$server->getURLGenerator()
												->imagePath($this->appName, $this->navIcon),
						 'name'  => $this->navName
					 ];
				 }
			 );
	}


//
//	public function registerSettingsAdmin() {
//		\OCP\App::registerAdmin(
//			$this->getContainer()
//				 ->query('AppName'), 'lib/admin'
//		);
//	}
}

