<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Indices added after circles_member was created, for `occ db:add-missing-indices`.
 *
 * - circle_id, level: the owner of a circle (level 9) is joined into most member queries;
 *   without it the lookup reads every member of the circle.
 * - single_id: memberships of an entity across all circles; circles_member_cisi starts
 *   with circle_id, so it cannot serve a lookup by single_id alone.
 *
 * @template-implements IEventListener<Event|AddMissingIndicesEvent>
 */
class AddMissingIndicesListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof AddMissingIndicesEvent) {
			return;
		}

		$event->addMissingIndex('circles_member', 'circles_member_cile', ['circle_id', 'level']);
		$event->addMissingIndex('circles_member', 'circles_member_si', ['single_id']);
	}
}
