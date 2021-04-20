<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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
use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Events\CircleMemberAddedEvent;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\GlobalScale\GSMount\MountProvider;
use OCA\Circles\Handlers\WebfingerHandler;
use OCA\Circles\Listeners\Files\AddingMember as ListenerFilesAddingMember;
use OCA\Circles\Listeners\Files\MemberAdded as ListenerFilesMemberAdded;
use OCA\Circles\Listeners\GroupCreated;
use OCA\Circles\Listeners\GroupDeleted;
use OCA\Circles\Listeners\GroupMemberAdded;
use OCA\Circles\Listeners\GroupMemberRemoved;
use OCA\Circles\Listeners\UserCreated;
use OCA\Circles\Listeners\UserDeleted;
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
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IServerContainer;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use Throwable;


require_once __DIR__ . '/../../vendor/autoload.php';


/**
 * Class Application
 *
 * @package OCA\Circles\AppInfo
 */
class Application extends App implements IBootstrap {


	const APP_ID = 'circles';
	const APP_NAME = 'Circles';
	const APP_TOKEN = 'dvG7laa0_UU';

	const APP_SUBJECT = 'http://nextcloud.com/';
	const APP_REL = 'https://apps.nextcloud.com/apps/circles';
	const APP_API = 1;

	const CLIENT_TIMEOUT = 3;


	/** @var ConfigService */
	private $configService;

	/** @var IAppContainer */
	private $container;


	/**
	 * Application constructor.
	 *
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct(self::APP_ID, $params);
	}


	/**
	 * @param IRegistrationContext $context
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);

		// User Events
		$context->registerEventListener(UserCreatedEvent::class, UserCreated::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeleted::class);

		// Group Events
		$context->registerEventListener(GroupCreatedEvent::class, GroupCreated::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeleted::class);
		$context->registerEventListener(UserAddedEvent::class, GroupMemberAdded::class);
		$context->registerEventListener(UserRemovedEvent::class, GroupMemberRemoved::class);

		// Local Events (for Files/Shares management)
		$context->registerEventListener(AddingCircleMemberEvent::class, ListenerFilesAddingMember::class);
		$context->registerEventListener(CircleMemberAddedEvent::class, ListenerFilesMemberAdded::class);

		$context->registerWellKnownHandler(WebfingerHandler::class);
	}


	/**
	 * @param IBootContext $context
	 *
	 * @throws Throwable
	 */
	public function boot(IBootContext $context): void {
		$serverContainer = $context->getServerContainer();

//		/** @var IManager $shareManager */
//		$shareManager = $serverContainer->get(IManager::class);
//		$shareManager->registerShareProvider(ShareByCircleProvider::class);

		$manager = $serverContainer->getNotificationManager();
		$manager->registerNotifierService(Notifier::class);

		$this->configService = $context->getAppContainer()
									   ->get(ConfigService::class);

		$context->injectFn(Closure::fromCallable([$this, 'registerMountProvider']));
//		$context->injectFn(Closure::fromCallable([$this, 'registerDavHooks']));

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
		$mountProviderCollection->registerProvider($container->get(MountProvider::class));
	}


	public function registerDavHooks(IServerContainer $container) {
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


	public function registerFilesPlugin(IServerContainer $container) {
//		$eventDispatcher = $container->getEventDispatcher();
//		$eventDispatcher->addListener(
//			'OCA\Files::loadAdditionalScripts',
//			function() {
//				Circles::addJavascriptAPI();
//
//				Util::addScript('circles', 'files/circles.files.app');
//				Util::addScript('circles', 'files/circles.files.list');
//
//				Util::addStyle('circles', 'files/circles.filelist');
//			}
//		);
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

