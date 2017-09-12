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

namespace OCA\Circles\Controller;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\Http\Client\IClientService;
use OCP\IRequest;


class TestController extends Controller {


	const TEST_DURATION = 120;

	/** @var ConfigService */
	private $configService;

	/** @var IClientService */
	private $clientService;

	/** @var MiscService */
	private $miscService;

	public function __construct(
		$appName, IRequest $request, ConfigService $configService, IClientService $clientService,
		MiscService $miscService
	) {
		parent::__construct($appName, $request);
		$this->configService = $configService;
		$this->clientService = $clientService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function testAsyncRun($data) {
		$lock = $data['lock'];
		if ($this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_LOCK) !== $lock) {
			return;
		}

		$this->configService->setAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT, 0);
		$this->miscService->asyncAndLeaveClientOutOfThis($this->testAsyncStatus());

		$t = 0;
		while ($t <= self::TEST_DURATION) {
			$t++;

			$this->configService->setAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT, $t);
			sleep(1);
		}

	}


	/**
	 * @param string $remote
	 *
	 * @return string
	 */
	private function generateTestAsyncURL($remote) {
		return $this->configService->generateRemoteHost($remote) . Application::TEST_URL_ASYNC;
	}


	public function testAsyncStart() {
		try {

			$lock = $this->testAsyncInitiate();
			$this->configService->setAppValue(ConfigService::CIRCLES_TEST_ASYNC_HAND, 1);
			$this->configService->setAppValue(ConfigService::CIRCLES_TEST_ASYNC_INIT, time());

			$client = $this->clientService->newClient();
			$url = $this->generateTestAsyncURL($this->configService->getLocalAddress());

			$client->put($url, MiscService::generateClientBodyData(['lock' => $lock]));

			$this->configService->setAppValue(ConfigService::CIRCLES_TEST_ASYNC_HAND, 0);
		} catch (Exception $e) {
			return $this->miscService->fail(['error' => $e->getMessage()]);
		}

		return $this->testAsyncStatus();
	}


	public function testAsyncReset() {
		try {
			$this->testAsyncInitiate();
		} catch (Exception $e) {
			return $this->miscService->fail(['error' => $e->getMessage()]);
		}

		return $this->testAsyncStatus();
	}


	/**
	 * @return DataResponse
	 */
	public function testAsyncStatus() {
		return $this->miscService->success(
			[
				'test'  => $this->getTestStatus(),
				'init'  => $this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_INIT),
				'hand'  => $this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_HAND),
				'lock'  => $this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_LOCK),
				'count' => $this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT)
			]
		);
	}


	/**
	 * @return array
	 */
	private function getTestStatus() {
		return [
			'running'  => (($this->isTestRunning()) ? 1 : 0),
			'counting' => (($this->isTestStillCounting()) ? 1 : 0),
			'note'     => $this->getTestNote()
		];
	}


	/**
	 * @return bool
	 */
	private function isTestRunning() {
		return ($this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_INIT) > (time()
																							 - self::TEST_DURATION));
	}


	/**
	 * @return bool
	 */
	private function isTestStillCounting() {
		$shouldBe = (time() - $this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_INIT));
		$current = $this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT);

		return ($current >= ($shouldBe - 3));
	}


	private function getTestNote() {
		if ($this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_HAND) === 1) {
			return 0;
		}
		if ($this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT) > 100) {
			return 5;
		}
		if ($this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT) > 50) {
			return 4;
		}
		if ($this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT) > 25) {
			$note = 3;
		}
		if ($this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT) > 15) {
			$note = 2;
		}
		if ($this->configService->getAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT) > 5) {
			return 1;
		}

	}


	/**
	 * @return string
	 * @throws Exception
	 */
	private function testAsyncInitiate() {
		if ($this->isTestRunning()) {
			throw new Exception('Wait for previous test to finish');
		}

		$lock = bin2hex(openssl_random_pseudo_bytes(24));

		$this->configService->setAppValue(ConfigService::CIRCLES_TEST_ASYNC_LOCK, $lock);
		$this->configService->setAppValue(ConfigService::CIRCLES_TEST_ASYNC_INIT, '0');
		$this->configService->setAppValue(ConfigService::CIRCLES_TEST_ASYNC_COUNT, 0);

		return $lock;
	}


}