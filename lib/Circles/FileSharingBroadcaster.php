<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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
use OCA\Circles\Db\TokensRequest;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\QueryException;
use OCP\Defaults;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IShare;
use OCP\Util;


class FileSharingBroadcaster implements IBroadcaster {

	/** @var IL10N */
	private $l10n = null;

	/** @var IMailer */
	private $mailer;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IUserManager */
	private $userManager;

	/** @var ILogger */
	private $logger;

	/** @var Defaults */
	private $defaults;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var TokensRequest */
	private $tokensRequest;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * {@inheritdoc}
	 */
	public function init() {
		$this->l10n = OC::$server->getL10N(Application::APP_NAME);
		$this->mailer = OC::$server->getMailer();
		$this->rootFolder = OC::$server->getLazyRootFolder();
		$this->userManager = OC::$server->getUserManager();
		$this->logger = OC::$server->getLogger();
		$this->urlGenerator = OC::$server->getURLGenerator();
		try {
			$this->defaults = OC::$server->query(Defaults::class);
			$this->tokensRequest = OC::$server->query(TokensRequest::class);
			$this->configService = OC::$server->query(ConfigService::class);
			$this->miscService = OC::$server->query(MiscService::class);
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
	public function createShareToCircle(SharingFrame $frame, Circle $circle) {
		if ($frame->is0Circle()) {
			return false;
		}

		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToCircle(SharingFrame $frame, Circle $circle) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToCircle(SharingFrame $frame, Circle $circle) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 * @throws IllegalIDChangeException
	 */
	public function createShareToMember(SharingFrame $frame, Member $member) {
		if (!$frame->is0Circle()) {
			return false;
		}

		$payload = $frame->getPayload();
		if (!key_exists('share', $payload)) {
			return false;
		}

		$share = $this->generateShare($payload['share']);
		if ($member->getType() === Member::TYPE_MAIL || $member->getType() === Member::TYPE_CONTACT) {
			try {
				$circle = $frame->getCircle();
				$password = '';

				if ($this->configService->enforcePasswordProtection()) {
					$password = $this->miscService->uuid(15);
				}
				$token = $this->tokensRequest->generateTokenForMember($member, $share->getId(), $password);
				if ($token !== '') {
					$this->sharedByMail($circle, $share, $member->getUserId(), $token, $password);
				}
			} catch (TokenDoesNotExistException $e) {
			} catch (NotFoundException $e) {
			}
		}

		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToMember(SharingFrame $frame, Member $member) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToMember(SharingFrame $frame, Member $member) {
		return true;
	}


	/**
	 * recreate the share from the JSON payload.
	 *
	 * @param array $data
	 *
	 * @return IShare
	 * @throws IllegalIDChangeException
	 */
	private function generateShare($data) {
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
	 * @param Circle $circle
	 * @param IShare $share
	 * @param string $email
	 * @param string $token
	 * @param string $password
	 */
	private function sharedByMail(Circle $circle, IShare $share, $email, $token, $password) {
		// genelink
		$link = $this->urlGenerator->linkToRouteAbsolute(
			'files_sharing.sharecontroller.showShare',
			['token' => $token]
		);

		try {
			$this->sendMail(
				$share->getNode()
					  ->getName(), $link,
				MiscService::getDisplay($share->getSharedBy(), Member::TYPE_USER),
				$circle->getName(), $email
			);
			if ($this->configService->sendPasswordByMail() && $password !== '') {
				$this->sendPasswordByMail(
					$share, MiscService::getDisplay($share->getSharedBy(), Member::TYPE_USER),
					$email, $password
				);
			}
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
		$text = $this->l10n->t('%s shared »%s« with \'%s\'.', [$author, $fileName, $circleName]);

		$emailTemplate =
			$this->generateEmailTemplate($subject, $text, $fileName, $link, $author, $circleName);

		$instanceName = $this->defaults->getName();
		$senderName = $this->l10n->t('%s on %s', [$author, $instanceName]);

		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);
		$message->setSubject($subject);
		$message->setPlainBody($emailTemplate->renderText());
		$message->setHtmlBody($emailTemplate->renderHtml());
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
		$message = $this->mailer->createMessage();

		$this->logger->log(0, "Sending password mail to circle '" . $circleName . "': " . $email);

		$filename = $share->getNode()
						  ->getName();
		$initiator = $share->getSharedBy();
		$shareWith = $share->getSharedWith();

		$initiatorUser = $this->userManager->get($initiator);
		$initiatorDisplayName =
			($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;
		$initiatorEmailAddress = ($initiatorUser instanceof IUser) ? $initiatorUser->getEMailAddress() : null;

		$plainBodyPart = $this->l10n->t(
			"%1\$s shared »%2\$s« with you.\nYou should have already received a separate mail with a link to access it.\n",
			[$initiatorDisplayName, $filename]
		);
		$htmlBodyPart = $this->l10n->t(
			'%1$s shared »%2$s« with you. You should have already received a separate mail with a link to access it.',
			[$initiatorDisplayName, $filename]
		);

		$emailTemplate = $this->mailer->createEMailTemplate(
			'sharebymail.RecipientPasswordNotification', [
														   'filename'       => $filename,
														   'password'       => $password,
														   'initiator'      => $initiatorDisplayName,
														   'initiatorEmail' => $initiatorEmailAddress,
														   'shareWith'      => $shareWith,
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
	 * @return \OCP\Mail\IEMailTemplate
	 */
	private function generateEmailTemplate($subject, $text, $fileName, $link, $author, $circleName
	) {

		$emailTemplate = $this->mailer->createEMailTemplate(
			'circles.ShareNotification', [
										   'fileName'   => $fileName,
										   'fileLink'   => $link,
										   'author'     => $author,
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
}
