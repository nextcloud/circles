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
use Exception;
use OC\User\NoUserException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\EmailAccountInvalidFormatException;
use OCA\Circles\Exceptions\GlobalScaleDSyncException;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberCantJoinCircleException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MembersLimitException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharesToken;
use OCP\IUser;
use OCP\Mail\IEMailTemplate;
use OCP\Util;


/**
 * Class MemberAdd
 *
 * @package OCA\Circles\GlobalScale
 */
class MemberAdd extends AGlobalScaleEvent {


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 *
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws EmailAccountInvalidFormatException
	 * @throws GlobalScaleDSyncException
	 * @throws GlobalScaleEventException
	 * @throws MemberAlreadyExistsException
	 * @throws MemberCantJoinCircleException
	 * @throws MembersLimitException
	 * @throws NoUserException
	 * @throws CircleTypeNotValidException
	 * @throws MemberIsNotModeratorException
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		parent::verify($event, $localCheck, true);

		$eventMember = $event->getMember();
		$this->cleanMember($eventMember);

		if ($eventMember->getInstance() === '') {
			$eventMember->setInstance($event->getSource());
		}

		$ident = $eventMember->getUserId();
		$this->membersService->verifyIdentBasedOnItsType(
			$ident, $eventMember->getType(), $eventMember->getInstance()
		);

		$circle = $event->getCircle();

		if (!$event->isForced()) {
			$circle->getHigherViewer()
				   ->hasToBeModerator();
		}

		$member = $this->membersRequest->getFreshNewMember(
			$circle->getUniqueId(), $ident, $eventMember->getType(), $eventMember->getInstance()
		);
		$member->hasToBeInviteAble();
		$member->setCachedName($eventMember->getCachedName());

		$this->circlesService->checkThatCircleIsNotFull($circle);
		$this->membersService->addMemberBasedOnItsType($circle, $member);

		$password = '';
		$sendPasswordByMail = false;
		if ($this->configService->enforcePasswordProtection($circle)) {
			if ($circle->getSetting('password_single_enabled') === 'true') {
				$password = $circle->getPasswordSingle();
			} else {
				$sendPasswordByMail = true;
				$password = $this->miscService->token(15);
			}
		}

		$event->setData(
			new SimpleDataStore(
				[
					'password'       => $password,
					'passwordByMail' => $sendPasswordByMail
				]
			)
		);
		$event->setMember($member);
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws MemberAlreadyExistsException
	 */
	public function manage(GSEvent $event): void {
		$circle = $event->getCircle();
		$member = $event->getMember();
		if ($member->getJoined() === '') {
			$this->membersRequest->createMember($member);
		} else {
			$this->membersRequest->updateMemberLevel($member);
		}


		$this->membersService->updateCachedName($member, false);
		$this->membersRequest->updateMemberInfo($member);

		$cachedName = $member->getCachedName();
		$password = $event->getData()
						  ->g('password');

		$shares = $this->generateUnknownSharesLinks($circle, $member, $password);
		$result = [
			'unknownShares' => $shares,
			'cachedName'    => $cachedName
		];

		if ($member->getType() === Member::TYPE_CONTACT
			&& $this->configService->isLocalInstance($member->getInstance())) {
			$result['contact'] = $this->miscService->getInfosFromContact($member);
		}

		$event->setResult(new SimpleDataStore($result));
		$this->eventsService->onMemberNew($circle, $member);
	}


	/**
	 * @param GSEvent[] $events
	 *
	 * @throws Exception
	 */
	public function result(array $events): void {
		$password = $cachedName = '';
		$circle = $member = null;
		$links = [];
		$recipients = [];
		foreach ($events as $event) {
			$data = $event->getData();
			if ($data->gBool('passwordByMail') !== false) {
				$password = $data->g('password');
			}
			$circle = $event->getCircle();
			$member = $event->getMember();
			$result = $event->getResult();
			if ($result->g('cachedName') !== '') {
				$cachedName = $result->g('cachedName');
			}

			$links = array_merge($links, $result->gArray('unknownShares'));
			$contact = $result->gArray('contact');
			if (!empty($contact)) {
				$recipients = $contact['emails'];
			}
		}

		if (empty($links) || $circle === null || $member === null) {
			return;
		}

		if ($cachedName !== '') {
			$member->setCachedName($cachedName);
			$this->membersService->updateMember($member);
		}

		if ($member->getType() === Member::TYPE_MAIL
			|| $member->getType() === Member::TYPE_CONTACT) {
			if ($member->getType() === Member::TYPE_MAIL) {
				$recipients = [$member->getUserId()];
			}

			$done = [];
			foreach ($recipients as $recipient) {
				if (in_array($recipient, $done)) {
					continue;
				}

				$done[] = $recipient;
				$this->memberIsMailbox($circle, $recipient, $links, $password);
			}
		}
	}


	/**
	 * @param Circle $circle
	 * @param string $recipient
	 * @param array $links
	 * @param string $password
	 */
	private function memberIsMailbox(Circle $circle, string $recipient, array $links, string $password) {
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
	 * @param Circle $circle
	 * @param Member $member
	 * @param string $password
	 *
	 * @return array
	 */
	private function generateUnknownSharesLinks(Circle $circle, Member $member, string $password): array {
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
	 * @param Member $member
	 *
	 * @return array
	 */
	private function getUnknownShares(Member $member): array {
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
	 * @param Member $member
	 * @param string $password
	 *
	 * @return array
	 * @throws TokenDoesNotExistException
	 */
	private function getMailLinkFromShare(array $share, Member $member, string $password = '') {
		$sharesToken = $this->tokensRequest->generateTokenForMember($member, (int)$share['id'], $password);
		$link = $this->urlGenerator->linkToRouteAbsolute(
			'files_sharing.sharecontroller.showShare',
			['token' => $sharesToken->getToken()]
		);

		$link = $this->configService->patchFrontalLink($link);

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
