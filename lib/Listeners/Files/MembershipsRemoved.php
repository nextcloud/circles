<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

/** @template-implements IEventListener<MembershipsRemovedEvent|Event> */
class MembershipsRemoved implements IEventListener {
	use TStringTools;


	/** @var CirclesManager */
	private $circlesManager;

	private ShareWrapperService $shareWrapperService;

	/** @var FederatedUserService */
	private $federatedUserService;

	public function __construct(
		CirclesManager $circlesManager,
		ShareWrapperService $shareWrapperService,
		FederatedUserService $federatedUserService,
	) {
		$this->circlesManager = $circlesManager;
		$this->shareWrapperService = $shareWrapperService;
		$this->federatedUserService = $federatedUserService;
	}


	/**
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
