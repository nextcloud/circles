<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Service;

use Exception;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\Defaults;
use OCP\IL10N;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Security\IHasher;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Util;

class SendMailService {
	use TArrayTools;
	use TStringTools;


	/** @var IL10N */
	private $l10n;

	/** @var IHasher */
	private $hasher;

	/** @var IMailer */
	private $mailer;

	/** @var Defaults */
	private $defaults;

	/** @var ConfigService */
	private $configService;


	/**
	 * SendMailService constructor.
	 *
	 * @param IL10N $l10n
	 * @param IMailer $mailer
	 * @param Defaults $defaults
	 * @param ConfigService $configService
	 */
	public function __construct(
		IL10N $l10n,
		IHasher $hasher,
		IMailer $mailer,
		Defaults $defaults,
		ConfigService $configService,
		private IManager $shareManager,
	) {
		$this->l10n = $l10n;
		$this->hasher = $hasher;
		$this->mailer = $mailer;
		$this->defaults = $defaults;
		$this->configService = $configService;
	}


	/**
	 * @param string $author
	 * @param Circle $circle
	 * @param Member $member
	 * @param ShareWrapper[] $shares
	 * @param array $mails
	 * @param string $password
	 */
	public function generateMail(
		string $author,
		Circle $circle,
		Member $member,
		array $shares,
		array $mails,
		string $password = '',
	): void {
		if (!$this->shareManager->shareApiAllowLinks()) {
			return;
		}

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

			$this->sendMailPassword($circle, $author, $mail, $password);
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
		bool $multiple = false,
	): IEMailTemplate {
		$emailTemplate = $this->mailer->createEMailTemplate('circles.ExistingShareNotification', []);
		$emailTemplate->addHeader();

		if ($multiple) {
			$text = $this->l10n->t('%s shared multiple files with "%s".', [$author, $circleName]);
		} else {
			$text = $this->l10n->t('%s shared a file with "%s".', [$author, $circleName]);
		}

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
		bool $multiple = false,
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

	/**
	 * Send mail notifications for the user share type
	 *
	 * @param string $link link to the file/folder
	 * @param string $shareWith email address of share receiver
	 * @param string $initiatorDisplayName name of the share creator
	 * @param string $circleName name of the circle shared with
	 * @param string|null $initiatorEmail email of the share creator
	 * @param IShare $share
	 * @throws Exception
	 */
	public function sendUserShareMail(
		string $link,
		string $shareWith,
		string $initiatorDisplayName,
		string $circleName,
		?string $initiatorEmail,
		IShare $share,
	): void {

		$filename = $share->getNode()->getName();
		$expiration = $share->getExpirationDate();
		$note = $share->getNote();
		$l = $this->l10n;

		$message = $this->mailer->createMessage();

		$emailTemplate = $this->mailer->createEMailTemplate('files_sharing.RecipientNotification', [
			'filename' => $filename,
			'link' => $link,
			'initiator' => $initiatorDisplayName,
			'expiration' => $expiration,
			'shareWith' => $shareWith,
		]);

		$emailTemplate->setSubject($l->t('%1$s shared %2$s with %3$s', [$initiatorDisplayName, $filename, $circleName]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($l->t('%1$s shared %2$s with "%3$s"', [$initiatorDisplayName, $filename, $circleName]), false);

		if ($note !== '') {
			$emailTemplate->addBodyText(htmlspecialchars($note), $note);
		}

		$emailTemplate->addBodyButton(
			$l->t('Open %s', [$filename]),
			$link
		);

		$message->setTo([$shareWith]);

		// The "From" contains the sharers name
		$instanceName = $this->defaults->getName();
		$senderName = $l->t(
			'%1$s via %2$s',
			[
				$initiatorDisplayName,
				$instanceName,
			]
		);
		$message->setFrom([\OCP\Util::getDefaultEmailAddress('noreply') => $senderName]);

		// The "Reply-To" is set to the sharer if an mail address is configured
		// also the default footer contains a "Do not reply" which needs to be adjusted.
		if ($initiatorEmail !== null) {
			$message->setReplyTo([$initiatorEmail => $initiatorDisplayName]);
			$emailTemplate->addFooter($instanceName . ($this->defaults->getSlogan() !== '' ? ' - ' . $this->defaults->getSlogan() : ''));
		} else {
			$emailTemplate->addFooter();
		}

		$message->useTemplate($emailTemplate);
		$failedRecipients = $this->mailer->send($message);
		if (!empty($failedRecipients)) {
			return;
		}
	}


	/**
	 * @param Circle $circle
	 * @param string $author
	 * @param string $email
	 * @param string $password
	 *
	 * @throws Exception
	 */
	private function sendMailPassword(
		Circle $circle,
		string $author,
		string $email,
		string $password,
	): void {
		if (!$this->configService->sendPasswordByMail($circle) || $password === '') {
			return;
		}

		$message = $this->mailer->createMessage();
		$plainBodyPart = $this->l10n->t(
			"%1\$s shared some content with you.\nYou should have already received a separate email with a link to access it.\n",
			[$author]
		);
		$htmlBodyPart = $this->l10n->t(
			'%1$s shared some content with you. You should have already received a separate email with a link to access it.',
			[$author]
		);

		$emailTemplate = $this->mailer->createEMailTemplate(
			'sharebymail.RecipientPasswordNotification',
			[
				'filename' => '',
				'password' => $password,
				'initiator' => $author,
				//				'initiatorEmail' => Util::getDefaultEmailAddress(''),
				'initiatorEmail' => '',
				'shareWith' => $circle->getDisplayName()
			]
		);

		$emailTemplate->setSubject(
			$this->l10n->t('Password to access content shared with you by %1$s', [$author])
		);
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l10n->t('Password to access content'), false);
		$emailTemplate->addBodyText(htmlspecialchars($htmlBodyPart), $plainBodyPart);
		$emailTemplate->addBodyText($this->l10n->t('It is protected with the following password:'));
		$emailTemplate->addBodyText($password);

		// The "From" contains the sharers name
		$instanceName = $this->defaults->getName();
		$senderName = $this->l10n->t(
			'%1$s via %2$s',
			[
				$author,
				$instanceName
			]
		);
		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);
		//		if ($initiatorEmailAddress !== null) {
		//			$message->setReplyTo([$initiatorEmailAddress => $initiatorDisplayName]);
		//			$emailTemplate->addFooter($instanceName . ' - ' . $this->defaults->getSlogan());
		//		} else {
		$emailTemplate->addFooter();
		//		}

		$message->setTo([$email]);
		$message->useTemplate($emailTemplate);
		$this->mailer->send($message);
	}


	/**
	 * @param Circle $circle
	 *
	 * @return array
	 */
	public function getPassword(Circle $circle): array {
		$clearPassword = $hashedPassword = '';
		if (!$this->configService->sendPasswordByMail($circle)) {
			$hashedPassword = $this->get('password_single', $circle->getSettings());
		}
		if ($hashedPassword === '') {
			$clearPassword = $this->token(14);
			$hashedPassword = $this->hasher->hash($clearPassword);
		}

		return [$clearPassword, $hashedPassword];
	}
}
