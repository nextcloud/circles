<?php

declare(strict_types=1);


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


namespace OCA\Circles\GlobalScale;

use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;
use Exception;
use OC;
use OC\Share20\Share;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\GlobalScale\GSShare;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\SharesToken;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\MiscService;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\Mail\IEMailTemplate;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;
use OCP\Util;

/**
 * Class FileShare
 *
 * @package OCA\Circles\GlobalScale
 */
class FileShare extends AGlobalScaleEvent {
	use TArrayTools;


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		// if event/file is local, we generate a federate share for the same circle on other instances
		if (!$this->configService->isLocalInstance($event->getSource())) {
			return;
		}

		try {
			$share = $this->getShareFromData($event->getData());
		} catch (Exception $e) {
			return;
		}

		try {
			$node = $share->getNode();
			$filename = $node->getName();
		} catch (NotFoundException $e) {
			$this->miscService->log('issue while FileShare: ' . $e->getMessage());

			return;
		}

		$event->getData()
			  ->s('gs_federated', $share->getToken())
			  ->s('gs_filename', '/' . $filename);
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws GSStatusException
	 * @throws CircleDoesNotExistException
	 */
	public function manage(GSEvent $event): void {
		$circle = $event->getDeprecatedCircle();

		// if event is not local, we create a federated file to the right instance of Nextcloud, using the right token
		if (!$this->configService->isLocalInstance($event->getSource())) {
			try {
				$share = $this->getShareFromData($event->getData());
			} catch (Exception $e) {
				return;
			}

			$data = $event->getData();
			$token = $data->g('gs_federated');
			$filename = $data->g('gs_filename');

			$gsShare = new GSShare($share->getSharedWith(), $token);
			$gsShare->setOwner($share->getShareOwner());
			$gsShare->setInstance($event->getSource());
			$gsShare->setParent(-1);
			$gsShare->setMountPoint($filename);

			$this->gsSharesRequest->create($gsShare);
		} else {
			// if the event is local, we send mail to mail-as-members
			$members = $this->membersRequest->forceGetMembers(
				$circle->getUniqueId(), DeprecatedMember::LEVEL_MEMBER, DeprecatedMember::TYPE_MAIL, true
			);

			foreach ($members as $member) {
				$this->sendShareToContact($event, $circle, $member->getMemberId(), [$member->getUserId()]);
			}
		}

		// we also fill the event's result for further things, like contact-as-members
		$members = $this->membersRequest->forceGetMembers(
			$circle->getUniqueId(), DeprecatedMember::LEVEL_MEMBER, DeprecatedMember::TYPE_CONTACT, true
		);

		$accounts = [];
		foreach ($members as $member) {
			if ($member->getInstance() === '') {
				$accounts[] = $this->miscService->getInfosFromContact($member);
			}
		}

		$event->setResult(new SimpleDataStore(['contacts' => $accounts]));
	}


	/**
	 * @param GSEvent[] $events
	 *
	 * @throws CircleDoesNotExistException
	 */
	public function result(array $events): void {
		$event = null;
		$contacts = [];
		foreach (array_keys($events) as $instance) {
			$event = $events[$instance];
			$contacts = array_merge(
				$contacts, $event->getResult()
								 ->gArray('contacts')
			);
		}

		if ($event === null || !$event->hasCircle()) {
			return;
		}

		$circle = $event->getDeprecatedCircle();

		foreach ($contacts as $contact) {
			$this->sendShareToContact($event, $circle, $contact['memberId'], $contact['emails']);
		}
	}


	/**
	 * @param GSEvent $event
	 * @param DeprecatedCircle $circle
	 * @param string $memberId
	 * @param array $emails
	 *
	 * @throws CircleDoesNotExistException
	 */
	private function sendShareToContact(GSEvent $event, DeprecatedCircle $circle, string $memberId, array $emails) {
		try {
			$member = $this->membersRequest->forceGetMemberById($memberId);
			$share = $this->getShareFromData($event->getData());
		} catch (Exception $e) {
			return;
		}

		$newCircle = $this->circlesRequest->forceGetCircle($circle->getUniqueId(), true);
		$password = '';
		$sendPasswordByMail = true;
//		if ($this->configService->enforcePasswordProtection($newCircle)) {
//			if ($newCircle->getSetting('password_single_enabled') === 'true') {
//				$password = $newCircle->getPasswordSingle();
//				$sendPasswordByMail = false;
//			} else {
//				$password = $this->miscService->token(15);
//			}
//		}

		try {
			$sharesToken =
				$this->tokensRequest->generateTokenForMember($member, (int)$share->getId(), $password);
		} catch (TokenDoesNotExistException $e) {
			return;
		}

		if (!$sendPasswordByMail) {
			$password = '';
		}

		foreach ($emails as $mail) {
			$this->sharedByMail($circle, $share, $mail, $sharesToken, $password);
		}
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

		try {
			$this->sendMail(
				$share->getNode()
					  ->getName(), $link,
				MiscService::getDisplay($share->getSharedBy(), DeprecatedMember::TYPE_USER),
				$circle->getName(), $email
			);
			$this->sendPasswordByMail(
				$share, MiscService::getDisplay($share->getSharedBy(), DeprecatedMember::TYPE_USER),
				$email, $password
			);
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

		$this->miscService->log(
			"Sending mail to circle '" . $circleName . "': " . $email . ' file: ' . $fileName
			. ' - link: ' . $link, 0
		);

		$subject = $this->l10n->t('%s shared »%s« with you.', [$author, $fileName]);
		$text = $this->l10n->t('%s shared »%s« with "%s".', [$author, $fileName, $circleName]);

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
//		if (!$this->configService->sendPasswordByMail() || $password === '') {
//			return;
//		}

		$message = $this->mailer->createMessage();

		$this->miscService->log("Sending password mail to circle '" . $circleName . "': " . $email, 0);

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
	 * @param string $circleId
	 *
	 * @return array
	 */
	private function getMailAddressFromCircle(string $circleId): array {
		$members = $this->membersRequest->forceGetMembers(
			$circleId, DeprecatedMember::LEVEL_MEMBER, DeprecatedMember::TYPE_MAIL
		);

		return array_map(
			function (DeprecatedMember $member) {
				return $member->getUserId();
			}, $members
		);
	}


	/**
	 * @param SimpleDataStore $data
	 *
	 * @return IShare
	 * @throws ShareNotFound
	 * @throws IllegalIDChangeException
	 */
	private function getShareFromData(SimpleDataStore $data) {
		$frame = SharingFrame::fromArray($data->gArray('frame'));
		$payload = $frame->getPayload();
		if (!key_exists('share', $payload)) {
			throw new ShareNotFound();
		}

		return $this->generateShare($payload['share']);
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
}
