<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright 2020
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


namespace OCA\Circles\Listeners;

use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;


/**
 * Class UserDeleted
 *
 * @package OCA\Circles\Events
 */
class UserDeleted implements IEventListener {


	/** @var MembersService */
	private $membersService;

	/** @var MiscService */
	private $miscService;


	/**
	 * UserDeleted constructor.
	 *
	 * @param MembersService $membersService
	 * @param MiscService $miscService
	 */
	public function __construct(MembersService $membersService, MiscService $miscService) {
		$this->membersService = $membersService;
		$this->miscService = $miscService;
	}


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		$user = $event->getUser();
		if ($user === null) {
			return;
		}

		try {
			$this->membersService->onUserRemoved($user->getUID());
		} catch (\Exception $e) {
			$this->miscService->e($e);
		}
	}

}

