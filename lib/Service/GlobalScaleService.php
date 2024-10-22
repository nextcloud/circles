<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Service;

use OC\Security\IdentityProof\Signer;
use OCA\Circles\Db\EventWrapperRequest;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TNCRequest;
use OCA\Circles\Tools\Traits\TStringTools;
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
		MiscService $miscService,
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
}
