<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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


namespace OCA\Circles\Controller;

use Exception;
use OCA\Circles\Model\Debug;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\DebugService;
use OCA\Circles\Service\SignedControllerService;
use OCA\Circles\Tools\Model\NCSignedRequest;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLocalSignatory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class DebugController extends Controller {
	use TNCLocalSignatory;
	use TDeserialize;

	private SignedControllerService $signedControllerService;
	private ConfigService $configService;
	private DebugService $debugService;

	public function __construct(
		string $appName,
		IRequest $request,
		SignedControllerService $signedControllerService,
		ConfigService $configService,
		DebugService $debugService
	) {
		parent::__construct($appName, $request);

		$this->signedControllerService = $signedControllerService;
		$this->configService = $configService;
		$this->debugService = $debugService;
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function debugDaemon(): DataResponse {
		try {
			if ($this->configService->getAppValue(ConfigService::DEBUG) !== DebugService::DEBUG_DAEMON) {
				throw new Exception();
			}

			/** @var Debug $debug
			 * @var NCSignedRequest $signed
			 */
			$debug = $this->signedControllerService->extractObjectFromRequest(Debug::class, $signed);
			$debug->setInstance($signed->getOrigin());
			$this->debugService->save($debug);

			return new DataResponse([]);
		} catch (Exception $e) {
			return $this->signedControllerService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}
	}
}
