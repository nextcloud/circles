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


namespace OCA\Circles\Service;

use OC;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\IConfig;
use OCP\IURLGenerator;

/**
 * Class ConfigService
 *
 * @package OCA\Circles\Service
 */
class ConfigService {
	use TStringTools;
	use TArrayTools;
	use TNCLogger;


	public const FRONTAL_CLOUD_BASE = 'frontal_cloud_base';
	public const FRONTAL_CLOUD_ID = 'frontal_cloud_id';
	public const FRONTAL_CLOUD_SCHEME = 'frontal_cloud_scheme';
	public const FRONTAL_CLOUD_PATH = 'frontal_cloud_path';
	public const INTERNAL_CLOUD_ID = 'internal_cloud_id';
	public const INTERNAL_CLOUD_SCHEME = 'internal_cloud_scheme';
	public const INTERNAL_CLOUD_PATH = 'internal_cloud_path';
	public const LOOPBACK_CLOUD_ID = 'loopback_cloud_id';
	public const LOOPBACK_CLOUD_SCHEME = 'loopback_cloud_scheme';
	public const LOOPBACK_CLOUD_PATH = 'loopback_cloud_path';
	public const IFACE0_CLOUD_ID = 'iface0_cloud_id';
	public const IFACE0_CLOUD_SCHEME = 'iface0_cloud_scheme';
	public const IFACE0_CLOUD_PATH = 'iface0_cloud_path';
	public const IFACE0_INTERNAL = 'iface0_internal';
	public const IFACE1_CLOUD_ID = 'iface1_cloud_id';
	public const IFACE1_CLOUD_SCHEME = 'iface1_cloud_scheme';
	public const IFACE1_CLOUD_PATH = 'iface1_cloud_path';
	public const IFACE1_INTERNAL = 'iface1_internal';
	public const IFACE2_CLOUD_ID = 'iface2_cloud_id';
	public const IFACE2_CLOUD_SCHEME = 'iface2_cloud_scheme';
	public const IFACE2_CLOUD_PATH = 'iface2_cloud_path';
	public const IFACE2_INTERNAL = 'iface2_internal';
	public const IFACE3_CLOUD_ID = 'iface3_cloud_id';
	public const IFACE3_CLOUD_SCHEME = 'iface3_cloud_scheme';
	public const IFACE3_CLOUD_PATH = 'iface3_cloud_path';
	public const IFACE3_INTERNAL = 'iface3_internal';
	public const IFACE4_CLOUD_ID = 'iface4_cloud_id';
	public const IFACE4_CLOUD_SCHEME = 'iface4_cloud_scheme';
	public const IFACE4_CLOUD_PATH = 'iface4_cloud_path';
	public const IFACE4_INTERNAL = 'iface4_internal';
	public const IFACE_TEST_ID = 'iface_test_id';
	public const IFACE_TEST_SCHEME = 'iface_test_scheme';
	public const IFACE_TEST_PATH = 'iface_test_path';
	public const IFACE_TEST_TOKEN = 'iface_test_token';
	public const LOOPBACK_TMP_ID = 'loopback_tmp_id';
	public const LOOPBACK_TMP_SCHEME = 'loopback_tmp_scheme';
	public const LOOPBACK_TMP_PATH = 'loopback_tmp_path';

	public const HARD_MODERATION = 'hard_moderation';
	public const FRONTEND_ENABLED = 'frontend_enabled';
	public const KEYHOLE_CFG_REQUEST = 'keyhole_cfg_request';
	public const ROUTE_TO_CIRCLE = 'route_to_circle';
	public const EVENT_EXAMPLES = 'event_examples';
	public const ENFORCE_PASSWORD = 'enforce_password';

	public const SELF_SIGNED_CERT = 'self_signed_cert';
	public const MEMBERS_LIMIT = 'members_limit';
	public const ACTIVITY_ON_NEW_CIRCLE = 'creation_activity';
	public const ALLOWED_TYPES = 'allowed_types';
	public const CIRCLE_TYPES_FORCE = 'circle_types_force';
	public const CIRCLE_TYPES_BLOCK = 'circle_types_block';

	public const BYPASS_CIRCLE_TYPES = 'bypass_circle_types';
	public const LIMIT_CIRCLE_CREATION = 'limit_circle_creation';

	public const MIGRATION_BYPASS = 'migration_bypass';
	public const MIGRATION_22 = 'migration_22';
	public const MIGRATION_22_1 = 'migration_22_1';
	public const MIGRATION_22_CONFIRMED = 'migration_22_confirmed';
	public const MIGRATION_RUN = 'migration_run';
	public const MAINTENANCE_UPDATE = 'maintenance_update';
	public const MAINTENANCE_RUN = 'maintenance_run';

	public const GS_MODE = 'mode';
	public const GS_KEY = 'key';

	public const GS_LOOKUP_INSTANCES = '/instances';
	public const GS_LOOKUP_USERS = '/users';


	// deprecated -- removing in NC25
	public const CIRCLES_CONTACT_BACKEND = 'contact_backend';
	public const CIRCLES_ACCOUNTS_ONLY = 'accounts_only'; // only UserType=1
	public const CIRCLES_SEARCH_FROM_COLLABORATOR = 'search_from_collaborator';

	public const FORCE_NC_BASE = 'force_nc_base';
	public const TEST_NC_BASE = 'test_nc_base';


	private static $defaults = [
		self::FRONTAL_CLOUD_BASE => '',
		self::FRONTAL_CLOUD_ID => '',
		self::FRONTAL_CLOUD_SCHEME => 'https',
		self::FRONTAL_CLOUD_PATH => '',
		self::INTERNAL_CLOUD_ID => '',
		self::INTERNAL_CLOUD_SCHEME => 'https',
		self::INTERNAL_CLOUD_PATH => '',
		self::LOOPBACK_CLOUD_ID => '',
		self::LOOPBACK_CLOUD_SCHEME => 'https',
		self::LOOPBACK_CLOUD_PATH => '',
		self::IFACE0_CLOUD_ID => '',
		self::IFACE0_CLOUD_SCHEME => 'https',
		self::IFACE0_CLOUD_PATH => '',
		self::IFACE0_INTERNAL => '0',
		self::IFACE1_CLOUD_ID => '',
		self::IFACE1_CLOUD_SCHEME => 'https',
		self::IFACE1_CLOUD_PATH => '',
		self::IFACE1_INTERNAL => '0',
		self::IFACE2_CLOUD_ID => '',
		self::IFACE2_CLOUD_SCHEME => 'https',
		self::IFACE2_CLOUD_PATH => '',
		self::IFACE2_INTERNAL => '0',
		self::IFACE3_CLOUD_ID => '',
		self::IFACE3_CLOUD_SCHEME => 'https',
		self::IFACE3_CLOUD_PATH => '',
		self::IFACE3_INTERNAL => '0',
		self::IFACE4_CLOUD_ID => '',
		self::IFACE4_CLOUD_SCHEME => 'https',
		self::IFACE4_CLOUD_PATH => '',
		self::IFACE4_INTERNAL => '0',
		self::IFACE_TEST_ID => '',
		self::IFACE_TEST_SCHEME => 'https',
		self::IFACE_TEST_PATH => '',
		self::IFACE_TEST_TOKEN => '',
		self::LOOPBACK_TMP_ID => '',
		self::LOOPBACK_TMP_SCHEME => '',
		self::LOOPBACK_TMP_PATH => '',

		self::FRONTEND_ENABLED => '1',
		self::HARD_MODERATION => '0',
		self::KEYHOLE_CFG_REQUEST => '0',
		self::ROUTE_TO_CIRCLE => 'contacts.contacts.directcircle',
		self::EVENT_EXAMPLES => '0',
		self::ENFORCE_PASSWORD => '2',

		self::SELF_SIGNED_CERT => '0',
		self::MEMBERS_LIMIT => '-1',
		self::ACTIVITY_ON_NEW_CIRCLE => '0',
		self::ALLOWED_TYPES => Member::ALLOWING_ALL_TYPES,
		self::CIRCLE_TYPES_FORCE => '0',
		self::CIRCLE_TYPES_BLOCK => '0',

		self::BYPASS_CIRCLE_TYPES => '',
		self::LIMIT_CIRCLE_CREATION => '',

		self::MIGRATION_BYPASS => '0',
		self::MIGRATION_22 => '0',
		self::MIGRATION_22_1 => '0',
		self::MIGRATION_22_CONFIRMED => '0',
		self::MIGRATION_RUN => '0',
		self::MAINTENANCE_UPDATE => '[]',
		self::MAINTENANCE_RUN => '0',

		self::FORCE_NC_BASE => '',
		self::TEST_NC_BASE => '',
		self::CIRCLES_CONTACT_BACKEND => '0',
		self::CIRCLES_ACCOUNTS_ONLY => '0',
		self::CIRCLES_SEARCH_FROM_COLLABORATOR => '0',
	];


	public const DISPLAY_NONE = 0;
	public const DISPLAY_AT = 1;
	public const DISPLAY_PARENTHESIS = 2;


	/** @var IConfig */
	private $config;

	/** @var IURLGenerator */
	private $urlGenerator;


	/**
	 * ConfigService constructor.
	 *
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IConfig $config, IURLGenerator $urlGenerator) {
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue(string $key): string {
		if (($value = $this->config->getAppValue(Application::APP_ID, $key, '')) !== '') {
			return $value;
		}

		if (($value = $this->config->getSystemValue(Application::APP_ID . '.' . $key, '')) !== '') {
			return $value;
		}

		return $this->get($key, self::$defaults);
	}

	/**
	 * @param string $key
	 *
	 * @return int
	 */
	public function getAppValueInt(string $key): int {
		return (int)$this->getAppValue($key);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function getAppValueBool(string $key): bool {
		return ($this->getAppValueInt($key) === 1);
	}


	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function setAppValue(string $key, string $value): void {
		$this->config->setAppValue(Application::APP_ID, $key, $value);
	}


	/**
	 *
	 */
	public function unsetAppConfig(): void {
		$this->config->deleteAppValues(Application::APP_ID);
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
	 * Get a user value by key and user
	 *
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
	 * @deprecated
	 */
	public function isContactsBackend(): bool {
		return ($this->getAppValue(ConfigService::CIRCLES_CONTACT_BACKEND) !== '0'
				&& $this->getAppValue(ConfigService::CIRCLES_CONTACT_BACKEND) !== '');
	}


	/**
	 * @return int
	 * @deprecated
	 */
	public function contactsBackendType(): int {
		return (int)$this->getAppValue(ConfigService::CIRCLES_CONTACT_BACKEND);
	}


	/**
	 * true if:
	 *   - password is generated randomly
	 *
	 * @param Circle $circle
	 *
	 * @return bool
	 */
	public function sendPasswordByMail(Circle $circle): bool {
		if (!$this->enforcePasswordOnSharedFile($circle)) {
			return false;
		}

		return (!$this->getBool('password_single_enabled', $circle->getSettings(), false)
				|| $this->get('password_single', $circle->getSettings()) === '');
	}


	/**
	 * true if:
	 * - password enforced for Circle
	 * - single password enabled for Circle
	 * - single password defined within Circle's settings
	 *
	 * @param Circle $circle
	 *
	 * @return bool
	 */
	public function isSinglePasswordAvailable(Circle $circle): bool {
		if (!$this->enforcePasswordOnSharedFile($circle)) {
			return false;
		}

		return ($this->getBool('password_single_enabled', $circle->getSettings(), false)
				&& $this->get('password_single', $circle->getSettings()) !== '');
	}


	/**
	 * true if:
	 *   - global setting of Nextcloud enforce password on shares.
	 *   - setting of Circles' app enforce password on shares.
	 *   - setting for specific Circle enforce password on shares.
	 *
	 * @param Circle $circle
	 *
	 * @return bool
	 */
	public function enforcePasswordOnSharedFile(Circle $circle): bool {
		if ($this->config->getAppValue(
			'core',
			'shareapi_enforce_links_password',
			'no'
		) === 'yes') {
			return true;
		}

		if ($this->getAppValueInt(ConfigService::ENFORCE_PASSWORD) === 1) {
			return true;
		}

		// Compat NC21
		if ($this->getBool('password_enforcement', $circle->getSettings(), false)) {
			return true;
		}

		return $this->getBool('enforce_password', $circle->getSettings(), false);
	}


	/**
	 * // TODO: fetch data from somewhere else than hard coded...
	 *
	 * @return array
	 */
	public function getSettings(): array {
		return [
			'frontendEnabled' => $this->getAppValueBool(self::FRONTEND_ENABLED),
			'allowedCircles' => Circle::$DEF_CFG_MAX,
			'allowedUserTypes' => Member::$DEF_TYPE_MAX,
			'membersLimit' => $this->getAppValueInt(self::MEMBERS_LIMIT)
		];
	}


	/**
	 * @return bool
	 */
	public function isGSAvailable(): bool {
		if (!empty($this->getGSSMockup())) {
			return true;
		}

		return $this->config->getSystemValueBool('gs.enabled', false);
	}


	/**
	 * @return string
	 * @throws GSStatusException
	 */
	public function getGSLookup(): string {
		$lookup = $this->config->getSystemValue('lookup_server', '');

		if (!$this->isGSAvailable() || $lookup === '') {
			throw new  GSStatusException();
		}

		return $lookup;
	}


	/**
	 * @return array
	 */
	public function getGSSMockup(): array {
		return $this->config->getSystemValue('gss.mockup', []);
	}


	/**
	 * @param string $type
	 *
	 * @return string
	 */
	public function getGSInfo(string $type): string {
		$clef = $this->config->getSystemValue('gss.jwt.key', '');
		$mode = $this->config->getSystemValue('gss.mode', '');

		switch ($type) {
			case self::GS_MODE:
				return $mode;

			case self::GS_KEY:
				return $clef;
		}


		return '';
	}


	/**
	 * @return array
	 * @throws GSStatusException
	 */
	public function getGSData(): array {
		return [
			'enabled' => $this->isGSAvailable(),
			'lookup' => $this->getGSLookup(),
			'mockup' => $this->getGSSMockup(),
			self::GS_MODE => $this->config->getSystemValue('gss.mode', ''),
			self::GS_KEY => $this->config->getSystemValue('gss.jwt.key', ''),
		];
	}


	/**
	 * @return array
	 */
	public function getTrustedDomains(): array {
		return array_map(
			function (string $address) {
				return strtolower($address);
			}, $this->config->getSystemValue('trusted_domains', [])
		);
	}


	/**
	 * @return string
	 */
	public function getLoopbackInstance(): string {
		$loopbackCloudId = $this->getAppValue(self::LOOPBACK_TMP_ID);
		if ($loopbackCloudId !== '') {
			return $loopbackCloudId;
		}

		$loopbackCloudId = $this->getAppValue(self::LOOPBACK_CLOUD_ID);
		if ($loopbackCloudId !== '') {
			return $loopbackCloudId;
		}

		$cliUrl = $this->getAppValue(self::FORCE_NC_BASE);
		if ($cliUrl === '') {
			$cliUrl = $this->config->getSystemValue('circles.force_nc_base', '');
		}

		if ($cliUrl === '') {
			$cliUrl = $this->config->getSystemValue('overwrite.cli.url', '');
		}

		$loopback = parse_url($cliUrl);
		if (!is_array($loopback) || !array_key_exists('host', $loopback)) {
			return $cliUrl;
		}

		if (array_key_exists('port', $loopback)) {
			$loopbackCloudId = $loopback['host'] . ':' . $loopback['port'];
		} else {
			$loopbackCloudId = $loopback['host'];
		}

		if (array_key_exists('scheme', $loopback)
			&& $this->getAppValue(self::LOOPBACK_TMP_SCHEME) !== $loopback['scheme']) {
			$this->setAppValue(self::LOOPBACK_TMP_SCHEME, $loopback['scheme']);
		}

		if (array_key_exists('path', $loopback)
			&& $this->getAppValue(self::LOOPBACK_TMP_PATH) !== $loopback['path']) {
			$this->setAppValue(self::LOOPBACK_TMP_PATH, $loopback['path']);
		}

		return $loopbackCloudId;
	}

	/**
	 * returns loopback address based on getLoopbackInstance and LOOPBACK_CLOUD_SCHEME
	 * should be used to async process
	 *
	 * @param string $route
	 * @param array $args
	 *
	 * @return string
	 */
	public function getLoopbackPath(string $route = '', array $args = []): string {
		$instance = $this->getLoopbackInstance();
		$scheme = $this->getAppValue(self::LOOPBACK_TMP_SCHEME);
		if ($scheme === '') {
			$scheme = $this->getAppValue(self::LOOPBACK_CLOUD_SCHEME);
		}

		$path = $this->getAppValue(self::LOOPBACK_TMP_PATH);
		if ($path === '') {
			$path = $this->getAppValue(self::LOOPBACK_CLOUD_PATH);
		}

		$base = $scheme . '://' . $instance . $path;

		if ($route === '') {
			return $base;
		}

		return rtrim($base, '/') . $this->linkToRoute($route, $args);
	}


	/**
	 * - must be configured using INTERNAL_CLOUD_ID
	 * - returns host+port, does not specify any protocol
	 * - used mainly to assign instance and source to a request to local GlobalScale
	 * - important only in GlobalScale environment
	 *
	 * @return string
	 *
	 */
	public function getInternalInstance(): string {
		return $this->getAppValue(self::INTERNAL_CLOUD_ID);
	}


	/**
	 * - must be configured using FRONTAL_CLOUD_ID
	 * - returns host+port, does not specify any protocol
	 * - used mainly to assign instance and source to a request
	 * - important only in remote environment
	 *
	 * @return string
	 */
	public function getFrontalInstance(): string {
		$frontalCloudId = $this->getAppValue(self::FRONTAL_CLOUD_ID);

		// using old settings local_cloud_id from NC20, deprecated in NC25
		if ($frontalCloudId === '') {
			$frontalCloudId = $this->config->getAppValue(Application::APP_ID, 'local_cloud_id', '');
			if ($frontalCloudId !== '') {
				$this->setAppValue(self::FRONTAL_CLOUD_ID, $frontalCloudId);
			}
		}

		return $frontalCloudId;
	}


	/**
	 * @param int $iface
	 *
	 * @return string
	 */
	public function getIfaceInstance(int $iface): string {
		switch ($iface) {
			case InterfaceService::IFACE0:
				return $this->getAppValue(self::IFACE0_CLOUD_ID);
			case InterfaceService::IFACE1:
				return $this->getAppValue(self::IFACE1_CLOUD_ID);
			case InterfaceService::IFACE2:
				return $this->getAppValue(self::IFACE2_CLOUD_ID);
			case InterfaceService::IFACE3:
				return $this->getAppValue(self::IFACE3_CLOUD_ID);
			case InterfaceService::IFACE4:
				return $this->getAppValue(self::IFACE4_CLOUD_ID);
		}

		return '';
	}


	/**
	 * @param string $instance
	 *
	 * @return bool
	 */
	public function isLocalInstance(string $instance): bool {
		if ($instance === '') {
			return true;
		}

		$instance = strtolower($instance);
		if ($instance === strtolower($this->getInternalInstance())) {
			return true;
		}

		if ($instance === strtolower($this->getFrontalInstance())) {
			return true;
		}

		if ($instance === strtolower($this->getLoopbackInstance())) {
			return true;
		}

		return (in_array($instance, $this->getTrustedDomains()));
	}


	/**
	 * @param IFederatedUser $federatedUser
	 * @param bool $displayName
	 * @param bool $displayInstance
	 *
	 * @return string
	 */
	public function displayFederatedUser(
		IFederatedUser $federatedUser,
		bool $displayName = false,
		bool $displayInstance = true
	): string {
		$name = ($displayName) ? $federatedUser->getDisplayName() : $federatedUser->getUserId();
		if ($federatedUser->getUserType() === Member::TYPE_MAIL) {
			return $name . ' ' . $this->displayInstance(
				$federatedUser->getInstance(),
				self::DISPLAY_PARENTHESIS
			);
		}

		if (!$displayInstance) {
			return $name;
		}

		return $name . $this->displayInstance($federatedUser->getInstance(), self::DISPLAY_AT);
	}

	/**
	 * @param string $instance
	 * @param int $type
	 *
	 * @return string
	 */
	public function displayInstance(string $instance, int $type = self::DISPLAY_NONE): string {
		if ($this->isLocalInstance($instance)) {
			return '';
		}

		switch ($type) {
			case self::DISPLAY_AT:
				return '@' . $instance;
			case self::DISPLAY_PARENTHESIS:
				return '(' . $instance . ')';
		}

		return $instance;
	}


	/**
	 * - Create route using getLoopbackAddress()
	 * - perfect for loopback request.
	 *
	 * @param NCRequest $request
	 * @param string $route
	 * @param array $args
	 */
	public function configureLoopbackRequest(
		NCRequest $request,
		string $route = '',
		array $args = []
	): void {
		$this->configureRequest($request);
		$request->setVerifyPeer(false);
		$request->basedOnUrl($this->getLoopbackPath($route, $args));
	}


	/**
	 * @param NCRequest $request
	 */
	public function configureRequest(NCRequest $request): void {
		$request->setVerifyPeer($this->getAppValue(ConfigService::SELF_SIGNED_CERT) !== '1');
		$request->setProtocols(['https', 'http']);
		$request->setHttpErrorsAllowed(true);
		$request->setLocalAddressAllowed(true);
		$request->setFollowLocation(true);
		$request->setTimeout(5);
	}


	/**
	 * @param string $route
	 * @param array $args
	 *
	 * @return string
	 */
	public function linkToRoute(string $route, array $args): string {
		$path = $this->urlGenerator->linkToRoute($route, $args);

		if (OC::$CLI) {
			// when running from the occ command, /index.php is not removed by itself
			if (str_starts_with($path, '/index.php/')
				&& $this->config->getSystemValueString('htaccess.RewriteBase', '') !== '') {
				$path = $this->config->getSystemValueString('htaccess.RewriteBase', '/') . substr($path, 11);
			}

			$knownPath = parse_url($this->config->getSystemValue('overwrite.cli.url'), PHP_URL_PATH);
		} else {
			$knownPath = OC::$WEBROOT;
		}

		$knownPath = $knownPath ? rtrim($knownPath, '/') : '';
		if ($knownPath === '') {
			return $path;
		}

		$pos = strpos($path, $knownPath);
		if ($pos === 0) {
			return substr($path, strlen($knownPath));
		}

		return $path;
	}


	/**
	 * Enforce or Block circle's config/type
	 *
	 * @param Circle $circle
	 */
	public function confirmAllowedCircleTypes(Circle $circle): void {
		$config = $circle->getConfig();
		$config |= $this->getAppValueInt(ConfigService::CIRCLE_TYPES_FORCE);
		$config &= ~$this->getAppValueInt(ConfigService::CIRCLE_TYPES_BLOCK);
		$circle->setConfig($config);
	}
}
