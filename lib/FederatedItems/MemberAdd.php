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


namespace OCA\Circles\FederatedItems;


use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OC\User\NoUserException;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Exceptions\MemberTypeNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemMustHaveMember;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\SharesToken;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IEMailTemplate;
use OCP\Util;


/**
 * Class MemberAdd
 *
 * @package OCA\Circles\GlobalScale
 */
class MemberAdd implements
	IFederatedItem,
	IFederatedItemMustHaveMember {


	use TStringTools;


	/** @var IUserManager */
	private $userManager;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var CircleService */
	private $circleService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MemberAdd constructor.
	 *
	 * @param IUserManager $userManager
	 * @param MemberRequest $memberRequest
	 * @param CircleService $circleService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager, MemberRequest $memberRequest, CircleService $circleService,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->memberRequest = $memberRequest;
		$this->circleService = $circleService;
		$this->configService = $configService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws MemberLevelException
	 * @throws NoUserException
	 * @throws FederatedEventException
	 * @throws UserTypeNotFoundException
	 * @throws \OCA\Circles\Exceptions\MembersLimitException
	 */
	public function verify(FederatedEvent $event): void {

		$member = $event->getMember();
		$circle = $event->getCircle();
		$initiator = $circle->getInitiator();

		if ($initiator->getLevel() < Member::LEVEL_MODERATOR) {
			throw new MemberLevelException('Insufficient rights to add members to this Circle');
		}

		$this->confirmMemberFormat($member);
		$member->setId($this->uuid(Member::ID_LENGTH));
		$member->setCircleId($circle->getId());


		// TODO: check Config on Circle to know if we set Level to 1 or just send an invitation
		$member->setLevel(Member::LEVEL_MEMBER);
		$member->setStatus(Member::STATUS_MEMBER);

		echo json_encode($member) . "\n";

		// TODO: Managing cached name
		//		$member->setCachedName($eventMember->getCachedName());

// check Circle is 'verified'
		$this->circleService->confirmCircleNotFull($circle);

		return;


//		$member = $this->membersRequest->getFreshNewMember(
//			$circle->getUniqueId(), $ident, $eventMember->getType(), $eventMember->getInstance()
//		);
//		$member->hasToBeInviteAble();
//
//		$this->membersService->addMemberBasedOnItsType($circle, $member);
//
//		$password = '';
//		$sendPasswordByMail = false;
//		if ($this->configService->enforcePasswordProtection($circle)) {
//			if ($circle->getSetting('password_single_enabled') === 'true') {
//				$password = $circle->getPasswordSingle();
//			} else {
//				$sendPasswordByMail = true;
//				$password = $this->miscService->token(15);
//			}
//		}
//
//		$event->setData(
//			new SimpleDataStore(
//				[
//					'password'       => $password,
//					'passwordByMail' => $sendPasswordByMail
//				]
//			)
//		);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 */
	public function manage(FederatedEvent $event): void {
		//$circle = $event->getCircle();
		$member = $event->getMember();
//		if ($member->getJoined() === '') {
//			$this->membersRequest->createMember($member);
//		} else {
//			$this->membersRequest->updateMemberLevel($member);
//		}
//

		$this->memberRequest->save($member);

//
//		//
//		// TODO: verifiez comment se passe le cached name sur un member_add
//		//
//		$cachedName = $member->getCachedName();
//		$password = $event->getData()
//						  ->g('password');
//
//		$shares = $this->generateUnknownSharesLinks($circle, $member, $password);
//		$result = [
//			'unknownShares' => $shares,
//			'cachedName'    => $cachedName
//		];
//
//		if ($member->getType() === DeprecatedMember::TYPE_CONTACT
//			&& $this->configService->isLocalInstance($member->getInstance())) {
//			$result['contact'] = $this->miscService->getInfosFromContact($member);
//		}
//
//		$event->setResult(new SimpleDataStore($result));
//		$this->eventsService->onMemberNew($circle, $member);
	}


	/**
	 * @param FederatedEvent[] $events
	 *
	 * @throws Exception
	 */
	public function result(array $events): void {
//		$password = $cachedName = '';
//		$circle = $member = null;
//		$links = [];
//		$recipients = [];
//		foreach ($events as $event) {
//			$data = $event->getData();
//			if ($data->gBool('passwordByMail') !== false) {
//				$password = $data->g('password');
//			}
//			$circle = $event->getDeprecatedCircle();
//			$member = $event->getMember();
//			$result = $event->getResult();
//			if ($result->g('cachedName') !== '') {
//				$cachedName = $result->g('cachedName');
//			}
//
//			$links = array_merge($links, $result->gArray('unknownShares'));
//			$contact = $result->gArray('contact');
//			if (!empty($contact)) {
//				$recipients = $contact['emails'];
//			}
//		}
//
//		if (empty($links) || $circle === null || $member === null) {
//			return;
//		}
//
//		if ($cachedName !== '') {
//			$member->setCachedName($cachedName);
//			$this->membersService->updateMember($member);
//		}
//
//		if ($member->getType() === DeprecatedMember::TYPE_MAIL
//			|| $member->getType() === DeprecatedMember::TYPE_CONTACT) {
//			if ($member->getType() === DeprecatedMember::TYPE_MAIL) {
//				$recipients = [$member->getUserId()];
//			}
//
//			foreach ($recipients as $recipient) {
//				$this->memberIsMailbox($circle, $recipient, $links, $password);
//			}
//		}
	}


	/**
	 * confirm the format of UserId, based on UserType.
	 *
	 * @param IFederatedUser $member
	 *
	 * @throws UserTypeNotFoundException
	 * @throws NoUserException
	 */
	private function confirmMemberFormat(IFederatedUser $member): void {
		switch ($member->getUserType()) {
			case Member::TYPE_USER:
				$this->confirmMemberTypeUser($member);
				break;

			// TODO #M003: confirm other UserType
			default:
				throw new UserTypeNotFoundException();
		}
	}


	/**
	 * @param IFederatedUser $member
	 *
	 * @throws NoUserException
	 */
	private function confirmMemberTypeUser(IFederatedUser $member): void {
		if ($this->configService->isLocalInstance($member->getInstance())) {
			$user = $this->userManager->get($member->getUserId());
			if ($user === null) {
				throw new NoUserException('user not found');
			}

			$member->setUserId($user->getUID());

			return;
		}

		// TODO #M002: request the remote instance and check that user exists
	}

//	/**
//	 * Verify if a local account is valid.
//	 *
//	 * @param $ident
//	 * @param $type
//	 *
//	 * @param string $instance
//	 *
//	 * @throws NoUserException
//	 */
//	private function verifyIdentLocalMember(&$ident, $type, string $instance = '') {
//		if ($type !== DeprecatedMember::TYPE_USER) {
//			return;
//		}
//
//		if ($instance === '') {
//			try {
//				$ident = $this->miscService->getRealUserId($ident);
//			} catch (NoUserException $e) {
//				throw new NoUserException($this->l10n->t("This user does not exist"));
//			}
//		}
//	}
//
//
//	/**
//	 * Verify if a mail have a valid format.
//	 *
//	 * @param string $ident
//	 * @param int $type
//	 *
//	 * @throws EmailAccountInvalidFormatException
//	 */
//	private function verifyIdentEmailAddress(string $ident, int $type) {
//		if ($type !== DeprecatedMember::TYPE_MAIL) {
//			return;
//		}
//
//		if ($this->configService->isAccountOnly()) {
//			throw new EmailAccountInvalidFormatException(
//				$this->l10n->t('You cannot add a mail address as member of your Circle')
//			);
//		}
//
//		if (!filter_var($ident, FILTER_VALIDATE_EMAIL)) {
//			throw new EmailAccountInvalidFormatException(
//				$this->l10n->t('Email format is not valid')
//			);
//		}
//	}
//
//
//	/**
//	 * Verify if a contact exist in current user address books.
//	 *
//	 * @param $ident
//	 * @param $type
//	 *
//	 * @throws NoUserException
//	 * @throws EmailAccountInvalidFormatException
//	 */
//	private function verifyIdentContact(&$ident, $type) {
//		if ($type !== DeprecatedMember::TYPE_CONTACT) {
//			return;
//		}
//
//		if ($this->configService->isAccountOnly()) {
//			throw new EmailAccountInvalidFormatException(
//				$this->l10n->t('You cannot add a contact as member of your Circle')
//			);
//		}
//
//		$tmpContact = $this->userId . ':' . $ident;
//		$result = MiscService::getContactData($tmpContact);
//		if (empty($result)) {
//			throw new NoUserException($this->l10n->t("This contact is not available"));
//		}
//
//		$ident = $tmpContact;
//	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param string $recipient
	 * @param array $links
	 * @param string $password
	 */
	private function memberIsMailbox(
		DeprecatedCircle $circle, string $recipient, array $links, string $password
	) {
		if ($circle->getViewer() === null) {
			$author = $circle->getOwner()
							 ->getUserId();
		} else {
			$author = $circle->getViewer()
							 ->getUserId();
		}

		try {
			$template = $this->generateMailExitingShares($author, $circle->getName());
			$this->fillMailExistingShares($template, $links);
			$this->sendMailExistingShares($template, $author, $recipient);
			$this->sendPasswordExistingShares($author, $recipient, $password);
		} catch (Exception $e) {
			$this->miscService->log('Failed to send mail about existing share ' . $e->getMessage());
		}
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 * @param string $password
	 *
	 * @return array
	 */
	private function generateUnknownSharesLinks(
		DeprecatedCircle $circle, DeprecatedMember $member, string $password
	): array {
		$unknownShares = $this->getUnknownShares($member);

		$data = [];
		foreach ($unknownShares as $share) {
			try {
				$data[] = $this->getMailLinkFromShare($share, $member, $password);
			} catch (TokenDoesNotExistException $e) {
			}
		}

		return $data;
	}


	/**
	 * @param DeprecatedMember $member
	 *
	 * @return array
	 */
	private function getUnknownShares(DeprecatedMember $member): array {
		$allShares = $this->sharesRequest->getSharesForCircle($member->getCircleId());
		$knownShares = array_map(
			function(SharesToken $shareToken) {
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

		return $unknownShares;
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
		$sharesToken = $this->tokensRequest->generateTokenForMember($member, (int)$share['id'], $password);
		$link = $this->urlGenerator->linkToRouteAbsolute(
			'files_sharing.sharecontroller.showShare',
			['token' => $sharesToken->getToken()]
		);
		$author = $share['uid_initiator'];
		$filename = basename($share['file_target']);

		return [
			'author'   => $author,
			'link'     => $link,
			'filename' => $filename
		];
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

		$text = $this->l10n->t('%s shared multiple files with \'%s\'.', [$author, $circleName]);
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
	private function sendMailExistingShares(IEMailTemplate $emailTemplate, string $author, string $recipient
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


	/**
	 * @param string $author
	 * @param string $email
	 * @param string $password
	 *
	 * @throws Exception
	 */
	protected function sendPasswordExistingShares(string $author, string $email, string $password) {
		if ($password === '') {
			return;
		}

		$message = $this->mailer->createMessage();

		$authorUser = $this->userManager->get($author);
		$authorName = ($authorUser instanceof IUser) ? $authorUser->getDisplayName() : $author;
		$authorEmail = ($authorUser instanceof IUser) ? $authorUser->getEMailAddress() : null;

		$this->miscService->log("Sending password mail about existing files to '" . $email . "'", 0);

		$plainBodyPart = $this->l10n->t(
			"%1\$s shared multiple files with you.\nYou should have already received a separate mail with a link to access them.\n",
			[$authorName]
		);
		$htmlBodyPart = $this->l10n->t(
			'%1$s shared multiple files with you. You should have already received a separate mail with a link to access them.',
			[$authorName]
		);

		$emailTemplate = $this->mailer->createEMailTemplate(
			'sharebymail.RecipientPasswordNotification', [
														   'password' => $password,
														   'author'   => $author
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

}
