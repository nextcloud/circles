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
use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\Files\FileShareCreatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\ContactService;
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
	use TNC22Logger;


	/** @var ShareWrapperService */
	private $shareWrapperService;

	/** @var ShareTokenService */
	private $shareTokenService;

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
	 * @param SendMailService $sendMailService
	 * @param ContactService $contactService
	 * @param ConfigService $configService
	 */
	public function __construct(
		ShareWrapperService $shareWrapperService,
		ShareTokenService $shareTokenService,
		SendMailService $sendMailService,
		ContactService $contactService,
		ConfigService $configService
	) {
		$this->shareWrapperService = $shareWrapperService;
		$this->shareTokenService = $shareTokenService;
		$this->sendMailService = $sendMailService;
		$this->contactService = $contactService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!$event instanceof FileShareCreatedEvent) {
			return;
		}

		$circle = $event->getCircle();

		foreach ($circle->getInheritedMembers() as $member) {
			if ($member->getUserType() !== Member::TYPE_MAIL
				&& $member->getUserType() !== Member::TYPE_CONTACT) {
				continue;
			}

			$mails = [];
			$share = null;
			foreach ($event->getResults() as $origin => $item) {
				$shares = $item->gData('shares');
				if (!$shares->hasKey($member->getId())) {
					continue;
				}

				$data = $shares->gData($member->getId());
				$mails = array_merge($mails, $data->gArray('mails'));

				// TODO: is it safe to use $origin to compare getInstance() ?
				// TODO: do we need to check the $origin ?
				// TODO: Solution would be to check the origin based on aliases using RemoteInstanceService
//				if ($member->getUserType() === Member::TYPE_CONTACT && $member->getInstance() === $origin) {
				try {
					$share = $data->gObj('share', ShareWrapper::class);
				} catch (Exception $e) {
				}

//				}
			}

			if (!is_null($share)) {
				$this->sendMailService->generateMail($circle, $member, [$share], $mails);
			}
		}
	}
}
