<?php

declare(strict_types=1);


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

use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TNCRequest;
use OCA\Circles\Tools\Traits\TStringTools;
use OC\Security\IdentityProof\Signer;
use OCA\Circles\Db\EventWrapperRequest;
use OCA\Circles\Exceptions\GSStatusException;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * Class GlobalScaleService
 *
 * @package OCA\Circles\Service
 */
class GlobalScaleService {
	use TNCRequest;
	use TStringTools;


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	/** @var Signer */
	private $signer;

	/** @var EventWrapperRequest */
	private $eventWrapperRequest;

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
	 * @param EventWrapperRequest $eventWrapperRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		IUserSession $userSession,
		Signer $signer,
		EventWrapperRequest $eventWrapperRequest,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->signer = $signer;
		$this->eventWrapperRequest = $eventWrapperRequest;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @return array
	 */
	public function getGlobalScaleInstances(): array {
		$mockup = $this->configService->getGSSMockup();
		if (!empty($mockup)) {
			return $mockup;
		}

		try {
			$lookup = $this->configService->getGSLookup();
			$request = new NCRequest(ConfigService::GS_LOOKUP_INSTANCES, Request::TYPE_POST);
			$this->configService->configureRequest($request);
			$request->basedOnUrl($lookup);
			$request->addData('authKey', $this->configService->getGSInfo(ConfigService::GS_KEY));

			try {
				return $this->retrieveJson($request);
			} catch (RequestNetworkException $e) {
				$this->e($e);
			}
		} catch (GSStatusException $e) {
		}

		return [];
	}



//	/**
//	 * @param GSEvent $event
//	 *
//	 * @return string
//	 */
//	public function asyncBroadcast(GSEvent $event): string {
//		$wrapper = new GSWrapper();
//		$wrapper->setEvent($event);
//		$wrapper->setToken($this->uuid());
//		$wrapper->setCreation(time());
//		$wrapper->setSeverity($event->getSeverity());
//
//		foreach ($this->getInstances($event->isAsync()) as $instance) {
//			$wrapper->setInstance($instance);
//			$wrapper = $this->eventWrapperRequest->create($wrapper);
//		}
//
//		$request = new NC22Request('', Request::TYPE_POST);
//		$this->configService->configureRequest(
//			$request, 'circles.EventWrapper.asyncBroadcast', ['token' => $wrapper->getToken()]
//		);
//
//		try {
//			$this->doRequest($request);
//		} catch (RequestContentException | RequestNetworkException | RequestResultSizeException | RequestServerException $e) {
//			$this->miscService->e($e);
//		}
//
//		return $wrapper->getToken();
//	}
//
//
//	/**
//	 * @param GSEvent $event
//	 *
//	 * @return AGlobalScaleEvent
//	 * @throws GlobalScaleEventException
//	 */
//	public function getGlobalScaleEvent(GSEvent $event): AGlobalScaleEvent {
//		$class = $this->getClassNameFromEvent($event);
//		try {
//			$gs = OC::$server->query($class);
//			if (!$gs instanceof AGlobalScaleEvent) {
//				throw new GlobalScaleEventException($class . ' not an AGlobalScaleEvent');
//			}
//
//			return $gs;
//		} catch (QueryException $e) {
//			throw new GlobalScaleEventException('AGlobalScaleEvent ' . $class . ' not found');
//		}
//	}
//
//
//	/**
//	 * @return string
//	 */
//	public function getKey(): string {
//		try {
//			$key = $this->configService->getGSStatus(ConfigService::GS_KEY);
//		} catch (GSStatusException $e) {
//			$key = $this->configService->getAppValue(ConfigService::CIRCLES_LOCAL_GSKEY);
//			if ($key === '') {
//				$key = $this->token(31);
//				$this->configService->setAppValue(ConfigService::CIRCLES_LOCAL_GSKEY, $key);
//			}
//		}
//
//		return md5('gskey:' . $key);
//	}
//
//	/**
//	 * @param string $key
//	 *
//	 * @throws GSKeyException
//	 */
//	public function checkKey(string $key) {
//		if ($key !== $this->getKey()) {
//			throw new GSKeyException('invalid key');
//		}
//	}
//
//
//	/**
//	 * @param GSEvent $event
//	 *
//	 * @throws GSKeyException
//	 */
//	public function checkEvent(GSEvent $event): void {
//		$this->checkKey($event->getKey());
//	}
//
//
//	/**
//	 * @param GSEvent $event
//	 *
//	 * @return string
//	 * @throws GlobalScaleEventException
//	 */
//	private function getClassNameFromEvent(GSEvent $event): string {
//		$className = $event->getType();
//		if (substr($className, 0, 25) !== '\OCA\Circles\GlobalScale\\' || strpos($className, '.')) {
//			throw new GlobalScaleEventException(
//				$className . ' does not seems to be a secured AGlobalScaleEvent'
//			);
//		}
//
//		return $className;
//	}
//
//
//	/**
//	 * @return IUser
//	 * @throws NoUserException
//	 */
//	private function getRandomUser(): IUser {
//		$user = $this->userSession->getUser();
//		if ($user !== null) {
//			return $user;
//		}
//
//		$random = $this->userManager->search('', 1);
//		if (sizeof($random) > 0) {
//			return array_shift($random);
//		}
//
//		throw new NoUserException();
//	}
}
