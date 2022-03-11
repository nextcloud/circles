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

use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
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
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class ShareCreatedSendMail
 *
 * @package OCA\Circles\Listeners\Files
 */
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


	/**
	 * ShareCreatedSendMail constructor.
	 *
	 * @param ShareWrapperService $shareWrapperService
	 * @param ShareTokenService $shareTokenService
	 * @param RemoteStreamService $remoteStreamService
	 * @param SendMailService $sendMailService
	 * @param ContactService $contactService
	 * @param ConfigService $configService
	 */
	public function __construct(
		ShareWrapperService $shareWrapperService,
		ShareTokenService $shareTokenService,
		RemoteStreamService $remoteStreamService,
		SendMailService $sendMailService,
		ContactService $contactService,
		ConfigService $configService
	) {
		$this->shareWrapperService = $shareWrapperService;
		$this->shareTokenService = $shareTokenService;
		$this->remoteStreamService = $remoteStreamService;
		$this->sendMailService = $sendMailService;
		$this->contactService = $contactService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Event $event
	 *
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

		foreach ($circle->getInheritedMembers(false, true) as $member) {
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
