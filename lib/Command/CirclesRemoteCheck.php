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


namespace OCA\Circles\Command;

use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21WellKnown;
use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Model\Remote\RemoteInstance;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\GlobalScaleService;
use OCA\Circles\Service\RemoteService;
use OCP\IL10N;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesRemote
 *
 * @package OCA\Circles\Command
 */
class CirclesRemoteCheck extends Base {


	use TNC21WellKnown;
	use TStringTools;


	/** @var IL10N */
	private $l10n;


	/** @var RemoteRequest */
	private $remoteRequest;

	/** @var GlobalScaleService */
	private $globalScaleService;

	/** @var RemoteService */
	private $remoteService;

	/** @var ConfigService */
	private $configService;


	/** @var OutputInterface */
	private $output;

	/**
	 * CirclesList constructor.
	 *
	 * @param RemoteRequest $remoteRequest
	 * @param GlobalScaleService $globalScaleService
	 * @param RemoteService $remoteService
	 * @param ConfigService $configService
	 */
	public function __construct(
		RemoteRequest $remoteRequest, GlobalScaleService $globalScaleService, RemoteService $remoteService,
		ConfigService $configService
	) {
		parent::__construct();

		$this->remoteRequest = $remoteRequest;
		$this->globalScaleService = $globalScaleService;
		$this->remoteService = $remoteService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:remote:check')
			 ->setDescription('verify Remote and Global Scale');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->output = $output;
		$this->verifyGSInstances();
		$this->checkRemoteInstances();

		return 0;
	}


	/**
	 *
	 */
	private function verifyGSInstances(): void {
		$instances = $this->globalScaleService->getGlobalScaleInstances();
		$known = array_map(
			function(RemoteInstance $instance): string {
				return $instance->getInstance();
			}, $this->remoteRequest->getFromType(RemoteInstance::TYPE_GLOBAL_SCALE)
		);

		$missing = array_diff($instances, $known);
		foreach ($missing as $instance) {
			$this->syncGSInstance($instance);
		}
	}


	/**
	 * @param string $instance
	 */
	private function syncGSInstance(string $instance): void {
		if ($this->configService->isLocalInstance($instance)) {
			return;
		}
		$this->output->write('Adding <comment>' . $instance . '</comment>: ');
		try {
			$this->remoteService->addRemoteInstance($instance, RemoteInstance::TYPE_GLOBAL_SCALE, true);
			$this->output->writeln('<info>ok</info>');
		} catch (Exception $e) {
			$msg = ($e->getMessage() === '') ? '' : ' (' . $e->getMessage() . ')';
			$this->output->writeln('<error>' . get_class($e) . $msg . '</error>');
		}
	}


	private function checkRemoteInstances(): void {
		$instances = $this->remoteRequest->getAllInstances();

		$output = new ConsoleOutput();
		$output = $output->section();
		$table = new Table($output);
		$table->setHeaders(['instance', 'type', 'UID', 'Authed']);
		$table->render();

		$local = $this->configService->getLocalInstance();
		foreach ($instances as $instance) {
			//$this->remoteService->
			try {
				$current = $this->remoteService->retrieveRemoteInstance($instance->getInstance());
				if ($current->getUid(true) === $instance->getUid(true)) {
					$currentUid = '<info>' . $current->getUid(true) . '</info>';
				} else {
					$currentUid = '<error>' . $current->getUid(true) . '</error>';
				}
			} catch (Exception $e) {
				$currentUid = '<error>' . $e->getMessage() . '</error>';
			}

			$table->appendRow(
				[
					$instance->getInstance(),
					$instance->getType(),
					$instance->getUid(),
					$currentUid
				]
			);
		}
	}

}

