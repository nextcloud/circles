<?php declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019
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


use daita\MySmallPhpTools\Exceptions\RequestContentException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\RequestResultNotJsonException;
use daita\MySmallPhpTools\Exceptions\RequestResultSizeException;
use daita\MySmallPhpTools\Exceptions\RequestServerException;
use daita\MySmallPhpTools\Model\Nextcloud\NC19Request;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Traits\Nextcloud\TNC19Request;
use daita\MySmallPhpTools\Traits\TStringTools;
use OC;
use OC\Security\IdentityProof\Signer;
use OC\User\NoUserException;
use OCA\Circles\Db\GSEventsRequest;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Exceptions\GSKeyException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\GlobalScale\AGlobalScaleEvent;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\GlobalScale\GSWrapper;
use OCP\AppFramework\QueryException;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;


/**
 * Class GlobalScaleService
 *
 * @package OCA\Circles\Service
 */
class GlobalScaleService {


	use TNC19Request;
	use TStringTools;


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	/** @var Signer */
	private $signer;

	/** @var GSEventsRequest */
	private $gsEventsRequest;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * GlobalScaleService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param Signer $signer
	 * @param GSEventsRequest $gsEventsRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		IUserSession $userSession,
		Signer $signer,
		GSEventsRequest $gsEventsRequest,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->signer = $signer;
		$this->gsEventsRequest = $gsEventsRequest;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param GSEvent $event
	 *
	 * @return string
	 */
	public function asyncBroadcast(GSEvent $event): string {
		$wrapper = new GSWrapper();
		$wrapper->setEvent($event);
		$wrapper->setToken($this->uuid());
		$wrapper->setCreation(time());
		$wrapper->setSeverity($event->getSeverity());

		foreach ($this->getInstances($event->isAsync()) as $instance) {
			$wrapper->setInstance($instance);
			$wrapper = $this->gsEventsRequest->create($wrapper);
		}

		$absolute = $this->urlGenerator->linkToRouteAbsolute(
			'circles.GlobalScale.asyncBroadcast', ['token' => $wrapper->getToken()]
		);

		$request = new NC19Request('', Request::TYPE_POST);
		$this->configService->configureRequest($request);
		$request->setAddressFromUrl($absolute);

		try {
			$this->doRequest($request);
		} catch (RequestContentException | RequestNetworkException | RequestResultSizeException | RequestServerException $e) {
			OC::$server->getLogger()
					   ->logException($e, ['app' => 'circles']);
		}

		return $wrapper->getToken();
	}


	/**
	 * @param GSEvent $event
	 *
	 * @return AGlobalScaleEvent
	 * @throws GlobalScaleEventException
	 */
	public function getGlobalScaleEvent(GSEvent $event): AGlobalScaleEvent {
		$class = $this->getClassNameFromEvent($event);
		try {
			$gs = OC::$server->query($class);
			if (!$gs instanceof AGlobalScaleEvent) {
				throw new GlobalScaleEventException($class . ' not an AGlobalScaleEvent');
			}

			return $gs;
		} catch (QueryException $e) {
			throw new GlobalScaleEventException('AGlobalScaleEvent ' . $class . ' not found');
		}
	}


	/**
	 * @return string
	 */
	public function getKey(): string {
		try {
			$key = $this->configService->getGSStatus(ConfigService::GS_KEY);
		} catch (GSStatusException $e) {
			$key = $this->configService->getAppValue(ConfigService::CIRCLES_LOCAL_GSKEY);
			if ($key === '') {
				$key = $this->token(31);
				$this->configService->setAppValue(ConfigService::CIRCLES_LOCAL_GSKEY, $key);
			}
		}

		return md5('gskey:' . $key);
	}

	/**
	 * @param string $key
	 *
	 * @throws GSKeyException
	 */
	public function checkKey(string $key) {
		if ($key !== $this->getKey()) {
			throw new GSKeyException('invalid key');
		}
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws GSKeyException
	 */
	public function checkEvent(GSEvent $event): void {
		$this->checkKey($event->getKey());
	}


	/**
	 * @param bool $all
	 *
	 * @return array
	 */
	public function getInstances(bool $all = false): array {
		/** @var string $lookup */
		try {
			$lookup = $this->configService->getGSStatus(ConfigService::GS_LOOKUP);
			$request = new NC19Request(ConfigService::GS_LOOKUP_INSTANCES, Request::TYPE_POST);
			$this->configService->configureRequest($request);

//			$user = $this->getRandomUser();
//			$data = $this->signer->sign('lookupserver', ['federationId' => $user->getCloudId()], $user);
			$data = ['authKey' => $this->configService->getGSStatus(ConfigService::GS_KEY)];
			$request->setData($data);
			$request->setAddressFromUrl($lookup);

			try {
				$instances = $this->retrieveJson($request);
			} catch (RequestContentException | RequestNetworkException | RequestResultSizeException | RequestServerException | RequestResultNotJsonException $e) {
				$this->miscService->log(
					'Issue while retrieving instances from lookup: ' . get_class($e) . ' ' . $e->getMessage()
				);

				return [];
			}
		} catch (GSStatusException $e) {
			return $this->getLocalInstance($all);
		}

		if ($all) {
			return $instances;
		}

		return array_values(array_diff($instances, $this->configService->getTrustedDomains()));
	}


	/**
	 * @param bool $all
	 *
	 * @return array
	 */
	private function getLocalInstance(bool $all): array {
		if (!$all) {
			return [];
		}

		$absolute = $this->urlGenerator->linkToRouteAbsolute('circles.Navigation.navigate');
		$local = parse_url($absolute);

		if (array_key_exists('port', $local)) {
			return [$local['host'] . ':' . $local['port']];
		} else {
			return [$local['host']];
		}
	}


	/**
	 * @param GSEvent $event
	 *
	 * @return string
	 * @throws GlobalScaleEventException
	 */
	private function getClassNameFromEvent(GSEvent $event): string {
		$className = $event->getType();
		if (substr($className, 0, 25) !== '\OCA\Circles\GlobalScale\\' || strpos($className, '.')) {
			throw new GlobalScaleEventException(
				$className . ' does not seems to be a secured AGlobalScaleEvent'
			);
		}

		return $className;
	}


	/**
	 * @return IUser
	 * @throws NoUserException
	 */
	private function getRandomUser(): IUser {
		$user = $this->userSession->getUser();
		if ($user !== null) {
			return $user;
		}

		$random = $this->userManager->search('', 1);
		if (sizeof($random) > 0) {
			return array_shift($random);
		}

		throw new NoUserException();
	}

}

