<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use OCA\Circles\BackgroundJob\OidcSyncUser;
use OCA\Circles\ConfigLexicon;
use OCP\AppFramework\Services\IAppConfig;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserLoggedInEvent;

/** @template-implements IEventListener<UserLoggedInEvent> */
class UserLoggedIn implements IEventListener {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IJobList $jobList,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof UserLoggedInEvent)) {
			return;
		}

		if (!$this->appConfig->getAppValueBool(ConfigLexicon::OIDC_ENABLED)) {
			return;
		}

		$this->jobList->add(OidcSyncUser::class, ['userId' => $event->getUser()->getUID()]);
	}
}
