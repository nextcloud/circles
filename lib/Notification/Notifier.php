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


namespace OCA\Circles\Notification;

use Exception;
use InvalidArgumentException;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCP\Contacts\IManager;
use OCP\Federation\ICloudIdManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

/**
 * Class Notifier
 *
 * @package OCA\Circles\Notification
 */
class Notifier implements INotifier {
	/** @var IL10N */
	private $l10n;


	/** @var IFactory */
	protected $factory;

	/** @var IManager */
	protected $contactsManager;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var array */
	protected $federatedContacts;

	/** @var ICloudIdManager */
	protected $cloudIdManager;

	/** @var MemberService */
	private $memberService;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var ConfigService */
	private $configService;


	public function __construct(
		IL10N $l10n,
		IFactory $factory,
		IManager $contactsManager,
		IURLGenerator $urlGenerator,
		ICloudIdManager $cloudIdManager,
		MemberService $memberService,
		FederatedUserService $federatedUserService,
		ConfigService $configService
	) {
		$this->l10n = $l10n;
		$this->factory = $factory;
		$this->contactsManager = $contactsManager;
		$this->urlGenerator = $urlGenerator;
		$this->cloudIdManager = $cloudIdManager;

		$this->federatedUserService = $federatedUserService;
		$this->memberService = $memberService;
		$this->configService = $configService;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return Application::APP_ID;
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->l10n->t(Application::APP_NAME);
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 *
	 * @return INotification
	 * @throws InvalidArgumentException
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			throw new InvalidArgumentException();
		}

		$iconPath = $this->urlGenerator->imagePath(Application::APP_ID, 'circles.svg');
		$notification->setIcon($this->urlGenerator->getAbsoluteURL($iconPath));

		if ($notification->getObjectType() === 'member') {
			try {
				$this->prepareMemberNotification($notification);
			} catch (Exception $e) {
				// TODO: delete notification
			}
		}

		$this->prepareActions($notification);

		return $notification;
	}


	/**
	 * @param INotification $notification
	 *
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws RequestBuilderException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	private function prepareMemberNotification(INotification $notification) {
		$this->federatedUserService->initCurrentUser($notification->getUser());

		$probe = new CircleProbe();
		$probe->initiatorAsDirectMember()
			  ->includeNonVisibleCircles();

		$member = $this->memberService->getMemberById(
			$notification->getObjectId(),
			'',
			$probe
		);

		switch ($notification->getSubject()) {
			case 'memberAdd':
				$subject = $this->l10n->t(
					'You are now a member of the Circle "%2$s"',
					[
						$member->getCircle()->getDisplayName()
					]
				);
				break;

			case 'invitation':
				$subject = $this->l10n->t(
					'You have been invited by %1$s into the Circle "%2$s"',
					[
						$member->getInvitedBy()->getDisplayName(),
						$member->getCircle()->getDisplayName()
					]
				);
				break;

			case 'joinRequest':
				$subject = $this->l10n->t(
					'%1$s sent a request to be a member of the Circle "%2$s"',
					[
						$this->configService->displayFederatedUser($member, true),
						$member->getCircle()->getDisplayName()
					]
				);
				break;

			default:
				throw new InvalidArgumentException();
		}

		$notification->setParsedSubject($subject);
	}


	/**
	 * @param INotification $notification
	 */
	private function prepareActions(INotification $notification): void {
		foreach ($notification->getActions() as $action) {
			switch ($action->getLabel()) {
				case 'accept':
					$action->setParsedLabel($this->l10n->t('Accept'))
						   ->setPrimary(true);
					break;

				case 'refuse':
					$action->setParsedLabel($this->l10n->t('Refuse'));
					break;

				case 'leave':
					$action->setParsedLabel($this->l10n->t('Leave the circle'));
					break;
			}

			$notification->addParsedAction($action);
		}
	}
}
