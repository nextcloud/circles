<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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


namespace OCA\Circles\Circles;

use Exception;
use OC;
use OC\Share20\Share;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\FileSharesRequest;
use OCA\Circles\Db\TokensRequest;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\SharesToken;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCA\FederatedFileSharing\Notifications;
use OCP\AppFramework\QueryException;
use OCP\Defaults;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IShare;
use OCP\Util;

/**
 * Class FileSharingBroadcaster
 * @deprecated
 * @package OCA\Circles\Circles
 */
class FileSharingBroadcaster implements IBroadcaster {
	/** @var bool */
	private $initiated = false;

	/** @var IL10N */
	private $l10n = null;

	/** @var IMailer */
	private $mailer;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IUserManager */
	private $userManager;

	/** @var ICloudIdManager */
	private $federationCloudIdManager;

	/** @var Notifications */
	private $federationNotifications;

	/** @var ILogger */
	private $logger;

	/** @var Defaults */
	private $defaults;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var FileSharesRequest */
	private $fileSharesRequest;

	/** @var TokensRequest */
	private $tokensRequest;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var bool */
	private $federatedEnabled = false;


	/**
	 * {@inheritdoc}
	 */
	public function init() {
		if ($this->initiated) {
			return;
		}

		$this->initiated = true;
		$this->l10n = OC::$server->getL10N(Application::APP_ID);
		$this->mailer = OC::$server->getMailer();
		$this->rootFolder = OC::$server->getLazyRootFolder();
		$this->userManager = OC::$server->getUserManager();
		$this->federationCloudIdManager = OC::$server->getCloudIdManager();
		$this->logger = OC::$server->getLogger();
		$this->urlGenerator = OC::$server->getURLGenerator();
		try {
			$this->defaults = OC::$server->query(Defaults::class);
			$this->fileSharesRequest = OC::$server->query(FileSharesRequest::class);
			$this->tokensRequest = OC::$server->query(TokensRequest::class);
			$this->configService = OC::$server->query(ConfigService::class);
			$this->miscService = OC::$server->query(MiscService::class);
		} catch (QueryException $e) {
			OC::$server->getLogger()
					   ->log(1, 'Circles: cannot init FileSharingBroadcaster - ' . $e->getMessage());
		}

		try {
			$this->federationNotifications =
				OC::$server->query(Notifications::class);
			$this->federatedEnabled = true;
		} catch (QueryException $e) {
		}
	}


	/**
	 * {@inheritdoc}
	 */
	public function end() {
	}


	/**
	 * {@inheritdoc}
	 */
	public function createShareToCircle(SharingFrame $frame, DeprecatedCircle $circle) {
		if ($frame->is0Circle()) {
			return false;
		}

		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToCircle(SharingFrame $frame, DeprecatedCircle $circle) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToCircle(SharingFrame $frame, DeprecatedCircle $circle) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 * @throws IllegalIDChangeException
	 * @throws Exception
	 */
	public function createShareToMember(SharingFrame $frame, DeprecatedMember $member) {
		if (!$frame->is0Circle()) {
			return false;
		}

		$payload = $frame->getPayload();
		if (!key_exists('share', $payload)) {
			return false;
		}

		$share = $this->generateShare($payload['share']);
		if ($member->getType() === DeprecatedMember::TYPE_MAIL || $member->getType() === DeprecatedMember::TYPE_CONTACT) {
			$circle = $frame->getCircle();
			try {
				// federated shared in contact
				$clouds = $this->getCloudsFromContact($member->getUserId());
				if ($this->federatedEnabled && !empty($clouds)) {
					$sent = false;
					foreach ($clouds as $cloudId) {
						$sharesToken = $this->tokensRequest->generateTokenForMember($member, $share->getId());
						if ($this->sharedByFederated($circle, $share, $cloudId, $sharesToken)) {
							$sent = true;
						}
					}

					if ($sent) {
						return true;
					}
				}

				$password = '';
				$sendPasswordByMail = true;
//				if ($this->configService->enforcePasswordProtection($circle)) {
//					if ($circle->getSetting('password_single_enabled') === 'true') {
//						$password = $circle->getPasswordSingle();
//						$sendPasswordByMail = false;
//					} else {
//						$password = $this->miscService->token(15);
//					}
//				}

				$sharesToken =
					$this->tokensRequest->generateTokenForMember($member, $share->getId(), $password);
				$mails = [$member->getUserId()];
				if ($member->getType() === DeprecatedMember::TYPE_CONTACT) {
					$mails = $this->getMailsFromContact($member->getUserId());
				}

				if (!$sendPasswordByMail) {
					$password = '';
				}

				foreach ($mails as $mail) {
					$this->sharedByMail($circle, $share, $mail, $sharesToken, $password);
				}
			} catch (Exception $e) {
			}
		}

		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToMember(SharingFrame $frame, DeprecatedMember $member) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToMember(SharingFrame $frame, DeprecatedMember $member) {
		return true;
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	public function sendMailAboutExistingShares(DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($member->getType() !== DeprecatedMember::TYPE_MAIL && $member->getType() !== DeprecatedMember::TYPE_CONTACT
			&& $member->getContactId() !== '') {
			return;
		}

		$this->init();

		$allShares = $this->fileSharesRequest->getSharesForCircle($member->getCircleId());
		$knownShares = array_map(
			function (SharesToken $shareToken) {
				return $shareToken->getShareId();
			},
			$this->tokensRequest->getTokensFromMember($member)
		);

		$unknownShares = [];
		foreach ($allShares as $share) {
			if (!in_array($share['id'], $knownShares)) {
				$unknownShares[] = $share;
			}
		}

		if ($circle->getViewer() === null) {
			$author = $circle->getOwner();
		} else {
			$author = $circle->getViewer();
		}

		$recipient = $member->getUserId();
		if ($member->getType() === DeprecatedMember::TYPE_CONTACT) {
			$data = MiscService::getContactData($member->getUserId());
			if (!array_key_exists('EMAIL', $data)) {
				return;
			}

			$emails = $data['EMAIL'];
			if (empty($emails)) {
				return;
			}

			$recipient = $emails[0];
		}

		$this->sendMailExitingShares($circle, $unknownShares, $author, $member, $recipient);
	}


	/**
	 * recreate the share from the JSON payload.
	 *
	 * @param array $data
	 *
	 * @return IShare
	 * @throws IllegalIDChangeException
	 */
	private function generateShare($data): IShare {
		$this->logger->log(0, 'Regenerate shares from payload: ' . json_encode($data));

		$share = new Share($this->rootFolder, $this->userManager);
		$share->setId($data['id']);
		$share->setSharedBy($data['sharedBy']);
		$share->setSharedWith($data['sharedWith']);
		$share->setNodeId($data['nodeId']);
		$share->setShareOwner($data['shareOwner']);
		$share->setPermissions($data['permissions']);
		$share->setToken($data['token']);
		$share->setPassword($data['password']);

		return $share;
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param IShare $share
	 * @param string $address
	 * @param SharesToken $token
	 *
	 * @return bool
	 */
	public function sharedByFederated(DeprecatedCircle $circle, IShare $share, string $address, SharesToken $token
	): bool {
		try {
			$cloudId = $this->federationCloudIdManager->resolveCloudId($address);

			$localUrl = $this->urlGenerator->getAbsoluteURL('/');
			$sharedByFederatedId = $share->getSharedBy();
			if ($this->userManager->userExists($sharedByFederatedId)) {
				$cloudId = $this->federationCloudIdManager->getCloudId($sharedByFederatedId, $localUrl);
			}
			$sharedByFederatedId = $cloudId->getId();
			$ownerCloudId = $this->federationCloudIdManager->getCloudId($share->getShareOwner(), $localUrl);

			return $this->federationNotifications->sendRemoteShare(
				$token->getToken(),
				$address,
				$share->getNode()
					  ->getName(),
				$share->getId(),
				$share->getShareOwner(),
				$ownerCloudId->getId(),
				$share->getSharedBy(),
				$sharedByFederatedId,
				IShare::TYPE_USER
			);
		} catch (\Exception $e) {
			$this->logger->logException(
				$e, [
					'message' => 'Failed to notify remote server of circles-federated share',
					'level' => ILogger::ERROR,
					'app' => 'circles',
				]
			);
		}

		return false;
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param IShare $share
	 * @param string $email
	 * @param SharesToken $sharesToken
	 * @param string $password
	 */
	private function sharedByMail(
		DeprecatedCircle $circle, IShare $share, string $email, SharesToken $sharesToken, string $password
	) {
		// genelink
		$link = $this->urlGenerator->linkToRouteAbsolute(
			'files_sharing.sharecontroller.showShare',
			['token' => $sharesToken->getToken()]
		);

		$lang = $this->configService->getCoreValueForUser($share->getSharedBy(), 'lang', '');
		if ($lang !== '') {
			$this->l10n = OC::$server->getL10N(Application::APP_ID, $lang);
		}

		$displayName = $this->miscService->getDisplayName($share->getSharedBy());
		try {
			$this->sendMail(
				$share->getNode()
					  ->getName(), $link, $displayName, $circle->getName(), $email
			);
			$this->sendPasswordByMail($share, $displayName, $email, $password);
		} catch (Exception $e) {
			OC::$server->getLogger()
					   ->log(1, 'Circles::sharedByMail - mail were not sent: ' . $e->getMessage());
		}
	}


	/**
	 * @param $fileName
	 * @param string $link
	 * @param string $author
	 * @param $circleName
	 * @param string $email
	 *
	 * @throws Exception
	 */
	protected function sendMail($fileName, $link, $author, $circleName, $email) {
		$message = $this->mailer->createMessage();

		$this->logger->log(
			0, "Sending mail to circle '" . $circleName . "': " . $email . ' file: ' . $fileName
			   . ' - link: ' . $link
		);

		$subject = $this->l10n->t('%s shared »%s« with you.', [$author, $fileName]);
		$text = $this->l10n->t('%s shared »%s« with "%s".', [$author, $fileName, $circleName]);

		$emailTemplate =
			$this->generateEmailTemplate($subject, $text, $fileName, $link, $author, $circleName);

		$instanceName = $this->defaults->getName();
		$senderName = $this->l10n->t('%s on %s', [$author, $instanceName]);

		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);
		$emailTemplate->setSubject($subject);
		$message->useTemplate($emailTemplate);
		$message->setTo([$email]);

		$this->mailer->send($message);
	}


	/**
	 * @param IShare $share
	 * @param string $circleName
	 * @param string $email
	 *
	 * @param $password
	 *
	 * @throws NotFoundException
	 * @throws Exception
	 */
	protected function sendPasswordByMail(IShare $share, $circleName, $email, $password) {
//		if (!$this->configService->sendPasswordByMail() || $password === '') {
//			return;
//		}

		$message = $this->mailer->createMessage();

		$this->logger->log(0, "Sending password mail to circle '" . $circleName . "': " . $email);

		$filename = $share->getNode()
						  ->getName();
		$initiator = $share->getSharedBy();
		$shareWith = $share->getSharedWith();

		$initiatorUser = $this->userManager->get($initiator);
		$initiatorDisplayName =
			($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;
		$initiatorEmailAddress =
			($initiatorUser instanceof IUser) ? $initiatorUser->getEMailAddress() : null;

		$plainBodyPart = $this->l10n->t(
			"%1\$s shared »%2\$s« with you.\nYou should have already received a separate email with a link to access it.\n",
			[$initiatorDisplayName, $filename]
		);
		$htmlBodyPart = $this->l10n->t(
			'%1$s shared »%2$s« with you. You should have already received a separate email with a link to access it.',
			[$initiatorDisplayName, $filename]
		);

		$emailTemplate = $this->mailer->createEMailTemplate(
			'sharebymail.RecipientPasswordNotification', [
				'filename' => $filename,
				'password' => $password,
				'initiator' => $initiatorDisplayName,
				'initiatorEmail' => $initiatorEmailAddress,
				'shareWith' => $shareWith,
			]
		);

		$emailTemplate->setSubject(
			$this->l10n->t(
				'Password to access »%1$s« shared to you by %2$s', [$filename, $initiatorDisplayName]
			)
		);
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l10n->t('Password to access »%s«', [$filename]), false);
		$emailTemplate->addBodyText(htmlspecialchars($htmlBodyPart), $plainBodyPart);
		$emailTemplate->addBodyText($this->l10n->t('It is protected with the following password:'));
		$emailTemplate->addBodyText($password);

		// The "From" contains the sharers name
		$instanceName = $this->defaults->getName();
		$senderName = $this->l10n->t(
			'%1$s via %2$s',
			[
				$initiatorDisplayName,
				$instanceName
			]
		);
		$message->setFrom([\OCP\Util::getDefaultEmailAddress($instanceName) => $senderName]);
		if ($initiatorEmailAddress !== null) {
			$message->setReplyTo([$initiatorEmailAddress => $initiatorDisplayName]);
			$emailTemplate->addFooter($instanceName . ' - ' . $this->defaults->getSlogan());
		} else {
			$emailTemplate->addFooter();
		}

		$message->setTo([$email]);
		$message->useTemplate($emailTemplate);
		$this->mailer->send($message);
	}


	/**
	 * @param $subject
	 * @param $text
	 * @param $fileName
	 * @param $link
	 * @param string $author
	 * @param string $circleName
	 *
	 * @return IEMailTemplate
	 */
	private function generateEmailTemplate($subject, $text, $fileName, $link, $author, $circleName
	) {
		$emailTemplate = $this->mailer->createEMailTemplate(
			'circles.ShareNotification', [
				'fileName' => $fileName,
				'fileLink' => $link,
				'author' => $author,
				'circleName' => $circleName,
			]
		);

		$emailTemplate->addHeader();
		$emailTemplate->addHeading($subject, false);
		$emailTemplate->addBodyText(
			htmlspecialchars($text) . '<br>' . htmlspecialchars(
				$this->l10n->t('Click the button below to open it.')
			), $text
		);
		$emailTemplate->addBodyButton(
			$this->l10n->t('Open »%s«', [htmlspecialchars($fileName)]), $link
		);

		return $emailTemplate;
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param array $unknownShares
	 * @param DeprecatedMember $author
	 * @param DeprecatedMember $member
	 * @param string $recipient
	 */
	public function sendMailExitingShares(
		DeprecatedCircle $circle, array $unknownShares, DeprecatedMember $author, DeprecatedMember $member, string $recipient
	) {
		$data = [];

		$password = '';
//		if ($this->configService->enforcePasswordProtection($circle)) {
//			$password = $this->miscService->token(15);
//		}

		foreach ($unknownShares as $share) {
			try {
				$data[] = $this->getMailLinkFromShare($share, $member, $password);
			} catch (TokenDoesNotExistException $e) {
			}
		}

		if (sizeof($data) === 0) {
			return;
		}

		try {
			$template = $this->generateMailExitingShares($author->getCachedName(), $circle->getName());
			$this->fillMailExistingShares($template, $data);
			$this->sendMailExistingShares($template, $author->getCachedName(), $recipient);
			$this->sendPasswordExistingShares($author, $recipient, $password);
		} catch (Exception $e) {
			$this->logger->log(2, 'Failed to send mail about existing share ' . $e->getMessage());
		}
	}


	/**
	 * @param $author
	 * @param string $email
	 * @param $password
	 *
	 * @throws Exception
	 */
	protected function sendPasswordExistingShares(DeprecatedMember $author, string $email, string $password) {
//		if (!$this->configService->sendPasswordByMail() || $password === '') {
//			return;
//		}

		$message = $this->mailer->createMessage();

		$authorUser = $this->userManager->get($author->getUserId());
		$authorName = ($authorUser instanceof IUser) ? $authorUser->getDisplayName() : $author;
		$authorEmail = ($authorUser instanceof IUser) ? $authorUser->getEMailAddress() : null;

		$this->logger->log(0, "Sending password mail about existing files to '" . $email . "'");

		$plainBodyPart = $this->l10n->t(
			"%1\$s shared multiple files with you.\nYou should have already received a separate email with a link to access them.\n",
			[$authorName]
		);
		$htmlBodyPart = $this->l10n->t(
			'%1$s shared multiple files with you. You should have already received a separate email with a link to access them.',
			[$authorName]
		);

		$emailTemplate = $this->mailer->createEMailTemplate(
			'sharebymail.RecipientPasswordNotification', [
				'password' => $password,
				'author' => $author
			]
		);

		$emailTemplate->setSubject(
			$this->l10n->t(
				'Password to access files shared to you by %1$s', [$authorName]
			)
		);
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l10n->t('Password to access files'), false);
		$emailTemplate->addBodyText(htmlspecialchars($htmlBodyPart), $plainBodyPart);
		$emailTemplate->addBodyText($this->l10n->t('It is protected with the following password:'));
		$emailTemplate->addBodyText($password);

		// The "From" contains the sharers name
		$instanceName = $this->defaults->getName();
		$senderName = $this->l10n->t(
			'%1$s via %2$s',
			[
				$authorName,
				$instanceName
			]
		);

		$message->setFrom([\OCP\Util::getDefaultEmailAddress($instanceName) => $senderName]);
		if ($authorEmail !== null) {
			$message->setReplyTo([$authorEmail => $authorName]);
			$emailTemplate->addFooter($instanceName . ' - ' . $this->defaults->getSlogan());
		} else {
			$emailTemplate->addFooter();
		}

		$message->setTo([$email]);
		$message->useTemplate($emailTemplate);
		$this->mailer->send($message);
	}

	/**
	 * @param array $share
	 * @param DeprecatedMember $member
	 * @param string $password
	 *
	 * @return array
	 * @throws TokenDoesNotExistException
	 */
	private function getMailLinkFromShare(array $share, DeprecatedMember $member, string $password = '') {
		$sharesToken = $this->tokensRequest->generateTokenForMember($member, $share['id'], $password);
		$link = $this->urlGenerator->linkToRouteAbsolute(
			'files_sharing.sharecontroller.showShare',
			['token' => $sharesToken->getToken()]
		);
		$author = $share['uid_initiator'];

		$filename = basename($share['file_target']);

		return [
			'author' => $author,
			'link' => $link,
			'filename' => $filename
		];
	}


	/**
	 * @param $author
	 * @param string $circleName
	 *
	 * @return IEMailTemplate
	 * @throws Exception
	 */
	protected function generateMailExitingShares($author, $circleName) {
		$this->logger->log(
			0, "Generating mail about existing share mail from '" . $author . "' in "
			   . $circleName
		);

		$emailTemplate = $this->mailer->createEMailTemplate('circles.ExistingShareNotification', []);
		$emailTemplate->addHeader();

		$text = $this->l10n->t('%s shared multiple files with "%s".', [$author, $circleName]);
		$emailTemplate->addBodyText(htmlspecialchars($text), $text);

		return $emailTemplate;
	}


	/**
	 * @param IEMailTemplate $emailTemplate
	 * @param array $data
	 */
	protected function fillMailExistingShares(IEMailTemplate $emailTemplate, array $data) {
		foreach ($data as $item) {
//			$text = $this->l10n->t('%s shared »%s« with you.', [$item['author'], $item['filename']]);
//			$emailTemplate->addBodyText(
//				htmlspecialchars($text) . '<br>' . htmlspecialchars(
//					$this->l10n->t('Click the button below to open it.')
//				), $text
//			);
			$emailTemplate->addBodyButton(
				$this->l10n->t('Open »%s«', [htmlspecialchars($item['filename'])]), $item['link']
			);
		}
	}


	/**
	 * @param IEMailTemplate $emailTemplate
	 * @param $author
	 * @param $recipient
	 *
	 * @throws Exception
	 */
	protected function sendMailExistingShares(IEMailTemplate $emailTemplate, $author, $recipient) {
		$subject = $this->l10n->t('%s shared multiple files with you.', [$author]);
//		$emailTemplate->addHeading($subject, false);

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
	 * @param string $contactId
	 *
	 * @return array
	 */
	private function getCloudsFromContact(string $contactId): array {
		$contact = MiscService::getContactData($contactId);
		if (!key_exists('CLOUD', $contact)) {
			return [];
		}

		return $contact['CLOUD'];
	}

	/**
	 * @param string $contactId
	 *
	 * @return array
	 */
	private function getMailsFromContact(string $contactId): array {
		$contact = MiscService::getContactData($contactId);
		if (!key_exists('EMAIL', $contact)) {
			return [];
		}

		return $contact['EMAIL'];
	}
}
