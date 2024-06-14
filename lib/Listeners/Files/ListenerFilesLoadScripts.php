<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners\Files;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/** @template-implements IEventListener<LoadAdditionalScriptsEvent|Event> */
class ListenerFilesLoadScripts implements IEventListener {
	public function __construct(
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof LoadAdditionalScriptsEvent) {
			return;
		}

		// FIXME: Those scripts need to be migrated to the new files API first
		// Util::addScript('circles', 'files/circles.files.app');
		// Util::addScript('circles', 'files/circles.files.list');
		Util::addStyle('circles', 'files/circles.filelist');
	}
}
