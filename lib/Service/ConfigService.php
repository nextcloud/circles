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

use daita\MySmallPhpTools\Model\Nextcloud\nc22\NC22Request;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\Member;
use OCP\IConfig;
use OCP\IURLGenerator;

class ConfigService {


	use TStringTools;
	use TArrayTools;


	const FRONTAL_CLOUD_ID = 'frontal_cloud_id';
	const FRONTAL_CLOUD_SCHEME = 'frontal_cloud_scheme';
	const INTERNAL_CLOUD_ID = 'internal_cloud_id';
	const INTERNAL_CLOUD_SCHEME = 'internal_cloud_scheme';
	const LOOPBACK_CLOUD_ID = 'loopback_cloud_id';
	const LOOPBACK_CLOUD_SCHEME = 'loopback_cloud_scheme';
	const CHECK_FRONTAL_USING = 'check_frontal_using';
	const CHECK_INTERNAL_USING = 'check_internal_using';
	const SELF_SIGNED_CERT = 'self_signed_cert';
	const MEMBERS_LIMIT = 'members_limit';
	const ACTIVITY_ON_NEW_CIRCLE = 'creation_activity';
	const MIGRATION_22 = 'migration_22';

	const LOOPBACK_TMP_ID = 'loopback_tmp_id';
	const LOOPBACK_TMP_SCHEME = 'loopback_tmp_scheme';

	const GS_MODE = 'mode';
	const GS_KEY = 'key';

	const GS_LOOKUP_INSTANCES = '/instances';
	const GS_LOOKUP_USERS = '/users';


	// deprecated -- removing in NC25
	const CIRCLES_CONTACT_BACKEND = 'contact_backend';
	const CIRCLES_ACCOUNTS_ONLY = 'accounts_only'; // only UserType=1
	const CIRCLES_SEARCH_FROM_COLLABORATOR = 'search_from_collaborator';

	const FORCE_NC_BASE = 'force_nc_base';
	const TEST_NC_BASE = 'test_nc_base';



	private $defaults = [
		self::FRONTAL_CLOUD_ID      => '',
		self::FRONTAL_CLOUD_SCHEME  => 'https',
		self::INTERNAL_CLOUD_ID     => '',
		self::INTERNAL_CLOUD_SCHEME => 'https',
		self::LOOPBACK_CLOUD_ID     => '',
		self::LOOPBACK_CLOUD_SCHEME => 'https',
		self::CHECK_FRONTAL_USING   => 'https://test.artificial-owl.com/',
		self::CHECK_INTERNAL_USING  => '',
		self::LOOPBACK_TMP_ID       => '',
		self::LOOPBACK_TMP_SCHEME   => '',

		self::SELF_SIGNED_CERT       => '0',
		self::MEMBERS_LIMIT          => '50',
		self::ACTIVITY_ON_NEW_CIRCLE => '1',
		self::MIGRATION_22           => '0',

		self::FORCE_NC_BASE                    => '',
		self::TEST_NC_BASE                     => '',
		self::CIRCLES_CONTACT_BACKEND          => '0',
		self::CIRCLES_ACCOUNTS_ONLY            => '0',
		self::CIRCLES_SEARCH_FROM_COLLABORATOR => '0',
	];


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

		if (($value = $this->config->getSystemValue('circles.' . $key, '')) !== '') {
			return $value;
		}

		return $this->get($key, $this->defaults);
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
	 * @return bool
	 * @deprecated
	 * should the password for a mail share be send to the recipient
	 *
	 */
	public function sendPasswordByMail() {
		if ($this->isContactsBackend()) {
			return false;
		}

		return ($this->config->getAppValue('sharebymail', 'sendpasswordmail', 'yes') === 'yes');
	}

	/**
	 * @param DeprecatedCircle $circle
	 *
	 * @return bool
	 * @deprecated
	 * do we require a share by mail to be password protected
	 *
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
			'allowedCircles'   => Circle::$DEF_CFG_MAX,
			'allowedUserTypes' => Member::$DEF_TYPE_MAX,
			'membersLimit'     => $this->getAppValueInt(self::MEMBERS_LIMIT)
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
			'enabled'     => $this->isGSAvailable(),
			'lookup'      => $this->getGSLookup(),
			'mockup'      => $this->getGSSMockup(),
			self::GS_MODE => $this->config->getSystemValue('gss.mode', ''),
			self::GS_KEY  => $this->config->getSystemValue('gss.jwt.key', ''),
		];
	}


	/**
	 * @return array
	 */
	public function getTrustedDomains(): array {
		return array_values($this->config->getSystemValue('trusted_domains', []));
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

		$base = $scheme . '://' . $instance;
		if ($route === '') {
			return $base;
		}

		return $base . $this->urlGenerator->linkToRoute($route, $args);
	}


	/**
	 * - must be configured using INTERNAL_CLOUD_ID
	 * - returns host+port, does not specify any protocol
	 * - used mainly to assign instance and source to a request to local GlobalScale
	 * - important only in GlobalScale environment
	 *
	 * @return string
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
	 * returns address based on FRONTAL_CLOUD_ID, FRONTAL_CLOUD_SCHEME and a routeName
	 * perfect for urlId in ActivityPub env.
	 *
	 * @param bool $internal
	 * @param string $route
	 * @param array $args
	 *
	 * @return string
	 * @throws RemoteInstanceException
	 */
	public function getInstancePath(
		bool $internal = false,
		string $route = 'circles.Remote.appService',
		array $args = []
	): string {
		if ($internal && $this->getInternalInstance() !== '') {
			$base = $this->getAppValue(self::INTERNAL_CLOUD_SCHEME) . '://' . $this->getInternalInstance();
		} else if ($this->getFrontalInstance() !== '') {
			$base = $this->getAppValue(self::FRONTAL_CLOUD_SCHEME) . '://' . $this->getFrontalInstance();
		} else {
			throw new RemoteInstanceException('not enabled');
		}

		if ($route === '') {
			return $base;
		}

		return $base . $this->urlGenerator->linkToRoute($route, $args);
	}

	/**
	 * @param string $host
	 * @param string $route
	 * @param array $args
	 *
	 * @return string
	 * @throws RemoteInstanceException
	 */
	public function getInstancePathBasedOnHost(
		string $host,
		string $route = 'circles.Remote.appService',
		array $args = []
	): string {
		return $this->getInstancePath(
			$this->isLocalInstance($host, true),
			$route,
			$args
		);
	}


	/**
	 * @param string $instance
	 * @param bool $internal
	 *
	 * @return bool
	 */
	public function isLocalInstance(string $instance, bool $internal = false): bool {
		if (strtolower($instance) === strtolower($this->getInternalInstance())
			&& $this->getInternalInstance() !== ''
		) {
			return true;
		}

		if (!$internal) {
			return false;
		}

		if (strtolower($instance) === strtolower($this->getFrontalInstance())) {
			return true;
		}

//		if ($this->getAppValue(self::FRONTAL_CLOUD_ID) === 'use-trusted-domain') {
			return (in_array($instance, $this->getTrustedDomains()));
//		}

//		return false;
	}

	/**
	 * @param string $instance
	 *
	 * @return string
	 */
	public function displayInstance(string $instance): string {
		if ($this->isLocalInstance($instance)) {
			return '';
		}

		return $instance;
	}


	/**
	 * @return string
	 */
	public function getLocalInstance(): string {
		if ($this->getFrontalInstance() !== '') {
			return $this->getFrontalInstance();
		}

		if ($this->getInternalInstance() !== '') {
			return $this->getInternalInstance();
		}

		if ($this->getLoopbackInstance()) {
			return $this->getLoopbackInstance();
		}

		return '';
	}

	/**
	 * @return array
	 */
	public function getValidLocalInstances(): array {
		return array_filter(
			array_unique(
				[
					$this->getFrontalInstance(),
					$this->getInternalInstance()
				]
			)
		);
	}


	/**
	 * - Create route using getLoopbackAddress()
	 * - perfect for loopback request.
	 *
	 * @param NC22Request $request
	 * @param string $route
	 * @param array $args
	 */
	public function configureLoopbackRequest(
		NC22Request $request,
		string $route = '',
		array $args = []
	): void {
		$this->configureRequest($request);
		$request->basedOnUrl($this->getLoopbackPath($route, $args));
	}


	/**
	 * @param NC22Request $request
	 */
	public function configureRequest(NC22Request $request): void {
		$request->setVerifyPeer($this->getAppValue(ConfigService::SELF_SIGNED_CERT) !== '1');
		$request->setProtocols(['https', 'http']);
		$request->setHttpErrorsAllowed(true);
		$request->setLocalAddressAllowed(true);
		$request->setFollowLocation(true);
		$request->setTimeout(5);
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

