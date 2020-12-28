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


namespace OCA\Circles\Notification;


use InvalidArgumentException;
use OC;
use OCA\Circles\AppInfo\Application;
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
	protected $url;

	/** @var array */
	protected $federatedContacts;

	/** @var ICloudIdManager */
	protected $cloudIdManager;


	public function __construct(
		IL10N $l10n, IFactory $factory, IManager $contactsManager, IURLGenerator $url,
		ICloudIdManager $cloudIdManager
	) {
		$this->l10n = $l10n;
		$this->factory = $factory;
		$this->contactsManager = $contactsManager;
		$this->url = $url;
		$this->cloudIdManager = $cloudIdManager;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'circles';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->l10n->t('Circles');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 *
	 * @return INotification
	 * @throws InvalidArgumentException
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'circles') {
			throw new InvalidArgumentException();
		}

		$l10n = OC::$server->getL10N(Application::APP_ID, $languageCode);

		$notification->setIcon(
			$this->url->getAbsoluteURL($this->url->imagePath('circles', 'black_circle.svg'))
		);
		$params = $notification->getSubjectParameters();

		switch ($notification->getSubject()) {
			case 'invitation':
				$notification->setParsedSubject(
					$l10n->t('You have been invited by %1$s into the Circle "%2$s"', $params)
				);
				break;

			case 'member_new':
				$notification->setParsedSubject(
					$l10n->t('You are now a member of the Circle "%2$s"', $params)
				);
				break;

			case 'request_new':
				$notification->setParsedSubject(
					$l10n->t('%1$s sent a request to be a member of the Circle "%2$s"', $params)
				);
				break;

			default:
				throw new InvalidArgumentException();
		}


		foreach ($notification->getActions() as $action) {
			switch ($action->getLabel()) {
				case 'accept':
					$action->setParsedLabel((string)$l10n->t('Accept'))
						   ->setPrimary(true);
					break;

				case 'refuse':
					$action->setParsedLabel((string)$l10n->t('Refuse'));
					break;

				case 'leave':
					$action->setParsedLabel((string)$l10n->t('Leave the circle'));
					break;
			}

			$notification->addParsedAction($action);
		}

		return $notification;

	}

}
