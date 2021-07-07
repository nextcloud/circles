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
use OCA\Circles\Events\CircleMemberAddedEvent;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ShareWrapperService;
use OCP\Defaults;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Util;

/**
 * Class MemberAdded
 *
 * @package OCA\Circles\Listeners\Files
 */
class MemberAdded implements IEventListener {
	use TStringTools;
	use TNC22Logger;


	/** @var IL10N */
	private $l10n;

	/** @var IMailer */
	private $mailer;

	/** @var Defaults */
	private $defaults;

	/** @var ShareWrapperService */
	private $shareWrapperService;


	/**
	 * MemberAdded constructor.
	 *
	 * @param IL10N $l10n
	 * @param IMailer $mailer
	 * @param Defaults $defaults
	 * @param ShareWrapperService $shareWrapperService
	 */
	public function __construct(
		IL10N $l10n,
		IMailer $mailer,
		Defaults $defaults,
		ShareWrapperService $shareWrapperService
	) {
		$this->l10n = $l10n;
		$this->mailer = $mailer;
		$this->defaults = $defaults;
		$this->shareWrapperService = $shareWrapperService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Event $event
	 *
	 * @throws RequestBuilderException
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

		/** @var Member[] $members */
		foreach ($members as $member) {
			if ($member->getUserType() !== Member::TYPE_MAIL
				&& $member->getUserType() !== Member::TYPE_CONTACT
			) {
				continue;
			}

			$mails = [];
			$shares = [];
			foreach ($event->getResults() as $origin => $item) {
				$files = $item->gData('files');
				if (!$files->hasKey($member->getId())) {
					continue;
				}

				$data = $files->gData($member->getId());
				$shares = array_merge($shares, $data->gObjs('shares', ShareWrapper::class));

				// TODO: is it safe to use $origin to compare getInstance() ?
				if ($member->getUserType() === Member::TYPE_CONTACT && $member->getInstance() === $origin) {
					$mails = $data->gArray('mails');
				}
			}

			$this->generateMail($circle, $member, $shares, $mails);
		}
	}


	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param ShareWrapper[] $shares
	 * @param array $mails
	 */
	private function generateMail(Circle $circle, Member $member, array $shares, array $mails): void {
		if (empty($shares)) {
			return;
		}

		if ($member->getUserType() === Member::TYPE_MAIL) {
			$mails = [$member->getUserId()];
		}

		if (empty($mails)) {
			return;
		}

		if ($member->hasInvitedBy()) {
			$invitedBy = $member->getInvitedBy()->getDisplayName();
		} else {
			$invitedBy = 'someone';
		}

		$links = [];
		foreach ($shares as $share) {
			$links[] = [
				'filename' => $share->getFileTarget(),
				'link' => $share->getShareToken()->getLink()
			];
		}

		$template = $this->generateMailExitingShares($invitedBy, $circle->getDisplayName());
		$this->fillMailExistingShares($template, $links);
		foreach ($mails as $mail) {
			try {
				$this->sendMailExistingShares($template, $invitedBy, $mail);
			} catch (Exception $e) {
			}
		}
	}


	/**
	 * @param string $author
	 * @param string $circleName
	 *
	 * @return IEMailTemplate
	 */
	private function generateMailExitingShares(string $author, string $circleName): IEMailTemplate {
		$emailTemplate = $this->mailer->createEMailTemplate('circles.ExistingShareNotification', []);
		$emailTemplate->addHeader();

		$text = $this->l10n->t('%s shared multiple files with "%s".', [$author, $circleName]);
		$emailTemplate->addBodyText(htmlspecialchars($text), $text);

		return $emailTemplate;
	}

	/**
	 * @param IEMailTemplate $emailTemplate
	 * @param array $links
	 */
	private function fillMailExistingShares(IEMailTemplate $emailTemplate, array $links) {
		foreach ($links as $item) {
			$emailTemplate->addBodyButton(
				$this->l10n->t('Open »%s«', [htmlspecialchars($item['filename'])]), $item['link']
			);
		}
	}


	/**
	 * @param IEMailTemplate $emailTemplate
	 * @param string $author
	 * @param string $recipient
	 *
	 * @throws Exception
	 */
	private function sendMailExistingShares(
		IEMailTemplate $emailTemplate,
		string $author,
		string $recipient
	) {
		$subject = $this->l10n->t('%s shared multiple files with you.', [$author]);

		$instanceName = $this->defaults->getName();
		$senderName = $this->l10n->t('%s on %s', [$author, $instanceName]);

		$message = $this->mailer->createMessage();

		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);
		$message->setSubject($subject);
		$message->setPlainBody($emailTemplate->renderText());
		$message->setHtmlBody($emailTemplate->renderHtml());
		$message->setTo([$recipient]);

		$this->mailer->send($message);
	}
}
