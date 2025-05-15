<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use Exception;
use OC\User\NoUserException;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\MissingKeyInArrayException;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\DAV\CardDAV\ContactsManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Contacts\ContactsMenu\IContactsStore;
use OCP\Contacts\IManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class MiscService {
	use TArrayTools;

	public function __construct(
		private LoggerInterface $logger,
		private IContactsStore $contactsStore,
		private string $appName,
		private IUserManager $userManager,
	) {
	}

	public function log($message, $level = 4) {
		$data = [
			'app' => $this->appName,
			'level' => $level
		];

		$this->logger->log($level, $message, $data);
	}


	/**
	 * @param Exception $e
	 */
	public function e(Exception $e) {
		$this->logger->error($e->getMessage(), ['app' => 'circles', 'exception' => $e]);
	}


	/**
	 * @param array $arr
	 * @param int|string $k
	 *
	 * @param string $default
	 *
	 * @return array|string
	 */
	public static function get($arr, $k, $default = '') {
		if (!key_exists($k, $arr)) {
			return $default;
		}

		return $arr[$k];
	}


	public static function mustContains($data, $arr) {
		if (!is_array($arr)) {
			$arr = [$arr];
		}

		foreach ($arr as $k) {
			if (!key_exists($k, $data)) {
				throw new MissingKeyInArrayException('missing_key_in_array');
			}
		}
	}


	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	public function fail($data) {
		$this->log(json_encode($data));

		return new DataResponse(
			array_merge($data, ['status' => 0]),
			Http::STATUS_NON_AUTHORATIVE_INFORMATION
		);
	}


	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	public function success($data) {
		return new DataResponse(
			array_merge($data, ['status' => 1]),
			Http::STATUS_CREATED
		);
	}


	/**
	 * return the real userId, with its real case
	 *
	 * @param $userId
	 *
	 * @return string
	 * @throws NoUserException
	 */
	public function getRealUserId($userId) {
		if ($this->userManager->userExists($userId)) {
			return $this->userManager->get($userId)
				->getUID();
		}

		$result = $this->userManager->search($userId);
		if (sizeof($result) !== 1) {
			throw new NoUserException();
		}

		$user = array_shift($result);

		return $user->getUID();
	}


	/**
	 * @param string $ident
	 *
	 * @return string
	 */
	public function getContactDisplayName(string $ident): string {
		if (!class_exists(ContactsManager::class) || !strpos($ident, ':')) {
			return '';
		}

		[$userId, $contactId] = explode(':', $ident);
		$entries = [];
		try {
			/** @var ContactsManager $cManager */
			$cManager = Server::get(ContactsManager::class);
			$urlGenerator = Server::get(IURLGenerator::class);

			$cm = Server::get(IManager::class);
			$cManager->setupContactsProvider($cm, $userId, $urlGenerator);
			$contact = $cm->search($contactId, ['UID']);

			$entries = array_shift($contact);
		} catch (Exception $e) {
		}

		if (key_exists('FN', $entries) && $entries['FN'] !== '') {
			return $entries['FN'];
		}

		if (key_exists('EMAIL', $entries) && $entries['EMAIL'] !== '') {
			return $entries['EMAIL'];
		}
	}


	/**
	 * @param string $ident
	 * @param int $type
	 *
	 * @return string
	 * @deprecated
	 *
	 */
	public static function getDisplay($ident, $type) {
		$display = $ident;

		self::getDisplayMember($display, $ident, $type);
		self::getDisplayContact($display, $ident, $type);

		return $display;
	}


	/**
	 * @param string $display
	 * @param string $ident
	 * @param int $type
	 */
	private static function getDisplayMember(&$display, $ident, $type) {
		if ($type !== DeprecatedMember::TYPE_USER) {
			return;
		}

		$user = Server::get(IUserManager::class)
			->get($ident);
		if ($user !== null) {
			$display = $user->getDisplayName();
		}
	}


	/**
	 * @param string $display
	 * @param string $ident
	 * @param int $type
	 */
	private static function getDisplayContact(&$display, $ident, $type) {
		if ($type !== DeprecatedMember::TYPE_CONTACT) {
			return;
		}

		$contact = self::getContactData($ident);
		if ($contact === null) {
			return;
		}
		self::getDisplayContactFromArray($display, $contact);
	}


	/**
	 * @param $ident
	 *
	 * @return mixed|string
	 * @deprecated
	 *
	 */
	public static function getContactData($ident) {
		if (!class_exists(ContactsManager::class) || !strpos($ident, ':')) {
			return [];
		}

		[$userId, $contactId] = explode(':', $ident);

		try {
			/** @var ContactsManager $cManager */
			$cManager = Server::get(ContactsManager::class);
			$urlGenerator = Server::get(IURLGenerator::class);

			$cm = Server::get(IManager::class);
			$cManager->setupContactsProvider($cm, $userId, $urlGenerator);
			$contact = $cm->search($contactId, ['UID']);

			return array_shift($contact);
		} catch (Exception $e) {
		}

		return [];
	}


	/**
	 * @param string $display
	 * @param array $contact
	 *
	 * @deprecated
	 */
	private static function getDisplayContactFromArray(string &$display, array $contact) {
		if (!is_array($contact)) {
			return;
		}

		if (key_exists('FN', $contact) && $contact['FN'] !== '') {
			$display = $contact['FN'];

			return;
		}

		if (key_exists('EMAIL', $contact) && $contact['EMAIL'] !== '') {
			$display = $contact['EMAIL'];

			return;
		}
	}


	/**
	 * return Display Name if user exists and display name exists.
	 * returns Exception if user does not exist.
	 *
	 * However, with noException set to true, will return userId even if user does not exist
	 *
	 * @param $userId
	 * @param bool $noException
	 *
	 * @return string
	 * @throws NoUserException
	 */
	public function getDisplayName($userId, $noException = false) {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			if ($noException) {
				return $userId;
			} else {
				throw new NoUserException();
			}
		}

		return $user->getDisplayName();
	}


	/**
	 * @param array $options
	 *
	 * @return array
	 */
	public static function generateClientBodyData($options = []) {
		return [
			'body' => ['data' => $options],
			'timeout' => Application::CLIENT_TIMEOUT,
			'connect_timeout' => Application::CLIENT_TIMEOUT
		];
	}


	/**
	 * Hacky way to async the rest of the process without keeping client on hold.
	 *
	 * @param string $result
	 */
	public function asyncAndLeaveClientOutOfThis($result = '') {
		if (ob_get_contents() !== false) {
			ob_end_clean();
		}

		header('Connection: close');
		ignore_user_abort();
		ob_start();
		echo(json_encode($result));
		$size = ob_get_length();
		header('Content-Length: ' . $size);
		ob_end_flush();
		flush();
	}


	/**
	 * Generate uuid: 2b5a7a87-8db1-445f-a17b-405790f91c80
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public function token(int $length = 0): string {
		$chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';

		$str = '';
		$max = strlen($chars) - 1;
		for ($i = 0; $i <= $length; $i++) {
			try {
				$str .= $chars[random_int(0, $max)];
			} catch (Exception $e) {
			}
		}

		return $str;
	}


	/**
	 * @param DeprecatedMember $member
	 *
	 * @return array
	 */
	public function getInfosFromContact(DeprecatedMember $member) {
		$contact = MiscService::getContactData($member->getUserId());

		return [
			'memberId' => $member->getMemberId(),
			'emails' => $this->getArray('EMAIL', $contact),
			'cloudIds' => $this->getArray('CLOUD', $contact)
		];
	}
}
