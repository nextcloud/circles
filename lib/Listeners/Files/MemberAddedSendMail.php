<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners\Files;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\CircleMemberAddedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\SendMailService;
use OCA\Circles\Service\ShareWrapperService;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<CircleMemberAddedEvent|Event> */
class MemberAddedSendMail implements IEventListener {
	use TStringTools;
	use TNCLogger;


	/** @var ShareWrapperService */
	private $shareWrapperService;

	/** @var SendMailService */
	private $sendMailService;

	public function __construct(
		ShareWrapperService $shareWrapperService,
		SendMailService $sendMailService,
	) {
		$this->sendMailService = $sendMailService;
		$this->shareWrapperService = $shareWrapperService;

		$this->setup('app', Application::APP_ID);
	}

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
