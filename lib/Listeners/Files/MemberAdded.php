<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCA\Circles\Listeners\Files;


use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Events\CircleMemberAddedEvent;
use OCA\Circles\Model\Member;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;


/**
 * Class MemberAdded
 *
 * @package OCA\Circles\Listeners\Files
 */
class MemberAdded implements IEventListener {


	use TStringTools;


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!$event instanceof CircleMemberAddedEvent) {
			return;
		}

		$federatedUsers = [];
		foreach ($event->getMembers() as $member) {
			if ($member->getUserType() === Member::TYPE_MAIL) {
				$federatedUsers[] = $member;
			}
		}

		if (empty($federatedUsers)) {
			return;
		}

		$result = [];
		foreach ($event->getResults() as $instance => $item) {
			$result[$instance] = $item->gData('files');
		}

		\OC::$server->getLogger()->log(3, 'FILES: ' . json_encode($result));
		\OC::$server->getLogger()->log(3, 'MAILS: ' . json_encode($federatedUsers));
	}

}

