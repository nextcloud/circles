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
use OCA\Circles\Events\DestroyingCircleEvent;
use OCA\Circles\Events\Files\CreatingFileShareEvent;
use OCA\Circles\Events\Files\FileShareCreatedEvent;
use OCA\Circles\Events\Files\PreparingFileShareEvent;
use OCA\Circles\Events\PreparingCircleMemberEvent;
use OCA\Circles\Events\RemovingCircleMemberEvent;
use OCA\Circles\Events\RequestingCircleMemberEvent;
use OCA\Circles\Handlers\WebfingerHandler;
use OCA\Circles\Listeners\AccountUpdated;
use OCA\Circles\Listeners\Files\AddingMemberSendMail as ListenerFilesAddingMemberSendMail;
use OCA\Circles\Listeners\Files\CreatingShareSendMail as ListenerFilesCreatingShareSendMail;
use OCA\Circles\Listeners\Files\DestroyingCircle as ListenerFilesDestroyingCircle;
use OCA\Circles\Listeners\Files\ListenerFilesLoadScripts;
use OCA\Circles\Listeners\Files\MemberAddedSendMail as ListenerFilesMemberAddedSendMail;
use OCA\Circles\Listeners\Files\PreparingMemberSendMail as ListenerFilesPreparingMemberSendMail;
use OCA\Circles\Listeners\Files\PreparingShareSendMail as ListenerFilesPreparingShareSendMail;
use OCA\Circles\Listeners\Files\RemovingMember as ListenerFilesRemovingMember;
use OCA\Circles\Listeners\Files\ShareCreatedSendMail as ListenerFilesShareCreatedSendMail;
use OCA\Circles\Listeners\GroupCreated;
use OCA\Circles\Listeners\GroupDeleted;
use OCA\Circles\Listeners\GroupMemberAdded;
use OCA\Circles\Listeners\GroupMemberRemoved;
use OCA\Circles\Listeners\Notifications\RequestingMember as ListenerNotificationsRequestingMember;
use OCA\Circles\Listeners\UserCreated;
use OCA\Circles\Listeners\UserDeleted;
use OCA\Circles\MountManager\CircleMountProvider;
use OCA\Circles\Notification\Notifier;
use OCA\Circles\Search\UnifiedSearchProvider;
use OCA\Circles\Service\ConfigService;
use OCA\Files\App as FilesApp;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\Accounts\UserUpdatedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IServerContainer;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use Throwable;

/**
 * Class Application
 *
 * @package OCA\Circles\AppInfo
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'circles';
	public const APP_NAME = 'Circles';
	public const APP_TOKEN = 'dvG7laa0_UU';

	public const APP_SUBJECT = 'http://nextcloud.com/';
	public const APP_REL = 'https://apps.nextcloud.com/apps/circles';
	public const APP_API = 1;

	public const CLIENT_TIMEOUT = 3;

	private ConfigService $configService;

	public function __construct(array $params = []) {
		parent::__construct(self::APP_ID, $params);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);

		// notification service
		$context->registerNotifierService(Notifier::class);

		// User Events
		$context->registerEventListener(UserCreatedEvent::class, UserCreated::class);
		$context->registerEventListener(UserUpdatedEvent::class, AccountUpdated::class);
		$context->registerEventListener(UserChangedEvent::class, AccountUpdated::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeleted::class);

		// Group Events
		$context->registerEventListener(GroupCreatedEvent::class, GroupCreated::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeleted::class);
		$context->registerEventListener(UserAddedEvent::class, GroupMemberAdded::class);
		$context->registerEventListener(UserRemovedEvent::class, GroupMemberRemoved::class);

		// Local Events (for Files/Shares/Notifications management)
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, ListenerFilesLoadScripts::class);
		$context->registerEventListener(PreparingCircleMemberEvent::class, ListenerFilesPreparingMemberSendMail::class);
		$context->registerEventListener(AddingCircleMemberEvent::class, ListenerFilesAddingMemberSendMail::class);
		$context->registerEventListener(AddingCircleMemberEvent::class, ListenerNotificationsRequestingMember::class);
		$context->registerEventListener(CircleMemberAddedEvent::class, ListenerFilesMemberAddedSendMail::class);
		$context->registerEventListener(PreparingFileShareEvent::class, ListenerFilesPreparingShareSendMail::class);
		$context->registerEventListener(CreatingFileShareEvent::class, ListenerFilesCreatingShareSendMail::class);
		$context->registerEventListener(FileShareCreatedEvent::class, ListenerFilesShareCreatedSendMail::class);
		$context->registerEventListener(RemovingCircleMemberEvent::class, ListenerFilesRemovingMember::class);
		$context->registerEventListener(RequestingCircleMemberEvent::class, ListenerNotificationsRequestingMember::class);
		$context->registerEventListener(DestroyingCircleEvent::class, ListenerFilesDestroyingCircle::class);

		$context->registerSearchProvider(UnifiedSearchProvider::class);
		$context->registerWellKnownHandler(WebfingerHandler::class);
	}


	/**
	 * @param IBootContext $context
	 *
	 * @throws Throwable
	 */
	public function boot(IBootContext $context): void {
		$serverContainer = $context->getServerContainer();

		$this->configService = $context->getAppContainer()
									   ->get(ConfigService::class);

		$context->injectFn(Closure::fromCallable([$this, 'registerMountProvider']));
		$context->injectFn(Closure::fromCallable([$this, 'registerFilesNavigation']));
	}


	public function registerMountProvider(IServerContainer $container) {
		if (!$this->configService->isGSAvailable()) {
			return;
		}

		$mountProviderCollection = $container->get(IMountProviderCollection::class);
		$mountProviderCollection->registerProvider($container->get(CircleMountProvider::class));
	}

	public function registerFilesNavigation() {
		$appManager = FilesApp::getNavigationManager();
		$appManager->add(
			function () {
				$l = OC::$server->getL10N('circles');

				return [
					'id' => 'circlesfilter',
					'appname' => 'circles',
					'script' => 'files/list.php',
					'order' => 25,
					'name' => $l->t('Shared to Circles'),
				];
			}
		);
	}
}
