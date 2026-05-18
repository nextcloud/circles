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

	/** @var Signer */
	private $signer;


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
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
		private IUserSession $userSession,
		Signer $signer,
		private EventWrapperRequest $eventWrapperRequest,
		private ConfigService $configService,
		private MiscService $miscService,
	) {
		$this->signer = $signer;
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
		} catch (GSStatusException) {
		}

		return [];
	}
}
