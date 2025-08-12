<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners\Files;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\Files\FileShareCreatedEvent;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\ContactService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\Service\SendMailService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Service\ShareWrapperService;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;

/** @template-implements IEventListener<FileShareCreatedEvent|Event> */
class ShareCreatedSendMail implements IEventListener {
	use TStringTools;
	use TNCLogger;


	/** @var ShareWrapperService */
	private $shareWrapperService;

	/** @var ShareTokenService */
	private $shareTokenService;

	/** @var RemoteStreamService */
	private $remoteStreamService;

	/** @var SendMailService */
	private $sendMailService;

	/** @var ConfigService */
	private $configService;

	/** @var ContactService */
	private $contactService;
	/** @var IUserManager */
	private $userManager;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(
		ShareWrapperService $shareWrapperService,
		ShareTokenService $shareTokenService,
		RemoteStreamService $remoteStreamService,
		SendMailService $sendMailService,
		ContactService $contactService,
		ConfigService $configService,
		IUserManager $userManager,
		IURLGenerator $urlGenerator,
		IRootFolder $rootFolder,
	) {
		$this->shareWrapperService = $shareWrapperService;
		$this->shareTokenService = $shareTokenService;
		$this->remoteStreamService = $remoteStreamService;
		$this->sendMailService = $sendMailService;
		$this->contactService = $contactService;
		$this->configService = $configService;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;
		$this->rootFolder = $rootFolder;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function handle(Event $event): void {
		if (!$event instanceof FileShareCreatedEvent) {
			return;
		}

		$circle = $event->getCircle();
		$clearPasswords = $event->getFederatedEvent()->getInternal()->gArray('clearPasswords');
		/** @var ShareWrapper $wrappedShare */
		$wrappedShare = $event->getFederatedEvent()->getParams()->gObj('wrappedShare', ShareWrapper::class);
		$iShare = $wrappedShare->getShare($this->rootFolder, $this->userManager, $this->urlGenerator);
		$link = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', [
			'token' => $iShare->getToken()
		]);
		$initiator = $iShare->getSharedBy();
		$initiatorUser = $this->userManager->get($initiator);
		$initiatorDisplayName = ($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;
		$initiatorEmail = ($initiatorUser instanceof IUser) ? $initiatorUser->getEMailAddress() : null;

		foreach ($circle->getInheritedMembers(false, true) as $member) {
			if ($member->getUserType() == Member::TYPE_USER && $member->isLocal()) {
				$user = $this->userManager->get($member->getUserId());
				if ($user === null) {
					continue;
				}
				$email = $user->getEMailAddress();
				if ($email === null
					|| $email === $initiatorEmail
				) {
					continue;
				}
				$this->sendMailService->sendUserShareMail(
					$link,
					$user->getEMailAddress(),
					$initiatorDisplayName,
					$circle->getDisplayName(),
					$initiatorEmail,
					$iShare,
				);
			}

			if ($member->getUserType() !== Member::TYPE_MAIL
				&& $member->getUserType() !== Member::TYPE_CONTACT) {
				continue;
			}

			$mails = [];
			$share = null;
			foreach ($event->getResults() as $origin => $item) {
				$info = $item->gData('info');
				if (!$info->hasKey($member->getId())) {
					continue;
				}

				$data = $info->gData($member->getId());
				try {
					if (($this->configService->isLocalInstance($member->getInstance())
						 && $this->configService->isLocalInstance($origin))
						|| $this->remoteStreamService->isFromSameInstance($origin, $member->getInstance())) {
						$mails = $data->gArray('mails');
					}
				} catch (RemoteNotFoundException $e) {
					continue;
				}

				try {
					// are we sure the 'share' entry is valid and not spoofed !?
					/** @var ShareWrapper $share */
					$share = $data->gObj('share', ShareWrapper::class);
				} catch (Exception $e) {
				}
			}

			if (!is_null($share)) {
				if ($share->hasInitiator()) {
					$author = $share->getInitiator()->getDisplayName();
				} elseif ($user = $this->userManager->get($share->getSharedBy())) {
					$author = $user->getDisplayName();
				} else {
					$author = 'someone';
				}

				$this->sendMailService->generateMail(
					$author,
					$circle,
					$member,
					[$share],
					$mails,
					$this->get($member->getSingleId(), $clearPasswords)
				);
			}
		}
	}
}
