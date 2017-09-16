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

use OC\Share20\Share;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\MountsRequest;
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Cloud;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\RemoteMount;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\MiscService;
use OCP\Defaults;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Mail\IMailer;
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

	/** @var Defaults */
	private $defaults;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var MountsRequest */
	private $mountsRequest;


	/**
	 * {@inheritdoc}
	 */
	public function init() {
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);
		$this->mailer = \OC::$server->getMailer();
		$this->rootFolder = \OC::$server->getLazyRootFolder();
		$this->userManager = \OC::$server->getUserManager();

		$this->defaults = \OC::$server->query(Defaults::class);
		$this->urlGenerator = \OC::$server->getURLGenerator();


		$app = new Application();
		$container = $app->getContainer();

		$this->mountsRequest = $container->query(MountsRequest::class);
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
		if ($frame->is0Circle() !== true) {
			$this->createFederatedShareToCircle($frame, $circle);

			return;
		}

		$this->createLocalShareToCircle($frame, $circle);
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
	 */
	public function createShareToMember(SharingFrame $frame, Member $member) {
		if ($frame->is0Circle() !== true) {
			return false;
		}

		$payload = $frame->getPayload();
		if (!key_exists('share', $payload)) {
			return false;
		}

		$share = $this->generateShare($payload['share']);
		if ($member->getType() === Member::TYPE_MAIL) {
			$this->sharedByMail(
				$frame->getCircle()
					  ->getName(), $share, $member->getUserId()
			);
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
	 * @param SharingFrame $frame
	 * @param Circle $circle
	 */
	private function createLocalShareToCircle(SharingFrame $frame, Circle $circle) {

	}


	/**
	 * @param SharingFrame $frame
	 * @param Circle $circle
	 */
	private function createFederatedShareToCircle(SharingFrame $frame, Circle $circle) {

		$share = MiscService::get($frame->getPayload(), 'share');
		MiscService::mustContains(
			$share, [
					  'sharedWith', 'sharedBy', 'permissions', 'nodeId', 'token', 'filename',
					  'shareOwner'
				  ]
		);

		$remoteMount = new RemoteMount();
		$remoteMount->setCircleId($circle->getUniqueId())
					->setRemoteCircleId($frame->getHeaders()['circleId'])
					->setCloud(
						new Cloud($frame->getHeaders()['cloudId'], $frame->getHeaders()['cloudHost'])
					)
					->setToken($share['token'])
					->setFileId($share['nodeId'])
					->setFilename($share['filename'])
					->setAuthor($share['shareOwner'])
					->setMountPoint('/test_test')
					->setMountPointHash('');

		$this->mountsRequest->create($remoteMount);
	}

	/**
	 * recreate the share from the JSON payload.
	 *
	 * @param array $data
	 *
	 * @return IShare
	 */
	private function generateShare($data) {

		$share = new Share($this->rootFolder, $this->userManager);
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
	 * @param $circleName
	 * @param IShare $share
	 * @param string $email
	 */
	private function sharedByMail($circleName, IShare $share, $email) {

		$link = $this->urlGenerator->linkToRouteAbsolute(
			'files_sharing.sharecontroller.showShare',
			['token' => $share->getToken()]
		);

		$this->sendMail(
			$share->getNode()
				  ->getName(), $link,
			MiscService::getDisplay($share->getSharedBy(), Member::TYPE_USER),
			$circleName, $email
		);
	}


	/**
	 * @param $fileName
	 * @param string $link
	 * @param string $author
	 * @param $circleName
	 * @param string $email
	 *
	 * @internal param string $filename
	 * @internal param string $circle
	 */
	protected function sendMail($fileName, $link, $author, $circleName, $email) {
		$message = $this->mailer->createMessage();

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
	 * @param $subject
	 * @param $text
	 * @param $fileName
	 * @param $link
	 * @param string $author
	 * @param string $circleName
	 *
	 * @return \OCP\Mail\IEMailTemplate
	 */
	private function generateEmailTemplate($subject, $text, $fileName, $link, $author, $circleName) {

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
			$text . "\n " . $this->l10n->t('Click the button below to open it.'), $text
		);
		$emailTemplate->addBodyButton($this->l10n->t('Open »%s«', [$fileName]), $link);

		return $emailTemplate;
	}
}
