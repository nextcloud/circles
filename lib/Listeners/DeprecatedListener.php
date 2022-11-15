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


namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\ContactAddressBookNotFoundException;
use OCA\Circles\Exceptions\ContactFormatException;
use OCA\Circles\Exceptions\ContactNotFoundException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\FederatedItems\MemberDisplayName;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\FederatedUserService;
use OCP\IUser;

/**
 * Class DeprecatedListener
 *
 * some events are still using the old dispatcher.
 *
 * @package OCA\Circles\Events
 */
class DeprecatedListener {
	/** @var CircleRequest */
	private $circleRequest;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;


	/**
	 * DeprecatedListener constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param FederatedUserService $federatedUserService
	 * @param FederatedEventService $federatedEventService
	 * @param CircleService $circleService
	 */
	public function __construct(
		CircleRequest $circleRequest,
		FederatedUserService $federatedUserService,
		FederatedEventService $federatedEventService,
		CircleService $circleService
	) {
		$this->circleRequest = $circleRequest;
		$this->federatedUserService = $federatedUserService;
		$this->federatedEventService = $federatedEventService;
		$this->circleService = $circleService;
	}


	/**
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws FederatedUserNotFoundException
	 * @throws ContactNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 * @throws InvalidIdException
	 * @throws FederatedUserException
	 * @throws InitiatorNotFoundException
	 */
	public function userAccountUpdated(IUser $user) {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($user->getUID());

		$this->circleRequest->updateDisplayName($federatedUser->getSingleId(), $user->getDisplayName());
		$this->federatedUserService->setCurrentUser($federatedUser);

		$probe = new CircleProbe();
		$probe->includeSystemCircles()
			  ->mustBeMember()
			  ->canBeRequestingMembership();

		$circles = $this->circleService->getCircles($probe);

		foreach ($circles as $circle) {
			// we are only interested in direct membership
			if ($circle->getInitiator()->getSingleId() !== $federatedUser->getSingleId()) {
				continue;
			}

			$event = new FederatedEvent(MemberDisplayName::class);
			$event->setCircle($circle);
			$event->getParams()->s('displayName', $user->getDisplayName());

			try {
				$this->federatedEventService->newEvent($event);
			} catch (Exception $e) {
			}
		}
	}
}
