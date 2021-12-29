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

use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use ArtificialOwl\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\PreparingCircleMemberEvent;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\ContactService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Service\ShareWrapperService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\IHasher;

/**
 * Class PreparingMemberSendMail
 *
 * @package OCA\Circles\Listeners\Files
 */
class PreparingMemberSendMail implements IEventListener {
	use TStringTools;
	use TNC22Logger;


	/** @var IHasher */
	private $hasher;

	/** @var ShareWrapperService */
	private $shareWrapperService;

	/** @var ShareTokenService */
	private $shareTokenService;

	/** @var ConfigService */
	private $configService;

	/** @var ContactService */
	private $contactService;


	/**
	 * AddingMember constructor.
	 *
	 * @param IHasher $hasher
	 * @param ShareWrapperService $shareWrapperService
	 * @param ShareTokenService $shareTokenService
	 * @param ContactService $contactService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IHasher $hasher,
		ShareWrapperService $shareWrapperService,
		ShareTokenService $shareTokenService,
		ContactService $contactService,
		ConfigService $configService
	) {
		$this->hasher = $hasher;
		$this->shareWrapperService = $shareWrapperService;
		$this->shareTokenService = $shareTokenService;
		$this->contactService = $contactService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Event $event
	 *
	 * @throws RequestBuilderException
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function handle(Event $event): void {
		if (!$event instanceof PreparingCircleMemberEvent
			|| !$this->configService->enforcePasswordOnSharedFile()) {
			return;
		}

		$member = $event->getMember();
		if ($member->getUserType() === Member::TYPE_CIRCLE) {
			$members = $member->getBasedOn()->getInheritedMembers();
		} else {
			$members = [$member];
		}

		$federatedEvent = $event->getFederatedEvent();
		$clearPasswords = $federatedEvent->getInternal()->gArray('clearPasswords');
		$hashedPasswords = $federatedEvent->getParams()->gArray('hashedPasswords');

		foreach ($members as $member) {
			if (($member->getUserType() !== Member::TYPE_MAIL
				 && $member->getUserType() !== Member::TYPE_CONTACT)
				|| array_key_exists($member->getSingleId(), $clearPasswords)
			) {
				continue;
			}

			$clearPassword = $this->token(14);
			$clearPasswords[$member->getSingleId()] = $clearPassword;
			$hashedPasswords[$member->getSingleId()] = $this->hasher->hash($clearPassword);
		}

		$federatedEvent->getInternal()->aArray('clearPasswords', $clearPasswords);
		$federatedEvent->getParams()->aArray('hashedPasswords', $hashedPasswords);
	}
}
