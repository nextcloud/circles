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

namespace OCA\Circles\AppInfo;

use OCA\Circles\Controller\FederatedController;
use OCA\Circles\Controller\GroupsController;
use \OCA\Circles\Controller\NavigationController;
use \OCA\Circles\Controller\CirclesController;
use \OCA\Circles\Controller\MembersController;


use OCA\Circles\Controller\SettingsController;
use OCA\Circles\Controller\SharesController;
use \OCA\Circles\Db\CirclesMapper;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\FederatedLinksRequest;
use \OCA\Circles\Db\MembersMapper;
use OCA\Circles\Events\UserEvents;
use OCA\Circles\Service\BroadcastService;
use \OCA\Circles\Service\DatabaseService;
use \OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\EventsService;
use OCA\Circles\Service\FederatedService;
use OCA\Circles\Service\GroupsService;
use \OCA\Circles\Service\MembersService;
use \OCA\Circles\Service\ConfigService;
use \OCA\Circles\Service\MiscService;
use OCA\Circles\Service\SharesService;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\Util;

class Application extends App {

	/** @var string */
	private $appName;


	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct('circles', $params);

		$container = $this->getContainer();
		$this->appName = $container->query('AppName');

		self::registerServices($container);
		self::registerControllers($container);
		self::registerMappers($container);
		self::registerDatabaseRequesters($container);
		self::registerCores($container);
		self::registerEvents($container);
		self::registerHooks();

		// Translates
		$container->registerService(
			'L10N', function(IAppContainer $c) {
			return $c->query('ServerContainer')
					 ->getL10N($c->query('AppName'));
		}
		);
	}


	/**
	 * Register Containers
	 *
	 * @param $container
	 */
	private function registerServices(IAppContainer &$container) {

		$container->registerService(
			'MiscService', function(IAppContainer $c) {
			return new MiscService($c->query('Logger'), $c->query('AppName'));
		}
		);

		$container->registerService(
			'ConfigService', function(IAppContainer $c) {
			return new ConfigService(
				$c->query('AppName'), $c->query('CoreConfig'), $c->query('UserId'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'DatabaseService', function(IAppContainer $c) {
			return new DatabaseService(
				$c->query('CirclesMapper'), $c->query('MembersMapper')
			);
		}
		);

		$container->registerService(
			'CirclesService', function(IAppContainer $c) {
			return new CirclesService(
				$c->query('UserId'), $c->query('L10N'), $c->query('ConfigService'),
				$c->query('CirclesRequest'), $c->query('DatabaseService'),
				$c->query('EventsService'), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'MembersService', function(IAppContainer $c) {
			return new MembersService(
				$c->query('UserId'), $c->query('L10N'), $c->query('UserManager'),
				$c->query('ConfigService'), $c->query('DatabaseService'),
				$c->query('EventsService'), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'GroupsService', function(IAppContainer $c) {
			return new GroupsService(
				$c->query('L10N'), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'BroadcastService', function(IAppContainer $c) {
			return new BroadcastService(
				$c->query('UserId'), $c->query('ConfigService'), $c->query('CirclesRequest'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'SharesService', function(IAppContainer $c) {
			return new SharesService(
				$c->query('UserId'), $c->query('ConfigService'), $c->query('CirclesRequest'),
				$c->query('BroadcastService'), $c->query('FederatedService'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'EventsService', function(IAppContainer $c) {
			return new EventsService(
				$c->query('UserId'), $c->query('ActivityManager'), $c->query('UserManager'),
				$c->query('CirclesRequest'), $c->query('MiscService')
			);
		}
		);


		$container->registerService(
			'FederatedService', function(IAppContainer $c) {
			return new FederatedService(
				$c->query('UserId'), $c->query('L10N'), $c->query('CirclesRequest'),
				$c->query('ConfigService'), $c->query('CirclesService'),
				$c->query('BroadcastService'), $c->query('FederatedLinksRequest'),
				$c->query('EventsService'), $c->query('ServerHost'), $c->query('HTTPClientService'),
				$c->query('MiscService')
			);
		}
		);
	}


	/**
	 * Register Controllers
	 *
	 * @param $container
	 */
	private static function registerControllers(IAppContainer &$container) {

		$container->registerService(
			'SettingsController', function(IAppContainer $c) {
			return new SettingsController(
				$c->query('AppName'), $c->query('Request'), $c->query('ConfigService'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'NavigationController', function(IAppContainer $c) {
			return new NavigationController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'), $c->query('CirclesService'),
				$c->query('MembersService'), $c->query('GroupsService'), $c->query('SharesService'),
				$c->query('FederatedService'), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'CirclesController', function(IAppContainer $c) {
			return new CirclesController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'), $c->query('CirclesService'),
				$c->query('MembersService'), $c->query('GroupsService'), $c->query('SharesService'),
				$c->query('FederatedService'), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'MembersController', function(IAppContainer $c) {
			return new MembersController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'), $c->query('CirclesService'),
				$c->query('MembersService'), $c->query('GroupsService'), $c->query('SharesService'),
				$c->query('FederatedService'), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'GroupsController', function(IAppContainer $c) {
			return new GroupsController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'), $c->query('CirclesService'),
				$c->query('MembersService'), $c->query('GroupsService'), $c->query('SharesService'),
				$c->query('FederatedService'), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'SharesController', function(IAppContainer $c) {
			return new SharesController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'), $c->query('CirclesService'),
				$c->query('MembersService'), $c->query('GroupsService'), $c->query('SharesService'),
				$c->query('FederatedService'), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'FederatedController', function(IAppContainer $c) {
			return new FederatedController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('ConfigService'), $c->query('CirclesService'),
				$c->query('MembersService'), $c->query('GroupsService'), $c->query('SharesService'),
				$c->query('FederatedService'), $c->query('MiscService')
			);
		}
		);

	}


	/**
	 * Register Request Builders
	 *
	 * @param IAppContainer $container
	 */
	private static function registerDatabaseRequesters(IAppContainer &$container) {

		$container->registerService(
			'CirclesRequest', function(IAppContainer $c) {
			return new CirclesRequest(
				$c->query('L10N'), $c->query('ServerContainer')
									 ->getDatabaseConnection(), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'FederatedLinksRequest', function(IAppContainer $c) {
			return new FederatedLinksRequest(
				$c->query('ServerContainer')
				  ->getDatabaseConnection(), $c->query('MiscService')
			);
		}
		);


	}

	/**
	 * Register Mappers
	 *
	 * @param $container
	 */
	private static function registerMappers(IAppContainer &$container) {

		$container->registerService(
			'CirclesMapper', function(IAppContainer $c) {
			return new CirclesMapper(
				$c->query('UserId'), $c->query('ServerContainer')
									   ->getDatabaseConnection(), $c->query('L10N'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'MembersMapper', function(IAppContainer $c) {
			return new MembersMapper(
				$c->query('ServerContainer')
				  ->getDatabaseConnection(), $c->query('L10N'), $c->query('MiscService')
			);
		}
		);

	}


	/**
	 * Register Cores
	 *
	 * @param $container
	 */
	private static function registerCores(IAppContainer &$container) {

		$container->registerService(
			'Logger', function(IAppContainer $c) {
			return $c->query('ServerContainer')
					 ->getLogger();
		}
		);
		$container->registerService(
			'CoreConfig', function(IAppContainer $c) {
			return $c->query('ServerContainer')
					 ->getConfig();
		}
		);

		$container->registerService(
			'UserId', function(IAppContainer $c) {
			$user = $c->query('ServerContainer')
					  ->getUserSession()
					  ->getUser();

			/** @noinspection PhpUndefinedMethodInspection */
			return is_null($user) ? '' : $user->getUID();
		}
		);

		$container->registerService(
			'UserManager', function(IAppContainer $c) {
			return $c->query('ServerContainer')
					 ->getUserManager();
		}
		);

		$container->registerService(
			'ActivityManager', function(IAppContainer $c) {
			return $c->query('ServerContainer')
					 ->getActivityManager();
		}
		);

		$container->registerService(
			'HTTPClientService', function(IAppContainer $c) {
			return $c->query('ServerContainer')
					 ->getHTTPClientService();
		}
		);


		$container->registerService(
			'ServerHost', function(IAppContainer $c) {
			return $c->query('ServerContainer')
					 ->getRequest()
					 ->getServerHost();
		}
		);

	}


	public function registerHooks() {
		Util::connectHook(
			'OC_User', 'post_deleteUser', '\OCA\Circles\Hooks\UserHooks', 'onUserDeleted'
		);
	}


	public function registerEvents(IAppContainer $container) {
		$container->registerService(
			'UserEvents', function(IAppContainer $c) {
			return new UserEvents($c->query('MembersService'), $c->query('MiscService'));
		}
		);
	}

	/**
	 * Register Navigation Tab
	 */
	public function registerNavigation() {

		$this->getContainer()
			 ->getServer()
			 ->getNavigationManager()
			 ->add(
				 function() {
					 $urlGen = \OC::$server->getURLGenerator();
					 $navName = \OC::$server->getL10N($this->appName)
											->t('Circles');

					 return [
						 'id'    => $this->appName,
						 'order' => 5,
						 'href'  => $urlGen->linkToRoute('circles.Navigation.navigate'),
						 'icon'  => $urlGen->imagePath($this->appName, 'circles.svg'),
						 'name'  => $navName
					 ];
				 }
			 );
	}

	public function registerSettingsAdmin() {
		\OCP\App::registerAdmin(
			$this->getContainer()
				 ->query('AppName'), 'lib/admin'
		);
	}
}

