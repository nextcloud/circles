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
use OCA\Circles\Controller\NavigationController;
use OCA\Circles\Controller\CirclesController;
use OCA\Circles\Controller\MembersController;


use OCA\Circles\Controller\SettingsController;
use OCA\Circles\Controller\SharesController;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\FederatedLinksRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Events\UserEvents;
use OCA\Circles\Service\BroadcastService;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\EventsService;
use OCA\Circles\Service\FederatedLinkService;
use OCA\Circles\Service\GroupsService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\SearchService;
use OCA\Circles\Service\SharesService;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\Util;

class Application extends App {

	const APP_NAME = 'circles';

	const REMOTE_URL_LINK = '/index.php/apps/circles/v1/link';
	const REMOTE_URL_PAYLOAD = '/index.php/apps/circles/v1/payload';

	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct(self::APP_NAME, $params);

		$container = $this->getContainer();

		self::registerEvents($container);
		self::registerHooks();
	}


	/**
	 * Register Hooks
	 */
	public function registerHooks() {
		Util::connectHook(
			'OC_User', 'post_deleteUser', '\OCA\Circles\Hooks\UserHooks', 'onUserDeleted'
		);
		Util::connectHook(
			'OC_User', 'post_deleteGroup', '\OCA\Circles\Hooks\UserHooks', 'onGroupDeleted'
		);
	}


	/**
	 * Register Events
	 *
	 * @param IAppContainer $container
	 */
	public function registerEvents(IAppContainer $container) {
//		$container->registerService(
//			'UserEvents', function(IAppContainer $c) {
//			return new UserEvents(
//				$c->query('MembersService'), $c->query('GroupsService'), $c->query('MiscService')
//			);
//		}
//		);
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
					 $navName = \OC::$server->getL10N(self::APP_NAME)
											->t('Circles');

					 return [
						 'id'    => self::APP_NAME,
						 'order' => 5,
						 'href'  => $urlGen->linkToRoute('circles.Navigation.navigate'),
						 'icon'  => $urlGen->imagePath(self::APP_NAME, 'circles.svg'),
						 'name'  => $navName
					 ];
				 }
			 );
	}

	public function registerSettingsAdmin() {
		\OCP\App::registerAdmin(self::APP_NAME, 'lib/admin');
	}
}

