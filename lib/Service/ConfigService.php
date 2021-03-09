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
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\Member;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;

class ConfigService {


	use TStringTools;


	const FRONTAL_CLOUD_ID = 'frontal_cloud_id';
	const FRONTAL_CLOUD_SCHEME = 'frontal_cloud_scheme';
	const INTERNAL_CLOUD_ID = 'internal_cloud_id';
	const INTERNAL_CLOUD_SCHEME = 'internal_cloud_scheme';
	const LOOPBACK_CLOUD_ID = 'loopback_cloud_id';
	const LOOPBACK_CLOUD_SCHEME = 'loopback_cloud_scheme';
	const SELF_SIGNED_CERT = 'self_signed_cert';
	const MEMBERS_LIMIT = 'members_limit';
	const ACTIVITY_ON_NEW_CIRCLE = 'creation_activity';

	// deprecated
	const CIRCLES_CONTACT_BACKEND = 'contact_backend';
	const CIRCLES_ACCOUNTS_ONLY = 'accounts_only'; // only UserType=1
	const CIRCLES_SEARCH_FROM_COLLABORATOR = 'search_from_collaborator';


	const FORCE_NC_BASE = 'force_nc_base';
	const TEST_NC_BASE = 'test_nc_base';

	const GS_ENABLED = 'enabled';
	const GS_MODE = 'mode';
	const GS_KEY = 'key';
	const GS_LOOKUP = 'lookup';
	const GS_MOCKUP = 'mockup';

	const GS_LOOKUP_INSTANCES = '/instances';
	const GS_LOOKUP_USERS = '/users';


	private $defaults = [
		self::FRONTAL_CLOUD_ID       => '',
		self::FRONTAL_CLOUD_SCHEME   => 'https',
		self::INTERNAL_CLOUD_ID      => '',
		self::INTERNAL_CLOUD_SCHEME  => 'https',
		self::LOOPBACK_CLOUD_ID      => '',
		self::LOOPBACK_CLOUD_SCHEME  => 'https',
		self::SELF_SIGNED_CERT       => '0',
		self::MEMBERS_LIMIT          => '50',
		self::ACTIVITY_ON_NEW_CIRCLE => '1',

		self::FORCE_NC_BASE                    => '',
		self::TEST_NC_BASE                     => '',
		self::CIRCLES_CONTACT_BACKEND          => '0',
		self::CIRCLES_ACCOUNTS_ONLY            => '0',
		self::CIRCLES_SEARCH_FROM_COLLABORATOR => '0',
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
	 *
	 */
	public function unsetAppConfig() {
		$this->config->deleteAppValues(Application::APP_ID);
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
	 * // TODO: fetch data from somewhere else than hard coded...
	 *
	 * @return array
	 */
	public function getSettings(): array {
		return [
			'allowedCircles' => Circle::$DEF_CFG_MAX,
			'allowedUserTypes' => Member::$DEF_TYPE_MAX,
			'membersLimit'   => $this->getAppValue(self::MEMBERS_LIMIT)
		];
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
		$mockup = $this->config->getSystemValue('gss.mockup', []);

		if ($lookup === '' || !$enabled) {
			if ($type === self::GS_ENABLED) {
				return false;
			}

			if ($type !== self::GS_MOCKUP) {
				throw new GSStatusException(
					'GS and lookup are not configured : ' . $lookup . ', ' . $enabled
				);
			}
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

			case self::GS_MOCKUP:
				return $mockup;
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
	 * - can be forced using FRONTAL_CLOUD_ID
	 * - use 'overwrite.cli.url'
	 * - can use the first entry from trusted_domains if FRONTAL_CLOUD_ID = 'use-trusted-domain'
	 * - used mainly to assign instance and source to a request
	 * - important only in remote environment; can be totally random in a jailed environment
	 *
	 * @return string
	 */
	public function getFrontalInstance(): string {
		$frontalCloudId = $this->getAppValue(self::FRONTAL_CLOUD_ID);

		// using old settings - Deprecated in NC25
		if ($frontalCloudId === '') {
			$frontalCloudId = $this->config->getAppValue($this->appName, 'local_cloud_id', '');
			$this->setAppValue(self::FRONTAL_CLOUD_ID, $frontalCloudId);
		}

		if ($frontalCloudId === '') {
			$cliUrl = $this->config->getSystemValue('overwrite.cli.url', '');
			$frontal = parse_url($cliUrl);
			if (!is_array($frontal) || !array_key_exists('host', $frontal)) {
				if ($cliUrl !== '') {
					return $cliUrl;
				}

				$randomCloudId = $this->uuid();
				$this->setAppValue(self::FRONTAL_CLOUD_ID, $randomCloudId);

				return $randomCloudId;
			}

			if (array_key_exists('port', $frontal)) {
				return $frontal['host'] . ':' . $frontal['port'];
			} else {
				return $frontal['host'];
			}
		} else if ($frontalCloudId === 'use-trusted-domain') {
			return $this->getTrustedDomains()[0];
		} else {
			return $frontalCloudId;
		}
	}


	/**
	 * returns address based on FRONTAL_CLOUD_ID, FRONTAL_CLOUD_SCHEME and a routeName
	 * perfect for urlId in ActivityPub env.
	 *
	 * @param string $route
	 * @param array $args
	 *
	 * @return string
	 */
	public function getFrontalPath(string $route = 'circles.Remote.appService', array $args = []): string {
		$base = $this->getAppValue(self::FRONTAL_CLOUD_SCHEME) . '://' . $this->getFrontalInstance();

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
		if ($instance === $this->getFrontalInstance()) {
			return true;
		}

		if ($this->getAppValue(self::FRONTAL_CLOUD_ID) === 'use-trusted-domain') {
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

		$request->setVerifyPeer($this->getAppValue(ConfigService::SELF_SIGNED_CERT) !== '1');
		$request->setHttpErrorsAllowed(true);
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
	 */
	private function configureRequestAddress(
		NC21Request $request,
		string $routeName,
		array $args = []
	): void {
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

