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
use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\ContactService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Service\ShareWrapperService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class AddingMemberSendMail
 *
 * @package OCA\Circles\Listeners\Files
 */
class AddingMemberSendMail implements IEventListener {
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
	 * AddingMember constructor.
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
	 * @throws RequestBuilderException
	 */
	public function handle(Event $event): void {
		if (!$event instanceof AddingCircleMemberEvent) {
			return;
		}

		$member = $event->getMember();
		if ($member->getUserType() === Member::TYPE_CIRCLE) {
			$members = $member->getBasedOn()->getInheritedMembers();
		} else {
			$members = [$member];
		}

		$circle = $event->getCircle();
		$federatedEvent = $event->getFederatedEvent();
		$shares = $this->shareWrapperService->getSharesToCircle($circle->getSingleId());
		$hashedPasswords = $federatedEvent->getParams()->gArray('hashedPasswords');

		$result = [];
		foreach ($members as $member) {
			if ($member->getUserType() !== Member::TYPE_MAIL
				&& $member->getUserType() !== Member::TYPE_CONTACT
			) {
				continue;
			}

			$files = [];
			foreach ($shares as $share) {
				try {
					$shareToken = $this->shareTokenService->generateShareToken(
						$share,
						$member,
						$this->get($member->getSingleId(), $hashedPasswords)
					);
				} catch (Exception $e) {
					continue;
				}

				$share->setShareToken($shareToken);
				$files[] = clone $share;
			}

			$result[$member->getId()] = [
				'shares' => $files,
				'mails' => $this->contactService->getMailAddressesFromMember($member)
			];
		}

		$federatedEvent->addResultEntry('files', $result);
	}
}
