<?php
declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 * @author Daniel Tygel <dtygel@eita.org.br>
 *
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


use Closure;
use OC;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\GlobalScale\GSMount\MountProvider;
use OCA\Circles\Notification\Notifier;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\DavService;
use OCA\Files\App as FilesApp;
use OCP\App\ManagerEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;
use OCP\IServerContainer;
use OCP\Util;


/**
 * Class Application
 *
 * @package OCA\Circles\AppInfo
 */
class Application extends App implements IBootstrap {

	const APP_NAME = 'circles';

	const TEST_URL_ASYNC = '/index.php/apps/circles/admin/testAsync';

	const CLIENT_TIMEOUT = 3;


	/** @var ConfigService */
	private $configService;

	/** @var IAppContainer */
	private $container;


	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct(self::APP_NAME, $params);
	}


	/**
	 * @param IRegistrationContext $context
	 */
	public function register(IRegistrationContext $context): void {
	}


	/**
	 * @param IBootContext $context
	 *
	 * @throws \Throwable
	 */
	public function boot(IBootContext $context): void {
		$manager = $context->getServerContainer()
						   ->getNotificationManager();
		$manager->registerNotifierService(Notifier::class);

		$this->configService = $context->getAppContainer()
									   ->get(ConfigService::class);

		$context->injectFn(Closure::fromCallable([$this, 'registerMountProvider']));
		$context->injectFn(Closure::fromCallable([$this, 'registerHooks']));
		$context->injectFn(Closure::fromCallable([$this, 'registerDavHooks']));

		$context->injectFn(Closure::fromCallable([$this, 'registerNavigation']));
		$context->injectFn(Closure::fromCallable([$this, 'registerFilesNavigation']));
		$context->injectFn(Closure::fromCallable([$this, 'registerFilesPlugin']));
	}


	/**
	 * @param IServerContainer $container
	 *
	 * @throws GSStatusException
	 * @throws QueryException
	 */
	public function registerMountProvider(IServerContainer $container) {
		if (!$this->configService->getGSStatus(ConfigService::GS_ENABLED)) {
			return;
		}
		$mountProviderCollection = $container->getMountProviderCollection();
		$mountProviderCollection->registerProvider($this->container->query(MountProvider::class));
	}


	/**
	 * Register Hooks
	 */
	public function registerHooks() {
		Util::connectHook('OC_User', 'post_deleteUser', '\OCA\Circles\Hooks\UserHooks', 'onUserDeleted');
		Util::connectHook('OC_User', 'post_deleteGroup', '\OCA\Circles\Hooks\UserHooks', 'onGroupDeleted');
	}


	public function registerDavHooks(IServerContainer $container) {
//			/** @var ConfigService $configService */
//
//			$configService = OC::$server->query(ConfigService::class);
		if (!$this->configService->isContactsBackend()) {
			return;
		}

		/** @var DavService $davService */
		$davService = $container->get(DavService::class);

		$event = OC::$server->getEventDispatcher();
		$event->addListener(ManagerEvent::EVENT_APP_ENABLE, [$davService, 'onAppEnabled']);
		$event->addListener('\OCA\DAV\CardDAV\CardDavBackend::createCard', [$davService, 'onCreateCard']);
		$event->addListener('\OCA\DAV\CardDAV\CardDavBackend::updateCard', [$davService, 'onUpdateCard']);
		$event->addListener('\OCA\DAV\CardDAV\CardDavBackend::deleteCard', [$davService, 'onDeleteCard']);
	}


	/**
	 * Register Navigation elements
	 *
	 * @param IServerContainer $container
	 */
	public function registerNavigation(IServerContainer $container) {
		if (!$this->configService->stillFrontEnd()) {
			return;
		}

		$appManager = $container->getNavigationManager();
		$appManager->add(
			function() {
				$urlGen = OC::$server->getURLGenerator();
				$navName = OC::$server->getL10N(self::APP_NAME)
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

	public function registerFilesPlugin(IServerContainer $container) {
		$eventDispatcher = $container->getEventDispatcher();
		$eventDispatcher->addListener(
			'OCA\Files::loadAdditionalScripts',
			function() {
				Circles::addJavascriptAPI();

				Util::addScript('circles', 'files/circles.files.app');
				Util::addScript('circles', 'files/circles.files.list');

				Util::addStyle('circles', 'files/circles.filelist');
			}
		);
	}


	/**
	 *
	 */
	public function registerFilesNavigation() {
		$appManager = FilesApp::getNavigationManager();
		$appManager->add(
			function() {
				$l = OC::$server->getL10N('circles');

				return [
					'id'      => 'circlesfilter',
					'appname' => 'circles',
					'script'  => 'files/list.php',
					'order'   => 25,
					'name'    => $l->t('Shared to Circles'),
				];
			}
		);
	}


}

