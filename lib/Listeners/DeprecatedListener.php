<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 * @deprecated
 *
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
		CircleService $circleService,
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
