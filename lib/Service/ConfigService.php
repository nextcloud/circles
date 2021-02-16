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

use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Model\DeprecatedCircle;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\PreConditionNotMetException;
use OCP\Util;

class ConfigService {


	use TStringTools;


	const CIRCLES_ALLOW_CIRCLES = 'allow_circles';
	const CIRCLES_CONTACT_BACKEND = 'contact_backend';
	const CIRCLES_STILL_FRONTEND = 'still_frontend';
	const CIRCLES_SWAP_TO_TEAMS = 'swap_to_teams';
	const CIRCLES_ALLOW_FEDERATED_CIRCLES = 'allow_federated';
	const CIRCLES_GS_ENABLED = 'gs_enabled';
	const CIRCLES_MEMBERS_LIMIT = 'members_limit';
	const CIRCLES_ACCOUNTS_ONLY = 'accounts_only';
	const CIRCLES_ALLOW_LINKED_GROUPS = 'allow_linked_groups';
	const CIRCLES_ALLOW_NON_SSL_LINKS = 'allow_non_ssl_links';
	const CIRCLES_NON_SSL_LOCAL = 'local_is_non_ssl';
	const CIRCLES_SELF_SIGNED = 'self_signed_cert';
	const CIRCLES_LOCAL_GSKEY = 'local_gskey';
	const CIRCLES_ACTIVITY_ON_CREATION = 'creation_activity';
	const CIRCLES_SKIP_INVITATION_STEP = 'skip_invitation_to_closed_circles';
	const CIRCLES_SEARCH_FROM_COLLABORATOR = 'search_from_collaborator';
	const CIRCLES_TEST_ASYNC_LOCK = 'test_async_lock';
	const CIRCLES_TEST_ASYNC_INIT = 'test_async_init';
	const CIRCLES_TEST_ASYNC_HAND = 'test_async_hand';
	const CIRCLES_TEST_ASYNC_COUNT = 'test_async_count';

	const LOCAL_CLOUD_ID = 'local_cloud_id';
	const LOCAL_CLOUD_SCHEME = 'local_cloud_scheme';
	const FORCE_NC_BASE = 'force_nc_base';
	const TEST_NC_BASE = 'test_nc_base';

	const GS_ENABLED = 'enabled';
	const GS_MODE = 'mode';
	const GS_KEY = 'key';
	const GS_LOOKUP = 'lookup';

	const GS_LOOKUP_INSTANCES = '/instances';
	const GS_LOOKUP_USERS = '/users';


	private $defaults = [
		self::CIRCLES_ALLOW_CIRCLES            => DeprecatedCircle::CIRCLES_ALL,
		self::CIRCLES_CONTACT_BACKEND          => '0',
		self::CIRCLES_STILL_FRONTEND           => '0',
		self::CIRCLES_TEST_ASYNC_INIT          => '0',
		self::CIRCLES_SWAP_TO_TEAMS            => '0',
		self::CIRCLES_ACCOUNTS_ONLY            => '0',
		self::CIRCLES_MEMBERS_LIMIT            => '50',
		self::CIRCLES_ALLOW_LINKED_GROUPS      => '0',
		self::CIRCLES_ALLOW_FEDERATED_CIRCLES  => '0',
		self::CIRCLES_GS_ENABLED               => '0',
		self::CIRCLES_LOCAL_GSKEY              => '',
		self::CIRCLES_ALLOW_NON_SSL_LINKS      => '0',
		self::CIRCLES_NON_SSL_LOCAL            => '0',
		self::CIRCLES_SELF_SIGNED              => '0',
		self::LOCAL_CLOUD_ID                   => '',
		self::LOCAL_CLOUD_SCHEME               => 'https',
		self::FORCE_NC_BASE                    => '',
		self::TEST_NC_BASE                     => '',
		self::CIRCLES_ACTIVITY_ON_CREATION     => '1',
		self::CIRCLES_SKIP_INVITATION_STEP     => '0',
		self::CIRCLES_SEARCH_FROM_COLLABORATOR => '0'
	];

	/** @var string */
	private $appName;

	/** @var IConfig */
	private $config;

	/** @var string */
	private $userId;

	/** @var IRequest */
	private $request;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var MiscService */
	private $miscService;

	/** @var int */
	private $allowedCircle = -1;

	/** @var int */
	private $allowedLinkedGroups = -1;

	/** @var int */
	private $allowedFederatedCircles = -1;

	/** @var int */
	private $allowedNonSSLLinks = -1;

	/** @var int */
	private $localNonSSL = -1;

	/**
	 * ConfigService constructor.
	 *
	 * @param string $appName
	 * @param IConfig $config
	 * @param IRequest $request
	 * @param string $userId
	 * @param IURLGenerator $urlGenerator
	 * @param MiscService $miscService
	 */
	public function __construct(
		$appName, IConfig $config, IRequest $request, $userId, IURLGenerator $urlGenerator,
		MiscService $miscService
	) {
		$this->appName = $appName;
		$this->config = $config;
		$this->request = $request;
		$this->userId = $userId;
		$this->urlGenerator = $urlGenerator;
		$this->miscService = $miscService;
	}


	/**
	 * @return string
	 * @deprecated
	 */
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
	 * @return bool
	 * @throws GSStatusException
	 */
	public function isLinkedGroupsAllowed() {
		if ($this->allowedLinkedGroups === -1) {
			$allowed = ($this->getAppValue(self::CIRCLES_ALLOW_LINKED_GROUPS) === '1'
						&& !$this->getGSStatus(self::GS_ENABLED));
			$this->allowedLinkedGroups = ($allowed) ? 1 : 0;
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
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getSystemValue($key) {
		$defaultValue = null;

		return $this->config->getSystemValue($key, $defaultValue);
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
	public function deleteAppValue($key): string {
		return $this->config->deleteAppValue($this->appName, $key);
	}


	/**
	 *
	 */
	public function unsetAppConfig() {
		$this->config->deleteAppValues(Application::APP_ID);
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
		return ($this->getAppValue(ConfigService::CIRCLES_CONTACT_BACKEND) !== '0'
				&& $this->getAppValue(ConfigService::CIRCLES_CONTACT_BACKEND) !== '');
	}


	/**
	 * @return int
	 */
	public function contactsBackendType(): int {
		return (int)$this->getAppValue(ConfigService::CIRCLES_CONTACT_BACKEND);
	}


	/**
	 * @return bool
	 */
	public function stillFrontEnd(): bool {
		if (!$this->isContactsBackend()) {
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
		if ($this->isContactsBackend()) {
			return false;
		}

		return ($this->config->getAppValue('sharebymail', 'sendpasswordmail', 'yes') === 'yes');
	}

	/**
	 * do we require a share by mail to be password protected
	 *
	 * @param DeprecatedCircle $circle
	 *
	 * @return bool
	 */
	public function enforcePasswordProtection(DeprecatedCircle $circle) {
		if ($this->isContactsBackend()) {
			return false;
		}

		if ($circle->getSetting('password_enforcement') === 'true') {
			return true;
		}

		return ($this->config->getAppValue('sharebymail', 'enforcePasswordProtection', 'no') === 'yes');
	}


	/**
	 * @param string $type
	 *
	 * @return array|bool|mixed
	 * @throws GSStatusException
	 */
	public function getGSStatus(string $type = '') {
		$enabled = $this->config->getSystemValueBool('gs.enabled', false);
		$lookup = $this->config->getSystemValue('lookup_server', '');

		if ($lookup === '' || !$enabled) {
			if ($type === self::GS_ENABLED) {
				return false;
			}

			throw new GSStatusException('GS and lookup are not configured : ' . $lookup . ', ' . $enabled);
		}

		$clef = $this->config->getSystemValue('gss.jwt.key', '');
		$mode = $this->config->getSystemValue('gss.mode', '');

		switch ($type) {
			case self::GS_ENABLED:
				return $enabled;

			case self::GS_MODE:
				return $mode;

			case self::GS_KEY:
				return $clef;

			case self::GS_LOOKUP:
				return $lookup;
		}

		return [
			self::GS_ENABLED => $enabled,
			self::GS_LOOKUP  => $lookup,
			self::GS_MODE    => $clef,
			self::GS_KEY     => $mode,
		];
	}


	/**
	 * @return array
	 */
	public function getTrustedDomains(): array {
		return array_values($this->config->getSystemValue('trusted_domains', []));
	}


	/**
	 * - returns host+port, does not specify any protocol
	 * - can be forced using LOCAL_CLOUD_ID
	 * - use 'overwrite.cli.url'
	 * - can use the first entry from trusted_domains if LOCAL_CLOUD_ID = 'use-trusted-domain'
	 * - used mainly to assign instance and source to a request
	 * - important only in remote environment; can be totally random in a jailed environment
	 *
	 * @return string
	 */
	public function getLocalInstance(): string {
		$localCloudId = $this->getAppValue(self::LOCAL_CLOUD_ID);
		if ($localCloudId === '') {
			$cliUrl = $this->config->getSystemValue('overwrite.cli.url', '');
			$local = parse_url($cliUrl);
			if (!is_array($local) || !array_key_exists('host', $local)) {
				if ($cliUrl !== '') {
					return $cliUrl;
				}

				$randomCloudId = $this->uuid();
				$this->setAppValue(self::LOCAL_CLOUD_ID, $randomCloudId);

				return $randomCloudId;
			}

			if (array_key_exists('port', $local)) {
				return $local['host'] . ':' . $local['port'];
			} else {
				return $local['host'];
			}
		} else if ($localCloudId === 'use-trusted-domain') {
			return $this->getTrustedDomains()[0];
		} else {
			return $localCloudId;
		}
	}


	/**
	 * returns address based on LOCAL_CLOUD_ID, LOCAL_CLOUD_SCHEME and a routeName
	 * perfect for urlId in ActivityPub env.
	 *
	 * @param string $route
	 * @param array $args
	 *
	 * @return string
	 */
	public function getRemotePath(string $route = 'circles.Navigation.navigate', array $args = []): string {
		$base = $this->getAppValue(self::LOCAL_CLOUD_SCHEME) . '://' . $this->getLocalInstance();

		if ($route === '') {
			return $base;
		}

		return $base . $this->urlGenerator->linkToRoute($route, $args);
	}

	/**
	 * @param string $instance
	 *
	 * @return bool
	 */
	public function isLocalInstance(string $instance): bool {
		if ($instance === $this->getLocalInstance()) {
			return true;
		}

		if ($this->getAppValue(self::LOCAL_CLOUD_ID) === 'use-trusted-domain') {
			return (in_array($instance, $this->getTrustedDomains()));
		}

		return false;
	}


	/**
	 * @param NC21Request $request
	 * @param string $routeName
	 * @param array $args
	 */
	public function configureRequest(NC21Request $request, string $routeName = '', array $args = []) {
		$this->configureRequestAddress($request, $routeName, $args);

		if ($this->getForcedNcBase() === '') {
			$request->setProtocols(['https', 'http']);
		}

		$request->setVerifyPeer($this->getAppValue(ConfigService::CIRCLES_SELF_SIGNED) !== '1');
		$request->setLocalAddressAllowed(true);
		$request->setFollowLocation(true);
		$request->setTimeout(5);
	}

	/**
	 * - Create route using overwrite.cli.url.
	 * - can be forced using FORCE_NC_BASE or TEST_BC_BASE (temporary)
	 * - can also be overwritten in config/config.php: 'circles.force_nc_base'
	 * - perfect for loopback request.
	 *
	 * @param NC21Request $request
	 * @param string $routeName
	 * @param array $args
	 *
	 * @return string
	 */
	private function configureRequestAddress(NC21Request $request, string $routeName, array $args = []) {
		if ($routeName === '') {
			return;
		}

		$ncBase = $this->getForcedNcBase();
		if ($ncBase !== '') {
			$absolute = $this->cleanLinkToRoute($ncBase, $routeName, $args);
		} else {
			$absolute = $this->urlGenerator->linkToRouteAbsolute($routeName, $args);
		}

		$request->basedOnUrl($absolute);
	}


	/**
	 * - return force_nc_base from config/config.php, then from FORCE_NC_BASE.
	 *
	 * @return string
	 */
	private function getForcedNcBase(): string {
		if ($this->getAppValue(self::TEST_NC_BASE) !== '') {
			return $this->getAppValue(self::TEST_NC_BASE);
		}

		$fromConfig = $this->config->getSystemValue('circles.force_nc_base', '');
		if ($fromConfig !== '') {
			return $fromConfig;
		}

		return $this->getAppValue(self::FORCE_NC_BASE);
	}


	/**
	 * sometimes, linkToRoute will include the base path to the nextcloud which will be duplicate with ncBase
	 *
	 * @param string $ncBase
	 * @param string $routeName
	 * @param array $args
	 *
	 * @return string
	 */
	private function cleanLinkToRoute(string $ncBase, string $routeName, array $args): string {
		$link = $this->urlGenerator->linkToRoute($routeName, $args);
		$forcedPath = rtrim(parse_url($ncBase, PHP_URL_PATH), '/');

		if ($forcedPath !== '' && strpos($link, $forcedPath) === 0) {
			$ncBase = substr($ncBase, 0, -strlen($forcedPath));
		}

		return rtrim($ncBase, '/') . $link;
	}

}

