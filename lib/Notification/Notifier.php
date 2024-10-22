<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Notification;

use Exception;
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
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

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
		ConfigService $configService,
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
	 * @throws UnknownNotificationException
	 * @throws AlreadyProcessedException
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			throw new UnknownNotificationException();
		}

		$iconPath = $this->urlGenerator->imagePath(Application::APP_ID, 'circles.svg');
		$notification->setIcon($this->urlGenerator->getAbsoluteURL($iconPath));

		if ($notification->getObjectType() === 'member') {
			try {
				$this->prepareMemberNotification($notification);
			} catch (UnknownNotificationException $e) {
				throw $e;
			} catch (Exception $e) {
				throw new AlreadyProcessedException();
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
	 * @throws UnknownNotificationException
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
					'You are now a member of the Team "%2$s"',
					[
						$member->getCircle()->getDisplayName()
					]
				);
				break;

			case 'invitation':
				$subject = $this->l10n->t(
					'You have been invited by %1$s into the Team "%2$s"',
					[
						$member->getInvitedBy()->getDisplayName(),
						$member->getCircle()->getDisplayName()
					]
				);
				break;

			case 'joinRequest':
				$subject = $this->l10n->t(
					'%1$s sent a request to be a member of the Team "%2$s"',
					[
						$this->configService->displayFederatedUser($member, true),
						$member->getCircle()->getDisplayName()
					]
				);
				break;

			default:
				throw new UnknownNotificationException();
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
					$action->setParsedLabel($this->l10n->t('Leave the team'));
					break;
			}

			$notification->addParsedAction($action);
		}
	}
}
