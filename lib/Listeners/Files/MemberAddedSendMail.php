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

use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\CircleMemberAddedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\SendMailService;
use OCA\Circles\Service\ShareWrapperService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class MemberAdded
 *
 * @package OCA\Circles\Listeners\Files
 */
class MemberAddedSendMail implements IEventListener {
	use TStringTools;
	use TNCLogger;


	/** @var ShareWrapperService */
	private $shareWrapperService;

	/** @var SendMailService */
	private $sendMailService;


	/**
	 * MemberAdded constructor.
	 *
	 * @param ShareWrapperService $shareWrapperService
	 * @param SendMailService $sendMailService
	 */
	public function __construct(
		ShareWrapperService $shareWrapperService,
		SendMailService $sendMailService
	) {
		$this->sendMailService = $sendMailService;
		$this->shareWrapperService = $shareWrapperService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!$event instanceof CircleMemberAddedEvent) {
			return;
		}

		$member = $event->getMember();
		$circle = $event->getCircle();

		if ($member->getUserType() === Member::TYPE_CIRCLE) {
			$members = $member->getBasedOn()->getInheritedMembers();
		} else {
			$members = [$member];
		}

		$clearPasswords = $event->getFederatedEvent()->getInternal()->gArray('clearPasswords');

		/** @var Member[] $members */
		foreach ($members as $member) {
			if ($member->getUserType() !== Member::TYPE_MAIL
				&& $member->getUserType() !== Member::TYPE_CONTACT
			) {
				continue;
			}

			$mails = $shares = [];
			foreach ($event->getResults() as $origin => $item) {
				foreach ($item->gArray('files') as $filesArray) {
					$files = new SimpleDataStore($filesArray);
					if (!$files->hasKey($member->getId())) {
						continue;
					}

					$data = $files->gData($member->getId());
					$shares = array_merge($shares, $data->gObjs('shares', ShareWrapper::class));

					// TODO: is it safe to use $origin to compare getInstance() ?
					// TODO: do we need to check the $origin ?
					// TODO: Solution would be to check the origin based on aliases using RemoteInstanceService
//				if ($member->getUserType() === Member::TYPE_CONTACT && $member->getInstance() === $origin) {
					$mails = array_merge($mails, $data->gArray('mails'));
//				}
				}
			}

			if ($member->hasInvitedBy()) {
				$author = $member->getInvitedBy()->getDisplayName();
			} else {
				$author = 'someone';
			}

			$this->sendMailService->generateMail(
				$author,
				$circle,
				$member,
				$shares,
				$mails,
				$this->get($member->getSingleId(), $clearPasswords)
			);
		}
	}
}
