<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Circles\Service;

use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory as L10NFactory;
use OCP\Mail\IMailer;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class CircleShareMailerService {

	private IMailer $mailer;
	private LoggerInterface $logger;
	private IURLGenerator $urlGenerator;
	private IConfig $config;
	private IUserManager $userManager;
	private Defaults $defaults;
	private IL10N $l;
	public function __construct(
		IMailer $mailer,
		L10NFactory $l10nFactory,
		LoggerInterface $logger,
		IURLGenerator $urlGenerator,
		IConfig $config,
		IUserManager $userManager,
		Defaults $defaults,
	) {
		$this->mailer = $mailer;
		$this->l = $l10nFactory->get('circles');
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->defaults = $defaults;
	}

	public function sendShareNotification(IShare $share, $circle): void {
		if ($this->config->getSystemValueBool('sharing.enable_share_mail', true)) {
			$circleMembers = $circle->getMembers();
			$link = $this->urlGenerator->linkToRouteAbsolute('files_sharing.Accept.accept', ['shareId' => 'ocinternal:' . $share->getId()]);
			foreach ($circleMembers as $member) {
				if ($member->getUserType() != 1) {
					continue;
				}
				$user = $this->userManager->get($member->getUserId());
				if ($user !== null) {
					$email = $user->getEMailAddress();
					if ($email != '' && $this->mailer->validateMailAddress($email)) {
						$this->sendUserShareMail(
							$share->getNode()->getName(),
							$link,
							$share->getSharedBy(),
							$email,
							$share->getExpirationDate(),
							$share->getNote()
						);
					}
				}
			}
		}
	}

	protected function sendUserShareMail(
		$filename,
		$link,
		$initiator,
		$shareWith,
		?\DateTime $expiration = null,
		$note = ''): void {
		$initiatorUser = $this->userManager->get($initiator);
		$initiatorDisplayName = ($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;

		$message = $this->mailer->createMessage();

		$emailTemplate = $this->mailer->createEMailTemplate('files_sharing.RecipientNotification', [
			'filename' => $filename,
			'link' => $link,
			'initiator' => $initiatorDisplayName,
			'expiration' => $expiration,
			'shareWith' => $shareWith,
		]);
		$l = $this->l;

		$emailTemplate->setSubject($l->t('%1$s shared %2$s with you', [$initiatorDisplayName, $filename]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($l->t('%1$s shared %2$s with you', [$initiatorDisplayName, $filename]), false);

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
		if ($initiatorUser) {
			$initiatorEmail = $initiatorUser->getEMailAddress();
			if ($initiatorEmail !== null) {
				$message->setReplyTo([$initiatorEmail => $initiatorDisplayName]);
				$emailTemplate->addFooter($instanceName . ($this->defaults->getSlogan() !== '' ? ' - ' . $this->defaults->getSlogan() : ''));
			} else {
				$emailTemplate->addFooter();
			}
		} else {
			$emailTemplate->addFooter();
		}

		$message->useTemplate($emailTemplate);

		$failedRecipients = $this->mailer->send($message);

		if (!empty($failedRecipients)) {
			$this->logger->error('Share notification mail could not be sent to: ' . implode(', ', $failedRecipients));
			return;
		}
	}
}
