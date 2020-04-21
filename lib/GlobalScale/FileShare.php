<?php declare(strict_types=1);


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


use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\TArrayTools;
use Exception;
use OC;
use OC\Share20\Share;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\BroadcasterIsNotCompatibleException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\GlobalScale\GSShare;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharesToken;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\QueryException;
use OCP\Files\NotFoundException;
use OCP\IL10N;
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


	/** @var IL10N */
	private $l10n;


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 *
	 * @throws GSStatusException
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		// TODO: might be a bad idea, all process of the sharing should be managed from here.
		// Even if we are not in a GS setup.
		// The reason is that if a mail needs to be send, all mail address associated to the circle needs to be retrieved
		if (!$this->configService->getGSStatus(ConfigService::GS_ENABLED)) {
			return;
		}

		// if event/file is local, we generate a federate share for the same circle on other instances
		if ($event->getSource() !== $this->configService->getLocalCloudId()) {
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
	 */
	public function manage(GSEvent $event): void {
		// TODO: might be a bad idea, all process of the sharing should be managed from here.
		// Even if we are not in a GS setup.
		// The reason is that if a mail needs to be send, all mail address associated to the circle needs to be retrieved
		if (!$this->configService->getGSStatus(ConfigService::GS_ENABLED)) {
			return;
		}

		// TODO - if event is local - generate mails sur be sent to TYPE_MAILS

		// if event is not local, we create a federated file to the right instance of Nextcloud, using the right token
		if ($event->getSource() !== $this->configService->getLocalCloudId()) {
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
		}

		$circle = $event->getCircle();
		$members = $this->membersRequest->forceGetMembers(
			$circle->getUniqueId(), Member::LEVEL_MEMBER, Member::TYPE_CONTACT, true
		);

		$accounts = [];
		foreach ($members as $member) {
			$accounts[] = $this->getInfosFromContact($member);
		}

		$event->setResult(new SimpleDataStore(['contacts' => $accounts]));
	}


	/**
	 * @param GSEvent[] $events
	 */
	public function result(array $events): void {
		$event = null;
//		$mails = $cloudIds = [];
		$contacts = [];
		foreach (array_keys($events) as $instance) {
			$event = $events[$instance];
			$contacts = array_merge(
				$contacts, $event->getResult()
								 ->gArray('contacts')
			);
//			$mails = array_merge(
//				$mails, $event->getResult()
//							  ->gArray('mails')
//			);
//			$cloudIds = array_merge(
//				$cloudIds, $event->getResult()
//								 ->gArray('cloudIds')
//			);
		}

		if ($event === null || !$event->hasCircle()) {
			return;
		}

		$circle = $event->getCircle();

		// we check mail address that were already filled
		$mails = $this->getMailAddressFromCircle($circle->getUniqueId());


//		foreach ($members as $member) {
//			$mails[] = $member->getUserId();
//		}


		foreach ($contacts as $contact) {
			$this->sendShareToContact($event, $circle, $contact);
		}


		// TODO - making this not needed - should force the async on this EVENT as it should be initiated in manage()
//		$contacts = $this->membersRequest->forceGetMembers(
//			$circle->getUniqueId(), Member::LEVEL_MEMBER, Member::TYPE_CONTACT
//		);
//
//		foreach ($contacts as $contact) {
//			$mails = array_merge($mails, $this->getMailsFromContact($contact->getUserId()));
//			$cloudIds = array_merge($cloudIds, $this->getCloudIdsFromContact($contact->getUserId()));
//		}
//
//		$mails = array_values(array_unique($mails));
//		$this->sendShareToMails($event, $mails);

//		$cloudIds = array_values(array_unique($cloudIds));
//		$this->sendShareToCloudIds($event, $cloudIds);
	}


	/**
	 * @param GSEvent $event
	 * @param array $contact
	 */
	private function sendShareToContact(GSEvent $event, Circle $circle, array $contact) {
		$password = '';
		if ($this->configService->enforcePasswordProtection()) {
			$password = $this->miscService->token(15);
		}

		try {
			$member = $this->membersRequest->forceGetMemberById($contact['memberId']);
			$share = $this->getShareFromData($event->getData());
		} catch (Exception $e) {
			return;
		}

		try {
			$sharesToken =
				$this->tokensRequest->generateTokenForMember($member, (int)$share->getId(), $password);
		} catch (TokenDoesNotExistException $e) {
			return;
		}

		foreach ($contact['emails'] as $mail) {
			$this->sharedByMail($circle, $share, $mail, $sharesToken, $password);
		}

//		$mails = [$member->getUserId()];
//		if ($member->getType() === Member::TYPE_CONTACT) {
//			$mails = $this->getMailsFromContact($member->getUserId());
//		}
//
//		foreach ($mails as $mail) {
//			$this->sharedByMail($circle, $share, $mail, $sharesToken, $password);
//		}

	}


	/**
	 * @param Circle $circle
	 * @param IShare $share
	 * @param string $email
	 * @param SharesToken $sharesToken
	 * @param string $password
	 */
	private function sharedByMail(
		Circle $circle, IShare $share, string $email, SharesToken $sharesToken, string $password
	) {
		// genelink
		$link = $this->urlGenerator->linkToRouteAbsolute(
			'files_sharing.sharecontroller.showShare',
			['token' => $sharesToken->getToken()]
		);

		$lang = $this->configService->getCoreValueForUser($share->getSharedBy(), 'lang', '');
		if ($lang !== '') {
			$this->l10n = OC::$server->getL10N(Application::APP_NAME, $lang);
		}

		try {
			$this->sendMail(
				$share->getNode()
					  ->getName(), $link,
				MiscService::getDisplay($share->getSharedBy(), Member::TYPE_USER),
				$circle->getName(), $email
			);
			$this->sendPasswordByMail(
				$share, MiscService::getDisplay($share->getSharedBy(), Member::TYPE_USER),
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
		if (!$this->configService->sendPasswordByMail() || $password === '') {
			return;
		}

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
	 * @return IEMailTemplate
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


	/**
	 * @param Member $member
	 *
	 * @return array
	 */
	private function getInfosFromContact(Member $member): array {
		$contact = MiscService::getContactData($member->getUserId());

		return [
			'memberId' => $member->getMemberId(),
			'emails'   => $this->getArray('EMAIL', $contact),
			'cloudIds' => $this->getArray('CLOUD', $contact)
		];
	}


	/**
	 * @param string $circleId
	 *
	 * @return array
	 */
	private function getMailAddressFromCircle(string $circleId): array {
		$members = $this->membersRequest->forceGetMembers(
			$circleId, Member::LEVEL_MEMBER, Member::TYPE_MAIL
		);

		return array_map(
			function(Member $member) {
				return $member->getUserId();
			}, $members
		);
	}


//
//	/**
//	 * @param GSEvent $event
//	 *
//	 * @throws BroadcasterIsNotCompatibleException
//	 * `     */
//	private function generateFederatedShare(GSEvent $event) {
//		$data = $event->getData();
//		$frame = SharingFrame::fromJSON(json_encode($data->gAll()));
//
//		try {
//			$broadcaster = \OC::$server->query((string)$frame->getHeader('broadcast'));
//			if (!($broadcaster instanceof IBroadcaster)) {
//				throw new BroadcasterIsNotCompatibleException();
//			}
//
//			$frameCircle = $frame->getCircle();
//			$circle = $this->circlesRequest->forceGetCircle($frameCircle->getUniqueId());
//		} catch (QueryException | CircleDoesNotExistException $e) {
//			return;
//		}
//
//		$this->feedBroadcaster($broadcaster, $frame, $circle);
//	}

//
//	/**
//	 * @param IBroadcaster $broadcaster
//	 * @param SharingFrame $frame
//	 * @param Circle $circle
//	 */
//	private function feedBroadcaster(IBroadcaster $broadcaster, SharingFrame $frame, Circle $circle) {
//		$broadcaster->init();
//
//		if ($circle->getType() !== Circle::CIRCLES_PERSONAL) {
//			$broadcaster->createShareToCircle($frame, $circle);
//		}
//
//		$members =
//			$this->membersRequest->forceGetMembers($circle->getUniqueId(), Member::LEVEL_MEMBER, 0, true);
//		foreach ($members as $member) {
//			$this->parseMember($member);
//
//			if ($member->isBroadcasting()) {
//				$broadcaster->createShareToMember($frame, $member);
//			}
//
//			if ($member->getInstance() !== '') {
//				$this->miscService->log('#### GENERATE FEDERATED CIRCLES SHARE ' . $member->getInstance());
//			}
//		}
//	}

//
//	/**
//	 * @param Member $member
//	 */
//	private function parseMember(Member &$member) {
//		$this->parseMemberFromContact($member);
//	}


//	/**
//	 * on Type Contact, we convert the type to MAIL and retrieve the first mail of the list.
//	 * If no email, we set the member as not broadcasting.
//	 *
//	 * @param Member $member
//	 */
//	private function parseMemberFromContact(Member &$member) {
//		if ($member->getType() !== Member::TYPE_CONTACT) {
//			return;
//		}
//
//		$contact = MiscService::getContactData($member->getUserId());
//		if (!key_exists('EMAIL', $contact)) {
//			$member->broadcasting(false);
//
//			return;
//		}
//
//		$member->setType(Member::TYPE_MAIL);
//		$member->setUserId(array_shift($contact['EMAIL']));
//	}


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
