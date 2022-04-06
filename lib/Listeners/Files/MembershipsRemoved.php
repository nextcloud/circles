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

use OCA\Circles\CirclesManager;
use OCA\Circles\Db\ShareWrapperRequest;
use OCA\Circles\Events\MembershipsRemovedEvent;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\ShareWrapperService;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class MembershipsRemoved
 *
 * @package OCA\Circles\Listeners\Files
 */
class MembershipsRemoved implements IEventListener {
	use TStringTools;


	/** @var CirclesManager */
	private $circlesManager;

	private ShareWrapperService $shareWrapperService;

	/** @var FederatedUserService */
	private $federatedUserService;


	/**
	 * MembershipsRemoved constructor.
	 *
	 * @param CirclesManager $circlesManager
	 * @param ShareWrapperService $shareWrapperService
	 * @param FederatedUserService $federatedUserService
	 */
	public function __construct(
		CirclesManager $circlesManager,
		ShareWrapperService $shareWrapperService,
		FederatedUserService $federatedUserService
	) {
		$this->circlesManager = $circlesManager;
		$this->shareWrapperService = $shareWrapperService;
		$this->federatedUserService = $federatedUserService;
	}


	/**
	 * @param Event $event
	 *
	 * @throws CircleNotFoundException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	public function handle(Event $event): void {
		if (!$event instanceof MembershipsRemovedEvent) {
			return;
		}

		foreach ($event->getMemberships() as $membership) {
			/*
			 * deprecated with ShareWrapperRequest::removeByInitiatorAndShareWith()
			 * will be replaced by:
			 * // $this->shareWrapperRequest->removeByMembership($membership);
			 */
			$federatedUser = $this->circlesManager->getFederatedUser($membership->getSingleId());
			if ($federatedUser->getUserType() === Member::TYPE_USER
				&& $federatedUser->isLocal()) {
				$this->shareWrapperService->deleteUserSharesToCircle(
					$membership->getCircleId(),
					$federatedUser->getUserId()
				);
			}
		}
	}
}
