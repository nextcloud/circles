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
use OCA\Circles\Db\ShareWrapperRequest;
use OCA\Circles\Events\MembershipsRemovedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\FederatedUserService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;


/**
 * Class MembershipsRemoved
 *
 * @package OCA\Circles\Listeners\Files
 */
class MembershipsRemoved implements IEventListener {


	use TStringTools;


	/** @var ShareWrapperRequest */
	private $shareWrapperRequest;

	/** @var FederatedUserService */
	private $federatedUserService;


	/**
	 * MembershipsRemoved constructor.
	 *
	 * @param ShareWrapperRequest $shareWrapperRequest
	 * @param FederatedUserService $federatedUserService
	 */
	public function __construct(
		ShareWrapperRequest $shareWrapperRequest,
		FederatedUserService $federatedUserService
	) {
		$this->shareWrapperRequest = $shareWrapperRequest;
		$this->federatedUserService = $federatedUserService;
	}


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!$event instanceof MembershipsRemovedEvent) {
			return;
		}

		foreach ($event->getMemberships() as $membership) {
//			$this->shareWrapperRequest->removeByMembership($membership);
			// deprecated with ShareWrapperRequest::removeByInitiatorAndShareWith()
			$federatedUser = $this->federatedUserService->getFederatedUser(
				$membership->getSingleId(),
				Member::TYPE_SINGLE
			);
			if ($federatedUser->getUserType() === Member::TYPE_USER) {
				$this->shareWrapperRequest->removeByInitiatorAndShareWith(
					$federatedUser->getUserId(),
					$membership->getCircleId()
				);
			}
		}
	}

}

