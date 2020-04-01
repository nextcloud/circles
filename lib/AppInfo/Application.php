<?php
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

use OCA\Circles\Api\v1\Circles;
use OCA\Circles\Notification\Notifier;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\DavService;
use OCA\Circles\Service\GroupsBackendService;
use OCA\Files\App as FilesApp;
use OCP\App\ManagerEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;
use OCP\IGroup;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Util;

require_once __DIR__ . '/../../appinfo/autoload.php';


class Application extends App {

	const APP_NAME = 'circles';

	const REMOTE_URL_PAYLOAD = '/index.php/apps/circles/v1/payload';
	const TEST_URL_ASYNC = '/index.php/apps/circles/admin/testAsync';

	const CLIENT_TIMEOUT = 3;

	/** @var IAppContainer */
	private $container;

	/** @var \OC\ServerServer */
	private $server;

	/** @var IEventDispatcher */
	private $dispatcher;

	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct(self::APP_NAME, $params);
	}

	public function register()
	{
		$this->container = $this->getContainer();
		$this->server = $this->container->getServer();
		$this->dispatcher = $this->server->query(IEventDispatcher::class);

		$manager = $this->server->getNotificationManager();
		$manager->registerNotifierService(Notifier::class);

		$this->registerNavigation();
		$this->registerFilesNavigation();
		$this->registerFilesPlugin();
		$this->registerHooks();
		$this->registerDavHooks();
		$this->registerGroupsBackendHooks();
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
	 * Register Navigation elements
	 */
	public function registerNavigation() {
		/** @var ConfigService $configService */
		try {
			$configService = $this->server->query(ConfigService::class);
		} catch (QueryException $e) {
			return;
		}

		if (!$configService->stillFrontEnd()) {
			return;
		}

		$appManager = $this->container->getServer()
									  ->getNavigationManager();
		$appManager->add(
			function() {
				$urlGen = $this->server->getURLGenerator();
				$navName = $this->server->getL10N(self::APP_NAME)
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

	public function registerFilesPlugin() {
		try {
			/** @var ConfigService $configService */
			$configService = $this->server->query(ConfigService::class);
			if (!$configService->isFilesFilteredCirclesAllowed()) {
				return;
			}
		} catch (QueryException $e) {
			return;
		}

		$this->dispatcher->addListener(
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
		try {
			/** @var ConfigService $configService */
			$configService = $this->server->query(ConfigService::class);
			if (!$configService->isFilesFilteredCirclesAllowed()) {
				return;
			}
		} catch (QueryException $e) {
			return;
		}

		$appManager = FilesApp::getNavigationManager();
		$appManager->add(
			function() {
				$l = $this->server->getL10N('circles');

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


	public function registerDavHooks() {
		try {
			/** @var ConfigService $configService */
			$configService = $this->server->query(ConfigService::class);
			if (!$configService->isContactsBackend()) {
				return;
			}

			/** @var DavService $davService */
			$davService = $this->server->query(DavService::class);
		} catch (QueryException $e) {
			return;
		}

		$this->dispatcher->addListener(ManagerEvent::EVENT_APP_ENABLE, [$davService, 'onAppEnabled']);
		$this->dispatcher->addListener('\OCA\DAV\CardDAV\CardDavBackend::createCard', [$davService, 'onCreateCard']);
		$this->dispatcher->addListener('\OCA\DAV\CardDAV\CardDavBackend::updateCard', [$davService, 'onUpdateCard']);
		$this->dispatcher->addListener('\OCA\DAV\CardDAV\CardDavBackend::deleteCard', [$davService, 'onDeleteCard']);
	}

	public function registerGroupsBackendHooks() {
		try {
			/** @var ConfigService $configService */
			$configService = $this->server->query(ConfigService::class);
			if (!$configService->isGroupsBackend()) {
				return;
			}

			/** @var GroupsBackendService $groupsBackendService */
			$groupsBackendService = $this->server->query(GroupsBackendService::class);
		} catch (QueryException $e) {
			return;
		}

		$this->dispatcher->addListener(ManagerEvent::EVENT_APP_ENABLE, [$groupsBackendService, 'onAppEnabled']);
		$this->dispatcher->addListener('\OCA\Circles::onCircleCreation', [$groupsBackendService, 'onCircleCreation']);
		$this->dispatcher->addListener('\OCA\Circles::onCircleDestruction', [$groupsBackendService, 'onCircleDestruction']);
		$this->dispatcher->addListener('\OCA\Circles::onMemberNew', [$groupsBackendService, 'onMemberNew']);
		$this->dispatcher->addListener('\OCA\Circles::onMemberInvited', [$groupsBackendService, 'onMemberInvited']);
		$this->dispatcher->addListener('\OCA\Circles::onMemberRequesting', [$groupsBackendService, 'onMemberRequesting']);
		$this->dispatcher->addListener('\OCA\Circles::onMemberLeaving', [$groupsBackendService, 'onMemberLeaving']);
		$this->dispatcher->addListener('\OCA\Circles::onMemberLevel', [$groupsBackendService, 'onMemberLevel']);
		$this->dispatcher->addListener('\OCA\Circles::onMemberOwner', [$groupsBackendService, 'onMemberOwner']);
		$this->dispatcher->addListener('\OCA\Circles::onGroupLink', [$groupsBackendService, 'onGroupLink']);
		$this->dispatcher->addListener('\OCA\Circles::onGroupUnlink', [$groupsBackendService, 'onGroupUnlink']);
		$this->dispatcher->addListener('\OCA\Circles::onGroupLevel', [$groupsBackendService, 'onGroupLevel']);
		$this->dispatcher->addListener('\OCA\Circles::onLinkRequestSent', [$groupsBackendService, 'onLinkRequestSent']);
		$this->dispatcher->addListener('\OCA\Circles::onLinkRequestReceived', [$groupsBackendService, 'onLinkRequestReceived']);
		$this->dispatcher->addListener('\OCA\Circles::onLinkRequestRejected', [$groupsBackendService, 'onLinkRequestRejected']);
		$this->dispatcher->addListener('\OCA\Circles::onLinkRequestCanceled', [$groupsBackendService, 'onLinkRequestCanceled']);
		$this->dispatcher->addListener('\OCA\Circles::onLinkRequestAccepted', [$groupsBackendService, 'onLinkRequestAccepted']);
		$this->dispatcher->addListener('\OCA\Circles::onLinkRequestAccepting', [$groupsBackendService, 'onLinkRequestAccepting']);
		$this->dispatcher->addListener('\OCA\Circles::onLinkUp', [$groupsBackendService, 'onLinkUp']);
		$this->dispatcher->addListener('\OCA\Circles::onLinkDown', [$groupsBackendService, 'onLinkDown']);
		$this->dispatcher->addListener('\OCA\Circles::onLinkRemove', [$groupsBackendService, 'onLinkRemove']);
		$this->dispatcher->addListener('\OCA\Circles::onSettingsChange', [$groupsBackendService, 'onSettingsChange']);

		$this->dispatcher->addListener(IGroup::class . '::postAddUser', [$groupsBackendService, 'onGroupPostAddUser']);
		$this->dispatcher->addListener(IGroup::class . '::postRemoveUser', [$groupsBackendService, 'onGroupPostRemoveUser']);
		$this->dispatcher->addListener(IGroup::class . '::postDelete', [$groupsBackendService, 'onGroupPostDelete']);
	}

}
