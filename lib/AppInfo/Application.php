<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\AppInfo;

use Closure;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Dashboard\TeamDashboardWidget;
use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Events\CircleMemberAddedEvent;
use OCA\Circles\Events\DestroyingCircleEvent;
use OCA\Circles\Events\Files\CreatingFileShareEvent;
use OCA\Circles\Events\Files\FileShareCreatedEvent;
use OCA\Circles\Events\Files\PreparingFileShareEvent;
use OCA\Circles\Events\PreparingCircleMemberEvent;
use OCA\Circles\Events\RemovingCircleMemberEvent;
use OCA\Circles\Events\RequestingCircleMemberEvent;
use OCA\Circles\FileSharingTeamResourceProvider;
use OCA\Circles\Handlers\WebfingerHandler;
use OCA\Circles\Listeners\AccountUpdated;
use OCA\Circles\Listeners\Files\AddingMemberSendMail as ListenerFilesAddingMemberSendMail;
use OCA\Circles\Listeners\Files\CreatingShareSendMail as ListenerFilesCreatingShareSendMail;
use OCA\Circles\Listeners\Files\DestroyingCircle as ListenerFilesDestroyingCircle;
use OCA\Circles\Listeners\Files\MemberAddedSendMail as ListenerFilesMemberAddedSendMail;
use OCA\Circles\Listeners\Files\PreparingMemberSendMail as ListenerFilesPreparingMemberSendMail;
use OCA\Circles\Listeners\Files\PreparingShareSendMail as ListenerFilesPreparingShareSendMail;
use OCA\Circles\Listeners\Files\RemovingMember as ListenerFilesRemovingMember;
use OCA\Circles\Listeners\Files\ShareCreatedSendMail as ListenerFilesShareCreatedSendMail;
use OCA\Circles\Listeners\GroupChanged;
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
use OCA\Circles\ShareByCircleProvider;
use OCP\Accounts\UserUpdatedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IServerContainer;
use OCP\Share\IManager as IShareManager;
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
	public const APP_API = '1';

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
		$context->registerEventListener(GroupChangedEvent::class, GroupChanged::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeleted::class);
		$context->registerEventListener(UserAddedEvent::class, GroupMemberAdded::class);
		$context->registerEventListener(UserRemovedEvent::class, GroupMemberRemoved::class);

		// Local Events (for Files/Shares/Notifications management)
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

		$context->registerDashboardWidget(TeamDashboardWidget::class);
		$context->registerTeamResourceProvider(FileSharingTeamResourceProvider::class);

		$context->registerConfigLexicon(ConfigLexicon::class);
	}


	/**
	 * @param IBootContext $context
	 *
	 * @throws Throwable
	 */
	public function boot(IBootContext $context): void {
		$serverContainer = $context->getServerContainer();

		$context->injectFn(function (IShareManager $shareManager) {
			$shareManager->registerShareProvider(ShareByCircleProvider::class);
		});

		$this->configService = $context->getAppContainer()
			->get(ConfigService::class);

		$context->injectFn(Closure::fromCallable([$this, 'registerMountProvider']));
	}


	public function registerMountProvider(IServerContainer $container) {
		if (!$this->configService->isGSAvailable()) {
			return;
		}

		$mountProviderCollection = $container->get(IMountProviderCollection::class);
		$mountProviderCollection->registerProvider($container->get(CircleMountProvider::class));
	}
}
