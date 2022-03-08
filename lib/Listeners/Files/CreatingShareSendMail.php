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
use OCA\Circles\Events\Files\CreatingFileShareEvent;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\ShareWrapperNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\ContactService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Service\ShareWrapperService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class CreatingShareSendMail
 *
 * @package OCA\Circles\Listeners\Files
 */
class CreatingShareSendMail implements IEventListener {
	use TStringTools;
	use TNCLogger;


	/** @var ShareWrapperService */
	private $shareWrapperService;

	/** @var ShareTokenService */
	private $shareTokenService;

	/** @var ConfigService */
	private $configService;

	/** @var ContactService */
	private $contactService;


	/**
	 * CreatingShareSendMail constructor.
	 *
	 * @param ShareWrapperService $shareWrapperService
	 * @param ShareTokenService $shareTokenService
	 * @param ContactService $contactService
	 * @param ConfigService $configService
	 */
	public function __construct(
		ShareWrapperService $shareWrapperService,
		ShareTokenService $shareTokenService,
		ContactService $contactService,
		ConfigService $configService
	) {
		$this->shareWrapperService = $shareWrapperService;
		$this->shareTokenService = $shareTokenService;
		$this->contactService = $contactService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Event $event
	 *
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function handle(Event $event): void {
		if (!$event instanceof CreatingFileShareEvent) {
			return;
		}

		$circle = $event->getCircle();
		$federatedEvent = $event->getFederatedEvent();
		$hashedPasswords = $federatedEvent->getParams()->gArray('hashedPasswords');

		$result = [];
		foreach ($circle->getInheritedMembers(false, true) as $member) {
			if ($member->getUserType() !== Member::TYPE_MAIL
				&& $member->getUserType() !== Member::TYPE_CONTACT) {
				continue;
			}

			$share = null;
			if ($this->configService->isLocalInstance($federatedEvent->getOrigin())) {
				try {
					/** @var ShareWrapper $share */
					$share = $federatedEvent->getParams()->gObj('wrappedShare', ShareWrapper::class);
					$this->shareWrapperService->getShareById((int)$share->getId());

					// we confirm share is not spoofed by the main instance of the Circle
					if ($share->getSharedWith() !== $circle->getSingleId()) {
						throw new ShareWrapperNotFoundException();
					}

					$shareToken = $this->shareTokenService->generateShareToken(
						$share,
						$member,
						$this->get($member->getSingleId(), $hashedPasswords)
					);

					$share->setShareToken($shareToken);
				} catch (Exception $e) {
					$share = null;
				}
			}

			$result[$member->getId()] = [
				'share' => $share,
				'mails' => $this->contactService->getMailAddressesFromMember($member)
			];
		}

		$federatedEvent->setResultEntry('info', $result);
	}
}
