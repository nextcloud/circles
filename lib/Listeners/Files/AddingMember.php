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


use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ShareWrapperService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;


/**
 * Class AddingMember
 *
 * @package OCA\Circles\Listeners\Files
 */
class AddingMember implements IEventListener {


	use TStringTools;


	/** @var ShareWrapperService */
	private $shareWrapperService;


	/**
	 * AddingMember constructor.
	 *
	 * @param ShareWrapperService $shareWrapperService
	 */
	public function __construct(ShareWrapperService $shareWrapperService) {
		$this->shareWrapperService = $shareWrapperService;
	}


	/**
	 * @param Event $event
	 *
	 * @throws RequestBuilderException
	 */
	public function handle(Event $event): void {
		if (!$event instanceof AddingCircleMemberEvent) {
			return;
		}

		$bypass = true;
		foreach ($event->getMembers() as $member) {
			if ($member->getUserType() === Member::TYPE_MAIL) {
				$bypass = false;
			}
		}

		if ($bypass) {
			return;
		}

		$circle = $event->getCircle();
		$files = $this->shareWrapperService->getSharesToCircle($circle->getSingleId());

		$event->getFederatedEvent()->addResult('files', new SimpleDataStore($files));
	}

}

