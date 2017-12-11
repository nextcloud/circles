<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright 2018
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

namespace OCA\Circles\Activity;

use OCP\Activity\IConsumer;
use OCP\Activity\IManager;
use OCA\Activity\Data;
use OCA\Activity\UserSettings;
use OCP\L10N\IFactory;
use OCP\Activity\IEvent;

class Consumer implements IConsumer {
	/** @var Data */
	protected $data;
	/** @var IManager */
	protected $manager;

	/** @var UserSettings */
	protected $userSettings;

	/** @var IFactory */
	protected $l10nFactory;

	/**
	 * Constructor
	 *
	 * @param Data $data
	 * @param IManager $manager
	 * @param UserSettings $userSettings
	 * @param IFactory $l10nFactory
	 */
	public function __construct(Data $data, IManager $manager, UserSettings $userSettings, IFactory $l10nFactory) {
		$this->data = $data;
		$this->manager = $manager;
		$this->userSettings = $userSettings;
		$this->l10nFactory = $l10nFactory;
	}

	/**
	 * Send an event to the notifications of a user
	 *
	 * @param IEvent $event
	 * @return null
	 */
	public function receive(IEvent $event) {
		$selfAction = $event->getAffectedUser() === $event->getAuthor();
		$emailSetting = $this->userSettings->getUserSetting($event->getAffectedUser(), 'email', $event->getType());
		$emailSetting = ($emailSetting) ? $this->userSettings->getUserSetting($event->getAffectedUser(), 'setting', 'batchtime') : false;

		// User is not the author or wants to see their own actions
		$createStream = !$selfAction || $this->userSettings->getUserSetting($event->getAffectedUser(), 'setting', 'self');

		// Add activity to stream
		try {
			$this->data->send($event);
		} catch (\Exception $e) {
			OC::$server->getLogger()->logException($e);
		}
		// User is not the author or wants to see their own actions
		$createEmail = !$selfAction || $this->userSettings->getUserSetting($event->getAffectedUser(), 'setting', 'selfemail');

		// Add activity to mail queue
		if ($emailSetting !== false && $createEmail) {
			$latestSend = $event->getTimestamp() + $emailSetting;
			$this->data->storeMail($event, $latestSend);
		}
	}
}