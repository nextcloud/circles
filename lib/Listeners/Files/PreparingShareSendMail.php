<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners\Files;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\Files\PreparingFileShareEvent;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\ContactService;
use OCA\Circles\Service\SendMailService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Service\ShareWrapperService;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\IHasher;

/** @template-implements IEventListener<PreparingFileShareEvent|Event> */
class PreparingShareSendMail implements IEventListener {
	use TStringTools;
	use TNCLogger;

	public function __construct(
		private IHasher $hasher,
		private ShareWrapperService $shareWrapperService,
		private ShareTokenService $shareTokenService,
		private SendMailService $sendMailService,
		private ContactService $contactService,
		private ConfigService $configService,
	) {
		$this->setup('app', Application::APP_ID);
	}

	/**
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function handle(Event $event): void {
		if (!$event instanceof PreparingFileShareEvent) {
			return;
		}

		$circle = $event->getCircle();
		if (!$this->configService->enforcePasswordOnSharedFile($circle)) {
			return;
		}

		$federatedEvent = $event->getFederatedEvent();

		$hashedPasswords = $clearPasswords = [];
		foreach ($circle->getInheritedMembers(false, true) as $member) {
			if (($member->getUserType() !== Member::TYPE_MAIL
				 && $member->getUserType() !== Member::TYPE_CONTACT)
				|| array_key_exists($member->getSingleId(), $clearPasswords)
			) {
				// Ignore members that are not 'mail' or the one we already generated a password
				continue;
			}

			[$clearPassword, $hashedPassword] = $this->sendMailService->getPassword($circle);
			$clearPasswords[$member->getSingleId()] = $clearPassword;
			$hashedPasswords[$member->getSingleId()] = $hashedPassword;
		}

		$federatedEvent->getInternal()->aArray('clearPasswords', $clearPasswords);
		$federatedEvent->getParams()->aArray('hashedPasswords', $hashedPasswords);
	}
}
