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

namespace OCA\Circles\Service;

use OC;
use OCA\Circles\Model\Circle;
use OCP\IConfig;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\Util;

class ConfigService {

	const CIRCLES_ALLOW_CIRCLES = 'allow_circles';
	const CIRCLES_CONTACT_BACKEND = 'contact_backend';
	const CIRCLES_STILL_FRONTEND = 'still_frontend';
	const CIRCLES_GROUP_BACKEND = 'group_backend';
	const CIRCLES_GROUP_BACKEND_NAME_PREFIX = 'group_backend_name_prefix';
	const CIRCLES_GROUP_BACKEND_NAME_SUFFIX = 'group_backend_name_suffix';
	const CIRCLES_SWAP_TO_TEAMS = 'swap_to_teams';
	const CIRCLES_ALLOW_FEDERATED_CIRCLES = 'allow_federated';
	const CIRCLES_MEMBERS_LIMIT = 'members_limit';
	const CIRCLES_ACCOUNTS_ONLY = 'accounts_only';
	const CIRCLES_ALLOW_FILES_CIRCLES_FILTER = 'allow_files_filtered_by_circles';
	const CIRCLES_ALLOW_LISTED_CIRCLES = 'allow_listed_circles';
	const CIRCLES_ALLOW_ANY_GROUP_MEMBERS = 'allow_adding_any_group_members';
	const CIRCLES_ALLOW_LINKED_GROUPS = 'allow_linked_groups';
	const CIRCLES_ALLOW_NON_SSL_LINKS = 'allow_non_ssl_links';
	const CIRCLES_NON_SSL_LOCAL = 'local_is_non_ssl';
	const CIRCLES_ACTIVITY_ON_CREATION = 'creation_activity';
	const CIRCLES_SKIP_INVITATION_STEP = 'skip_invitation_to_closed_circles';

	const CIRCLES_TEST_ASYNC_LOCK = 'test_async_lock';
	const CIRCLES_TEST_ASYNC_INIT = 'test_async_init';
	const CIRCLES_TEST_ASYNC_HAND = 'test_async_hand';
	const CIRCLES_TEST_ASYNC_COUNT = 'test_async_count';

	private $defaults = [
		self::CIRCLES_ALLOW_CIRCLES              => Circle::CIRCLES_ALL,
		self::CIRCLES_CONTACT_BACKEND            => '0',
		self::CIRCLES_STILL_FRONTEND             => '0',
		self::CIRCLES_GROUP_BACKEND              => '0',
		self::CIRCLES_GROUP_BACKEND_NAME_PREFIX  => '',
		self::CIRCLES_GROUP_BACKEND_NAME_SUFFIX  => '',
		self::CIRCLES_TEST_ASYNC_INIT            => '0',
		self::CIRCLES_SWAP_TO_TEAMS              => '0',
		self::CIRCLES_ACCOUNTS_ONLY              => '0',
		self::CIRCLES_MEMBERS_LIMIT              => '50',
		self::CIRCLES_ALLOW_FILES_CIRCLES_FILTER => '1',
		self::CIRCLES_ALLOW_LISTED_CIRCLES       => '1',
		self::CIRCLES_ALLOW_ANY_GROUP_MEMBERS    => '1',
		self::CIRCLES_ALLOW_LINKED_GROUPS        => '0',
		self::CIRCLES_ALLOW_FEDERATED_CIRCLES    => '0',
		self::CIRCLES_ALLOW_NON_SSL_LINKS        => '0',
		self::CIRCLES_NON_SSL_LOCAL              => '0',
		self::CIRCLES_ACTIVITY_ON_CREATION       => '1',
		self::CIRCLES_SKIP_INVITATION_STEP       => '0'
	];

	/** @var string */
	private $appName;

	/** @var IConfig */
	private $config;

	/** @var string */
	private $userId;

	/** @var IRequest */
	private $request;

	/** @var MiscService */
	private $miscService;

	/** @var int */
	private $allowedCircle = -1;

	/** @var int */
	private $allowFilesFilteredByCircles = -1;

	/** @var int */
	private $allowedListedCircles = -1;

	/** @var int */
	private $allowAddingAnyGroupMembers = -1;

	/** @var int */
	private $allowedLinkedGroups = -1;

	/** @var int */
	private $allowedFederatedCircles = -1;

	/** @var int */
	private $allowedNonSSLLinks = -1;

	/** @var int */
	private $localNonSSL = -1;

	/** @var string */
	private $groupBackendNamePrefix = null;

	/** @var string */
	private $groupBackendNameSuffix = null;

	/**
	 * ConfigService constructor.
	 *
	 * @param string $appName
	 * @param IConfig $config
	 * @param IRequest $request
	 * @param string $userId
	 * @param MiscService $miscService
	 */
	public function __construct(
		$appName, IConfig $config, IRequest $request, $userId, MiscService $miscService
	) {
		$this->appName = $appName;
		$this->config = $config;
		$this->request = $request;
		$this->userId = $userId;
		$this->miscService = $miscService;
	}


	public function getLocalAddress() {
		return (($this->isLocalNonSSL()) ? 'http://' : '')
			   . $this->request->getServerHost();
	}


	/**
	 * returns if this type of circle is allowed by the current configuration.
	 *
	 * @param $type
	 *
	 * @return int
	 */
	public function isCircleAllowed($type) {
		if ($this->allowedCircle === -1) {
			$this->allowedCircle = (int)$this->getAppValue(self::CIRCLES_ALLOW_CIRCLES);
		}

		return ((int)$type & (int)$this->allowedCircle);
	}

	/**
	 * returns if the files list could be filtered by circles
	 *
	 * @return bool
	 */
	public function isFilesFilteredCirclesAllowed() {
		if ($this->allowFilesFilteredByCircles === -1) {
			$this->allowFilesFilteredByCircles =
				(int)$this->getAppValue(self::CIRCLES_ALLOW_FILES_CIRCLES_FILTER);
		}

		return ($this->allowFilesFilteredByCircles === 1);
	}

	/**
	 * returns if the circles are allowed to be listed outside the Circles application.
	 *
	 * @return bool
	 */
	public function isListedCirclesAllowed() {
		if ($this->allowedListedCircles === -1) {
			$this->allowedListedCircles =
				(int)$this->getAppValue(self::CIRCLES_ALLOW_LISTED_CIRCLES);
		}

		return ($this->allowedListedCircles === 1);
	}

	/**
	 * returns if the current user is allowed to add any group members.
	 * even if he isn't a member of these groups
	 *
	 * @return bool
	 */
	public function isAddingAnyGroupMembersAllowed() {
		if ($this->allowAddingAnyGroupMembers === -1) {
			$this->allowAddingAnyGroupMembers =
				(int)$this->getAppValue(self::CIRCLES_ALLOW_ANY_GROUP_MEMBERS);
		}

		return ($this->allowAddingAnyGroupMembers === 1);
	}

	/**
	 * @return bool
	 */
	public function isLinkedGroupsAllowed() {
		if ($this->allowedLinkedGroups === -1) {
			$this->allowedLinkedGroups =
				(int)$this->getAppValue(self::CIRCLES_ALLOW_LINKED_GROUPS);
		}

		return ($this->allowedLinkedGroups === 1);
	}


	/**
	 * @return bool
	 */
	public function isFederatedCirclesAllowed() {
		if ($this->allowedFederatedCircles === -1) {
			$this->allowedFederatedCircles =
				(int)$this->getAppValue(self::CIRCLES_ALLOW_FEDERATED_CIRCLES);
		}

		return ($this->allowedFederatedCircles === 1);
	}

	/**
	 * @return bool
	 */
	public function isInvitationSkipped() {
		return (int)$this->getAppValue(self::CIRCLES_SKIP_INVITATION_STEP) === 1;
	}

	/**
	 * @return bool
	 */
	public function isLocalNonSSL() {
		if ($this->localNonSSL === -1) {
			$this->localNonSSL =
				(int)$this->getAppValue(self::CIRCLES_NON_SSL_LOCAL);
		}

		return ($this->localNonSSL === 1);
	}


	/**
	 * @return bool
	 */
	public function isNonSSLLinksAllowed() {
		if ($this->allowedNonSSLLinks === -1) {
			$this->allowedNonSSLLinks =
				(int)$this->getAppValue(self::CIRCLES_ALLOW_NON_SSL_LINKS);
		}

		return ($this->allowedNonSSLLinks === 1);
	}


	/**
	 * @param string $remote
	 *
	 * @return string
	 */
	public function generateRemoteHost($remote) {
		if ((!$this->isNonSSLLinksAllowed() || strpos($remote, 'http://') !== 0)
			&& strpos($remote, 'https://') !== 0
		) {
			$remote = 'https://' . $remote;
		}

		return rtrim($remote, '/');
	}


	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getCoreValue($key) {
		$defaultValue = null;

		return $this->config->getAppValue('core', $key, $defaultValue);
	}


	/**
	 * Get available hosts
	 *
	 * @return array
	 */
	public function getAvailableHosts(): array {
		return $this->config->getSystemValue('trusted_domains', []);
	}


	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue($key) {
		$defaultValue = null;

		if (array_key_exists($key, $this->defaults)) {
			$defaultValue = $this->defaults[$key];
		}

		return $this->config->getAppValue($this->appName, $key, $defaultValue);
	}

	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function setAppValue($key, $value) {
		$this->config->setAppValue($this->appName, $key, $value);
	}

	/**
	 * remove a key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function deleteAppValue($key) {
		return $this->config->deleteAppValue($this->appName, $key);
	}

	/**
	 * Get a user value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getUserValue($key) {
		return $this->config->getUserValue($this->userId, $this->appName, $key);
	}

	/**
	 * Set a user value by key
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 * @throws PreConditionNotMetException
	 */
	public function setUserValue($key, $value) {
		return $this->config->setUserValue($this->userId, $this->appName, $key, $value);
	}


	/**
	 * Get a user value by key and user
	 *
	 * @param string $userId
	 * @param string $key
	 *
	 * @param string $default
	 *
	 * @return string
	 */
	public function getCoreValueForUser($userId, $key, $default = '') {
		return $this->config->getUserValue($userId, 'core', $key, $default);
	}


	/**
	 * Get a user value by key and user
	 *
	 * @param string $userId
	 * @param string $key
	 *
	 * @return string
	 */
	public function getValueForUser($userId, $key) {
		return $this->config->getUserValue($userId, $this->appName, $key);
	}

	/**
	 * Set a user value by key
	 *
	 * @param string $userId
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 * @throws PreConditionNotMetException
	 */
	public function setValueForUser($userId, $key, $value) {
		return $this->config->setUserValue($userId, $this->appName, $key, $value);
	}

	/**
	 * return the cloud version.
	 * if $complete is true, return a string x.y.z
	 *
	 * @param boolean $complete
	 *
	 * @return string|integer
	 */
	public function getCloudVersion($complete = false) {
		$ver = Util::getVersion();

		if ($complete) {
			return implode('.', $ver);
		}

		return $ver[0];
	}


	/**
	 * @return bool
	 */
	public function isAccountOnly() {
		return ($this->getAppValue(self::CIRCLES_ACCOUNTS_ONLY) === '1');
	}


	/**
	 * @return bool
	 */
	public function isContactsBackend(): bool {
		return ($this->getAppValue(ConfigService::CIRCLES_CONTACT_BACKEND) !== '0');
	}


	public function contactsBackendType(): int {
		return (int)$this->getAppValue(ConfigService::CIRCLES_CONTACT_BACKEND);
	}

	/**
	 * @return bool
	 */
	public function isGroupsBackend(): bool {
		return ($this->getAppValue(ConfigService::CIRCLES_GROUP_BACKEND) !== '0');
	}

	/**
	 * returns the prefix of the group name
	 *
	 * @return string|null
	 */
	public function getGroupBackendNamePrefix() {
		if ($this->groupBackendNamePrefix === null && $this->isGroupsBackend()) {
			$this->groupBackendNamePrefix = ltrim((string) $this->getAppValue(self::CIRCLES_GROUP_BACKEND_NAME_PREFIX));
		}

		return $this->groupBackendNamePrefix;
	}

	/**
	 * returns the suffix of the group name
	 *
	 * @return string|null
	 */
	public function getGroupBackendNameSuffix() {
		if ($this->groupBackendNameSuffix === null && $this->isGroupsBackend()) {
			$l = OC::$server->getL10N('circles');
			$defaultSuffix = ' '.$l->t('Circle');
			$customSuffix = (string) $this->getAppValue(self::CIRCLES_GROUP_BACKEND_NAME_SUFFIX);
			$this->groupBackendNameSuffix = rtrim($customSuffix ?: $defaultSuffix);
		}

		return $this->groupBackendNameSuffix;
	}

	/**
	 * @return bool
	 */
	public function stillFrontEnd(): bool {
		if ($this->getAppValue(self::CIRCLES_CONTACT_BACKEND) !== '1') {
			return true;
		}

		if ($this->getAppValue(self::CIRCLES_STILL_FRONTEND) === '1') {
			return true;
		}

		return false;
	}


	/**
	 * should the password for a mail share be send to the recipient
	 *
	 * @return bool
	 */
	public function sendPasswordByMail() {
		if ($this->getAppValue(self::CIRCLES_CONTACT_BACKEND) === '1') {
			return false;
		}

		return ($this->config->getAppValue('sharebymail', 'sendpasswordmail', 'yes') === 'yes');
	}

	/**
	 * do we require a share by mail to be password protected
	 *
	 * @return bool
	 */
	public function enforcePasswordProtection() {
		if ($this->getAppValue(self::CIRCLES_CONTACT_BACKEND) === '1') {
			return false;
		}

		return ($this->config->getAppValue('sharebymail', 'enforcePasswordProtection', 'no') === 'yes');
	}


	public function getInstanceId() {
		return $this->config->getSystemValue('instanceid');
	}

}
