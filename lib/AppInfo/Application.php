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

use OCA\Circles\Mount\RemoteMountProvider;
use OCP\AppFramework\App;
use OCP\Util;


class Application extends App {

	const APP_NAME = 'circles';

	const REMOTE_URL_PAYLOAD = '/index.php/apps/circles/v1/payload';
	const TEST_URL_ASYNC = '/index.php/apps/circles/admin/testAsync';

	const CLIENT_TIMEOUT = 3;

	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct(self::APP_NAME, $params);
		self::registerHooks();
		self::registerProviders();
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


	public function registerProviders() {
		$container = $this->getContainer();
		$container->getServer()
				  ->getMountProviderCollection()
				  ->registerProvider($container->query(RemoteMountProvider::class));
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

