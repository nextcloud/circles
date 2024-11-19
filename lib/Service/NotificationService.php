<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 * @author Daniel Tygel <dtygel@eita.org.br>
 *
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


namespace OCA\Circles\Service;

use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Member;
use OCP\IURLGenerator;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;

/**
 * Class NotificationService
 *
 * @package OCA\Circles\Service
 */
class NotificationService {
	use TNCLogger;


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var INotificationManager */
	private $notificationManager;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var TimezoneService */
	private $timezoneService;


	/**
	 * NotificationService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param INotificationManager $notificationManager
	 * @param MemberRequest $memberRequest
	 * @param TimezoneService $timezoneService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		INotificationManager $notificationManager,
		MemberRequest $memberRequest,
		TimezoneService $timezoneService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->notificationManager = $notificationManager;
		$this->memberRequest = $memberRequest;
		$this->timezoneService = $timezoneService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Member $member
	 */
	public function notificationInvited(Member $member): void {
		if ($member->getUserType() !== Member::TYPE_USER || !$member->isLocal()) {
			return;
		}

		$this->deleteNotification('member', $member->getId());
		$notification = $this->createMemberNotification(
			$member->getUserId(),
			$member->getId(),
			'invitation'
		);

		$declineAction = $notification->createAction();
		$declineUrl = $this->urlGenerator->linkToOCSRouteAbsolute('circles.Local.circleLeave', ['circleId' => $member->getCircleId()]);
		$declineAction->setLabel('refuse')
					  ->setLink($declineUrl, 'PUT');
		$notification->addAction($declineAction);

		$acceptAction = $notification->createAction();
		$acceptUrl = $this->urlGenerator->linkToOCSRouteAbsolute('circles.Local.circleJoin', ['circleId' => $member->getCircleId()]);
		$acceptAction->setLabel('accept')
					 ->setLink($acceptUrl, 'PUT');
		$notification->addAction($acceptAction);

		$this->notificationManager->notify($notification);
	}


	/**
	 * @param Member $member
	 *
	 * @throws RequestBuilderException
	 */
	public function notificationRequested(Member $member): void {
//		if ($member->getUserType() !== Member::TYPE_USER || !$member->isLocal()) {
//			return;
//		}

		$this->deleteNotification('member', $member->getId());

		$moderators = $this->memberRequest->getInheritedMembers(
			$member->getCircleId(),
			false,
			Member::LEVEL_MODERATOR
		);

		foreach ($moderators as $moderator) {
			if ($moderator->getUserType() !== Member::TYPE_USER || !$moderator->isLocal()) {
				continue;
			}

			$notification = $this->createMemberNotification(
				$moderator->getUserId(),
				$member->getId(),
				'joinRequest'
			);

			$declineAction = $notification->createAction();
			$declineUrl = $this->urlGenerator->linkToOCSRouteAbsolute(
				'circles.Local.memberRemove',
				[
					'circleId' => $member->getCircleId(),
					'memberId' => $member->getId()
				]
			);
			$declineAction->setLabel('refuse')
						  ->setLink($declineUrl, 'DELETE');
			$notification->addAction($declineAction);

			$acceptAction = $notification->createAction();
			$acceptUrl = $this->urlGenerator->linkToOCSRouteAbsolute(
				'circles.Local.memberConfirm',
				[
					'circleId' => $member->getCircleId(),
					'memberId' => $member->getId()
				]
			);
			$acceptAction->setLabel('accept')
						 ->setLink($acceptUrl, 'PUT');
			$notification->addAction($acceptAction);

			$this->notificationManager->notify($notification);
		}
	}


	/**
	 * @param string $object
	 * @param string $objectId
	 */
	public function deleteNotification(string $object, string $objectId) {
//		if ($objectId === '') {
//			return;
//		}
//
//		$notification = $this->notificationManager->createNotification();
//		$notification->setApp('circles')
//					 ->setObject($object, $objectId);
//
//		$this->notificationManager->markProcessed($notification);
	}


	/**
	 * @param string $userId
	 * @param string $memberId
	 * @param string $subject
	 *
	 * @return INotification
	 */
	private function createMemberNotification(
		string $userId,
		string $memberId,
		string $subject
	): INotification {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('circles')
					 ->setDateTime($this->timezoneService->getDateTime())
					 ->setUser($userId)
					 ->setObject('member', $memberId)
					 ->setSubject($subject);

		return $notification;
	}

	public function markInvitationAsProcessed(Member $member): void {
		if ($member->getUserType() !== Member::TYPE_USER || !$member->isLocal()) {
			return;
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('circles')
			->setUser($member->getUserId())
			->setObject('member', $member->getId())
			->setSubject('invitation');

		$this->notificationManager->markProcessed($notification);
	}
}
