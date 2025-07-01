<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Circles\Helpers;

use OC\Share20\DefaultShareProvider;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Share\IShare;
use OCP\Share\IShareProviderWithNotification;
use Psr\Log\LoggerInterface;

class CircleShareMailHelper extends DefaultShareProvider implements IShareProviderWithNotification {

	public function __construct(
		private IMailer $mailer,
		private IL10N $l,
		private LoggerInterface $logger,
		private IURLGenerator $urlGenerator,
		private IConfig $config,
		private IUserManager $userManager,
		private Defaults $defaults,
	) {
	}
	public function sendShareNotification(IShare $share, $circle): void {
		if ($this->config->getSystemValueBool('sharing.enable_share_mail', true)) {
			$circleMembers = $circle->getMembers();
			$link = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', [
				'token' => $share->getToken()
			]);
			foreach ($circleMembers as $member) {
				if ($member->getUserType() != 1) {
					continue;
				}
				$user = $this->userManager->get($member->getUserId());
				if ($user !== null) {
					$email = $user->getEMailAddress();
					if ($email != '' && $this->mailer->validateMailAddress($email)) {
						$this->sendUserShareMail(
							$this->l,
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
		IL10N $l,
		$filename,
		$link,
		$initiator,
		$shareWith,
		?\DateTime $expiration = null,
		$note = '',
	): void {
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
