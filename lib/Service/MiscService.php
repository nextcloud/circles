<?php
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

namespace OCA\Circles\Service;

use OCA\Circles\Tools\Traits\TArrayTools;
use Exception;
use OC;
use OC\User\NoUserException;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\MissingKeyInArrayException;
use OCA\Circles\Model\DeprecatedMember;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Contacts\ContactsMenu\IContactsStore;
use OCP\ILogger;
use OCP\IUserManager;

class MiscService {
	use TArrayTools;


	/** @var ILogger */
	private $logger;

	/** @var IContactsStore */
	private $contactsStore;

	/** @var string */
	private $appName;

	/** @var IUserManager */
	private $userManager;

	public function __construct(
		ILogger $logger, IContactsStore $contactsStore, $appName, IUserManager $userManager
	) {
		$this->logger = $logger;
		$this->contactsStore = $contactsStore;
		$this->appName = $appName;
		$this->userManager = $userManager;
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
		$this->logger->logException($e, ['app' => 'circles']);
	}


	/**
	 * @param $arr
	 * @param $k
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
		if (!class_exists(\OCA\DAV\CardDAV\ContactsManager::class) || !strpos($ident, ':')) {
			return '';
		}

		[$userId, $contactId] = explode(':', $ident);
		$entries = [];
		try {
			/** @var \OCA\DAV\CardDAV\ContactsManager $cManager */
			$cManager = OC::$server->query(\OCA\DAV\CardDAV\ContactsManager::class);
			$urlGenerator = OC::$server->getURLGenerator();

			$cm = OC::$server->getContactsManager();
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

		$user = OC::$server->getUserManager()
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
		if (!class_exists(\OCA\DAV\CardDAV\ContactsManager::class) || !strpos($ident, ':')) {
			return [];
		}

		[$userId, $contactId] = explode(':', $ident);

		try {
			/** @var \OCA\DAV\CardDAV\ContactsManager $cManager */
			$cManager = OC::$server->query(\OCA\DAV\CardDAV\ContactsManager::class);
			$urlGenerator = OC::$server->getURLGenerator();

			$cm = OC::$server->getContactsManager();
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
