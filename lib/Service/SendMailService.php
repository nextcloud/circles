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


namespace OCA\Circles\Service;

use Exception;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCP\Defaults;
use OCP\IL10N;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Util;

/**
 * Class SendMailService
 *
 * @package OCA\Circles\Service
 */
class SendMailService {


	/** @var IL10N */
	private $l10n;

	/** @var IMailer */
	private $mailer;

	/** @var Defaults */
	private $defaults;


	/**
	 * SendMailService constructor.
	 *
	 * @param IL10N $l10n
	 * @param IMailer $mailer
	 * @param Defaults $defaults
	 */
	public function __construct(
		IL10N $l10n,
		IMailer $mailer,
		Defaults $defaults
	) {
		$this->l10n = $l10n;
		$this->mailer = $mailer;
		$this->defaults = $defaults;
	}


	/**
	 * @param string $author
	 * @param Circle $circle
	 * @param Member $member
	 * @param ShareWrapper[] $shares
	 * @param array $mails
	 */
	public function generateMail(string $author, Circle $circle, Member $member, array $shares, array $mails): void {
		if (empty($shares)) {
			return;
		}

		if ($member->getUserType() === Member::TYPE_MAIL) {
			$mails = [$member->getUserId()];
		}

		if (empty($mails)) {
			return;
		}

		$links = [];
		foreach ($shares as $share) {
			$links[] = [
				'filename' => $share->getFileTarget(),
				'link' => $share->getShareToken()->getLink()
			];
		}

		$template = $this->generateMailExitingShares(
			$author,
			$circle->getDisplayName(),
			sizeof($links) > 1
		);

		$this->fillMailExistingShares($template, $links);
		foreach ($mails as $mail) {
			try {
				$this->sendMailExistingShares($template, $author, $mail, sizeof($links) > 1);
			} catch (Exception $e) {
			}
		}
	}


	/**
	 * @param string $author
	 * @param string $circleName
	 * @param bool $multiple
	 *
	 * @return IEMailTemplate
	 */
	private function generateMailExitingShares(
		string $author,
		string $circleName,
		bool $multiple = false
	): IEMailTemplate {
		$emailTemplate = $this->mailer->createEMailTemplate('circles.ExistingShareNotification', []);
		$emailTemplate->addHeader();

		if ($multiple) {
			$text = $this->l10n->t('%s shared multiple files with "%s".', [$author, $circleName]);
		} else {
			$text = $this->l10n->t('%s shared a file with "%s".', [$author, $circleName]);
		}

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
	 * @param bool $multiple
	 *
	 * @throws Exception
	 */
	private function sendMailExistingShares(
		IEMailTemplate $emailTemplate,
		string $author,
		string $recipient,
		bool $multiple = false
	) {
		if ($multiple) {
			$subject = $this->l10n->t('%s shared multiple files with you.', [$author]);
		} else {
			$subject = $this->l10n->t('%s shared a file with you.', [$author]);
		}

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
