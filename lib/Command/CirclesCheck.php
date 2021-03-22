<?php declare(strict_types=1);


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


namespace OCA\Circles\Command;

use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Model\Nextcloud\nc22\NC22Request;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Request;
use daita\MySmallPhpTools\Traits\TArrayTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\AppInfo\Capabilities;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\GlobalScaleService;
use OCA\Circles\Service\GSUpstreamService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;


/**
 * Class CirclesCheck
 *
 * @package OCA\Circles\Command
 */
class CirclesCheck extends Base {


	use TArrayTools;
	use TNC22Request;


	/** @var Capabilities */
	private $capabilities;

	/** @var GlobalScaleService */
	private $globalScaleService;

	/** @var GSUpstreamService */
	private $gsUpstreamService;

	/** @var ConfigService */
	private $configService;


	/** @var int */
	private $delay = 5;

	/** @var array */
	private $sessions = [];

	/**
	 * CirclesCheck constructor.
	 *
	 * @param Capabilities $capabilities
	 * @param GlobalScaleService $globalScaleService
	 * @param GSUpstreamService $gsUpstreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		Capabilities $capabilities, GlobalScaleService $globalScaleService,
		GSUpstreamService $gsUpstreamService, ConfigService $configService
	) {
		parent::__construct();

		$this->capabilities = $capabilities;
		$this->gsUpstreamService = $gsUpstreamService;
		$this->globalScaleService = $globalScaleService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:check')
			 ->setDescription('Checking your configuration')
			 ->addOption('delay', 'd', InputOption::VALUE_REQUIRED, 'delay before checking result')
			 ->addOption('capabilities', '', InputOption::VALUE_NONE, 'listing app\'s capabilities')
			 ->addOption('url', '', InputOption::VALUE_REQUIRED, 'specify a source url', '');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('capabilities')) {
			$capabilities = $this->getArray('circles', $this->capabilities->getCapabilities());
			$output->writeln(json_encode($capabilities, JSON_PRETTY_PRINT));

			return 0;
		}

		if ($input->getOption('delay')) {
			$this->delay = (int)$input->getOption('delay');
		}

		$this->configService->setAppValue(ConfigService::TEST_NC_BASE, '');
		$this->configService->setAppValue(ConfigService::TEST_NC_BASE, $input->getOption('url'));

		if (!$this->testRequest($output, 'GET', 'core.CSRFToken.index')) {
			$this->configService->setAppValue(ConfigService::TEST_NC_BASE, '');

			return 0;
		}

		if (!$this->testRequest(
			$output, 'POST', 'circles.RemoteWrapper.asyncBroadcast',
			['token' => 'test-dummy-token']
		)) {
			$this->configService->setAppValue(ConfigService::TEST_NC_BASE, '');

			return 0;
		}

		$test = new GSEvent(GSEvent::TEST, true, true);
		$test->setAsync(true);
		$token = $this->gsUpstreamService->newEvent($test);

		$output->writeln('- Async request is sent, now waiting ' . $this->delay . ' seconds');
		sleep($this->delay);
		$output->writeln('- Pause is over, checking results for ' . $token);

		$wrappers = $this->gsUpstreamService->getEventsByToken($token);

		$result = [];
		$instances = array_merge($this->globalScaleService->getInstances(true));
		foreach ($wrappers as $wrapper) {
			$result[$wrapper->getInstance()] = $wrapper->getEvent();
		}

		$localLooksGood = false;
		foreach ($instances as $instance) {
			$output->write($instance . ' ');
			if (array_key_exists($instance, $result)
				&& $result[$instance]->getResult()
									 ->gInt('status') === 1) {
				$output->writeln('<info>ok</info>');
				if ($this->configService->isLocalInstance($instance)) {
					$localLooksGood = true;
				}
			} else {
				$output->writeln('<error>fail</error>');
			}
		}

		$this->configService->setAppValue(ConfigService::TEST_NC_BASE, '');

		if ($localLooksGood) {
			$this->saveUrl($input, $output, $input->getOption('url'));
		}

		return 0;
	}


	/**
	 * @param OutputInterface $o
	 * @param string $type
	 * @param string $route
	 * @param array $args
	 *
	 * @return bool
	 * @throws RequestNetworkException
	 */
	private function testRequest(OutputInterface $o, string $type, string $route, array $args = []): bool {
		$request = new NC22Request('', Request::type($type));
		$this->configService->configureRequest($request, $route, $args);
		$request->setFollowLocation(false);

		$o->write('- ' . $type . ' request on ' . $request->getCompleteUrl() . ': ');
		$this->doRequest($request);

		$color = 'error';
		$result = $request->getResult();
		if ($result->getStatusCode() === 200) {
			$color = 'info';
		}

		$o->writeln('<' . $color . '>' . $result->getStatusCode() . '</' . $color . '>');

		if ($result->getStatusCode() === 200) {
			return true;
		}

		return false;
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $address
	 */
	private function saveUrl(InputInterface $input, OutputInterface $output, string $address): void {
		if ($address === '') {
			return;
		}

		$output->writeln('');
		$output->writeln(
			'The address <info>' . $address . '</info> seems to reach your local Nextcloud.'
		);

		$helper = $this->getHelper('question');
		$output->writeln('');
		$question = new ConfirmationQuestion(
			'<info>Do you want to store this address in database ?</info> (y/N) ', false, '/^(y|Y)/i'
		);

		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('Configuration NOT saved');

			return;
		}

		$this->configService->setAppValue(ConfigService::FORCE_NC_BASE, $address);
		$output->writeln(
			'New configuration <info>' . Application::APP_ID . '.' . ConfigService::FORCE_NC_BASE . '=\''
			. $address . '\'</info> stored in database'
		);
	}

}

