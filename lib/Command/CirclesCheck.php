<?php

declare(strict_types=1);


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

use ArtificialOwl\MySmallPhpTools\Exceptions\RequestNetworkException;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc22\NC22Request;
use ArtificialOwl\MySmallPhpTools\Model\Request;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Request;
use ArtificialOwl\MySmallPhpTools\Traits\TArrayTools;
use ArtificialOwl\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\AppInfo\Capabilities;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\LoopbackTest;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\RemoteService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\Service\RemoteUpstreamService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class CirclesCheck
 *
 * @package OCA\Circles\Command
 */
class CirclesCheck extends Base {
	use TStringTools;
	use TArrayTools;
	use TNC22Request;


	public static $checks = [
		'internal',
		'frontal',
		'loopback'
	];

	/** @var Capabilities */
	private $capabilities;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var RemoteService */
	private $remoteService;

	/** @var RemoteStreamService */
	private $remoteStreamService;

	/** @var RemoteUpstreamService */
	private $remoteUpstreamService;

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
	 * @param FederatedEvent $federatedEventService
	 * @param RemoteService $remoteService
	 * @param RemoteStreamService $remoteStreamService
	 * @param RemoteUpstreamService $remoteUpstreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		Capabilities $capabilities,
		FederatedEventService $federatedEventService,
		RemoteService $remoteService,
		RemoteStreamService $remoteStreamService,
		RemoteUpstreamService $remoteUpstreamService,
		ConfigService $configService
	) {
		parent::__construct();

		$this->capabilities = $capabilities;
		$this->federatedEventService = $federatedEventService;
		$this->remoteService = $remoteService;
		$this->remoteStreamService = $remoteStreamService;
		$this->remoteUpstreamService = $remoteUpstreamService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:check')
			 ->setDescription('Checking your configuration')
			 ->addOption('delay', 'd', InputOption::VALUE_REQUIRED, 'delay before checking result')
			 ->addOption('capabilities', '', InputOption::VALUE_NONE, 'listing app\'s capabilities')
			 ->addOption('type', '', InputOption::VALUE_REQUIRED, 'configuration to check', '')
			 ->addOption('test', '', InputOption::VALUE_REQUIRED, 'specify an url to test', '');
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
			$capabilities = $this->getArray('circles', $this->capabilities->getCapabilities(true));
			$output->writeln(json_encode($capabilities, JSON_PRETTY_PRINT));

			return 0;
		}

		if ($input->getOption('delay')) {
			$this->delay = (int)$input->getOption('delay');
		}

		$this->configService->setAppValue(ConfigService::TEST_NC_BASE, '');
		$test = $input->getOption('test');
		$type = $input->getOption('type');
		if ($test !== '' && $type === '') {
			throw new Exception('Please specify a --type for the test');
		}
		if ($test !== '' && !in_array($type, self::$checks)) {
			throw new Exception('Unknown type: ' . implode(', ', self::$checks));
		}

//		$this->configService->setAppValue(ConfigService::TEST_NC_BASE, $test);

		if ($type === '' || $type === 'loopback') {
			$output->writeln('### Checking <info>loopback</info> address.');
			$this->checkLoopback($input, $output, $test);
			$output->writeln('');
			$output->writeln('');
		}
		if ($type === '' || $type === 'internal') {
			$output->writeln('### Testing <info>internal</info> address.');
			$this->checkInternal($input, $output, $test);
			$output->writeln('');
			$output->writeln('');
		}
		if ($type === '' || $type === 'frontal') {
			$output->writeln('### Testing <info>frontal</info> address.');
			$this->checkFrontal($input, $output, $test);
			$output->writeln('');
		}


//
//		if (!$this->testRequest($output, 'GET', 'core.CSRFToken.index')) {
//			$this->configService->setAppValue(ConfigService::TEST_NC_BASE, '');
//
//			return 0;
//		}
//
//		if (!$this->testRequest(
//			$output, 'POST', 'circles.EventWrapper.asyncBroadcast',
//			['token' => 'test-dummy-token']
//		)) {
//			$this->configService->setAppValue(ConfigService::TEST_NC_BASE, '');
//
//			return 0;
//		}
//
//		$test = new GSEvent(GSEvent::TEST, true, true);
//		$test->setAsync(true);
//		$token = $this->gsUpstreamService->newEvent($test);
//
//		$output->writeln('- Async request is sent, now waiting ' . $this->delay . ' seconds');
//		sleep($this->delay);
//		$output->writeln('- Pause is over, checking results for ' . $token);
//
//		$wrappers = $this->gsUpstreamService->getEventsByToken($token);
//
//		$result = [];
//		$instances = array_merge($this->globalScaleService->getInstances(true));
//		foreach ($wrappers as $wrapper) {
//			$result[$wrapper->getInstance()] = $wrapper->getEvent();
//		}
//
//		$localLooksGood = false;
//		foreach ($instances as $instance) {
//			$output->write($instance . ' ');
//			if (array_key_exists($instance, $result)
//				&& $result[$instance]->getResult()
//									 ->gInt('status') === 1) {
//				$output->writeln('<info>ok</info>');
//				if ($this->configService->isLocalInstance($instance)) {
//					$localLooksGood = true;
//				}
//			} else {
//				$output->writeln('<error>fail</error>');
//			}
//		}
//
//		$this->configService->setAppValue(ConfigService::TEST_NC_BASE, '');
//
//		if ($localLooksGood) {
//			$this->saveUrl($input, $output, $input->getOption('url'));
//		}

		return 0;
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $test
	 *
	 * @throws Exception
	 */
	private function checkLoopback(InputInterface $input, OutputInterface $output, string $test = ''): void {
		$output->writeln('. The <info>loopback</info> setting is mandatory and can be checked locally.');
		$output->writeln(
			'. The address you need to define here must be a reachable url of your Nextcloud from the hosting server itself.'
		);
		$output->writeln(
			'. By default, the App will use the entry \'overwrite.cli.url\' from \'config/config.php\'.'
		);

		$notDefault = false;
		if ($test === '') {
			$test = $this->configService->getLoopbackPath();
		} else {
			$notDefault = true;
		}

		$output->writeln('');
		$output->writeln('* testing current address: ' . $test);

		try {
			$this->setupLoopback($input, $output, $test);
			$output->writeln('* <info>Loopback</info> address looks good');
			if ($notDefault) {
				$this->saveLoopback($input, $output, $test);
			}

			return;
		} catch (Exception $e) {
		}

		$output->writeln('');
		$output->writeln('- <comment>You do not have a valid loopback address setup right now.</comment>');
		$output->writeln('');

		$helper = $this->getHelper('question');
		while (true) {
			$question = new Question(
				'<info>Please write down a new loopback address to test</info>: ', ''
			);

			$loopback = $helper->ask($input, $output, $question);
			if (is_null($loopback) || $loopback === '') {
				$output->writeln('exiting.');
				throw new Exception('Your Circles App is not fully configured.');
			}

			try {
				[$scheme, $cloudId] = $this->parseAddress($loopback);
			} catch (Exception $e) {
				$output->writeln('<error>format must be http[s]://domain.name[:post]</error>');
				continue;
			}

			$loopback = $scheme . '://' . $cloudId;
			$output->write('* testing address: ' . $loopback . ' ');

			if ($this->testLoopback($input, $output, $loopback)) {
				$output->writeln('* <info>Loopback</info> address looks good');
				$this->saveLoopback($input, $output, $loopback);

				return;
			}
		}
	}


	/**
	 * @throws Exception
	 */
	private function setupLoopback(InputInterface $input, OutputInterface $output, string $address): void {
		$e = null;
		try {
			[$scheme, $cloudId] = $this->parseAddress($address);

			$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_SCHEME, $scheme);
			$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_ID, $cloudId);
			if (!$this->testLoopback($input, $output, $address)) {
				throw new Exception();
			}
		} catch (Exception $e) {
		}

		$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_SCHEME, '');
		$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_ID, '');

		if (!is_null($e)) {
			throw $e;
		}
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $address
	 *
	 * @return bool
	 * @throws RequestNetworkException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RequestBuilderException
	 */
	private function testLoopback(InputInterface $input, OutputInterface $output, string $address): bool {
		if (!$this->testRequest($output, 'GET', 'core.CSRFToken.index')) {
			return false;
		}

		if (!$this->testRequest(
			$output, 'POST', 'circles.EventWrapper.asyncBroadcast',
			['token' => 'test-dummy-token']
		)) {
			return false;
		}

		$output->write('- Creating async FederatedEvent ');
		$test = new FederatedEvent(LoopbackTest::class);
		$this->federatedEventService->newEvent($test);

		$output->writeln('<info>' . $test->getWrapperToken() . '</info>');

		$output->writeln('- Waiting for async process to finish (' . $this->delay . 's)');
		sleep($this->delay);

		$output->write('- Checking status on FederatedEvent ');
		$wrappers = $this->remoteUpstreamService->getEventsByToken($test->getWrapperToken());
		if (count($wrappers) !== 1) {
			$output->writeln('<error>Event created too many Wrappers</error>');
			$output->writeln('<error>Event created too many Wrappers</error>');
		}

		$wrapper = array_shift($wrappers);

		$checkVerify = $wrapper->getEvent()->getData()->gInt('verify');
		if ($checkVerify === LoopbackTest::VERIFY) {
			$output->write('<info>verify=' . $checkVerify . '</info> ');
		} else {
			$output->writeln('<error>verify=' . $checkVerify . '</error>');

			return false;
		}

		$checkManage = $wrapper->getResult()->gInt('manage');
		if ($checkManage === LoopbackTest::MANAGE) {
			$output->write('<info>manage=' . $checkManage . '</info> ');
		} else {
			$output->writeln('<error>manage=' . $checkManage . '</error>');

			return false;
		}

		$output->writeln('');

		return true;
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $loopback
	 *
	 * @throws Exception
	 */
	private function saveLoopback(InputInterface $input, OutputInterface $output, string $loopback): void {
		[$scheme, $cloudId] = $this->parseAddress($loopback);

		$question = new ConfirmationQuestion(
			'- Do you want to save <info>'. $loopback . '</info> as your <info>loopback</info> address ? (y/N) ', false, '/^(y|Y)/i'
		);

		$helper = $this->getHelper('question');
		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('skipping.');

			return;
		}

		$this->configService->setAppValue(ConfigService::LOOPBACK_CLOUD_SCHEME, $scheme);
		$this->configService->setAppValue(ConfigService::LOOPBACK_CLOUD_ID, $cloudId);
		$output->writeln(
			'- Address <info>' . $loopback . '</info> is now used as <info>loopback</info>'
		);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $test
	 */
	private function checkInternal(InputInterface $input, OutputInterface $output, string $test): void {
		$output->writeln(
			'. The <info>internal</info> setting is mandatory only if you are willing to use Circles in a GlobalScale setup on a local network.'
		);
		$output->writeln(
			'. The address you need to define here is the local address of your Nextcloud, reachable by all other instances of our GlobalScale.'
		);
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $test
	 */
	private function checkFrontal(InputInterface $input, OutputInterface $output, string $test): void {
		$output->writeln('. The <info>frontal</info> setting is optional.');
		$output->writeln(
			'. The purpose of this address is for your Federated Circle to reach other instances of Nextcloud over the Internet.'
		);
		$output->writeln(
			'. The address you need to define here must be reachable from the Internet.'
		);
		$output->writeln(
			'. By default, this feature is disabled (and is considered ALPHA in Nextcloud 22).'
		);

		$question = new ConfirmationQuestion(
			'- <comment>Do you want to enable this feature ?</comment> (y/N) ', false, '/^(y|Y)/i'
		);

		$helper = $this->getHelper('question');
		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('skipping.');

			return;
		}


		while (true) {
			$question = new Question(
				'<info>Please write down a new frontal address to test</info>: ', ''
			);

			$frontal = $helper->ask($input, $output, $question);
			if (is_null($frontal) || $frontal === '') {
				$output->writeln('skipping.');

				return;
			}

			try {
				[$scheme, $cloudId] = $this->parseAddress($frontal);
			} catch (Exception $e) {
				$output->writeln('<error>format must be http[s]://domain.name[:post]</error>');
				continue;
			}

			$frontal = $scheme . '://' . $cloudId;
			break;
		}

		$question = new ConfirmationQuestion(
			'<comment>Do you want to check the validity of this frontal address?</comment> (y/N) ', false,
			'/^(y|Y)/i'
		);

		if ($helper->ask($input, $output, $question)) {
			$output->writeln(
				'You will need to run this <info>curl</info> command from a remote terminal and paste its result: '
			);
			$output->writeln(
				'     curl ' . $frontal . '/.well-known/webfinger?resource=http://nextcloud.com/'
			);

			$question = new Question('result: ', '');
			$pasteWebfinger = $helper->ask($input, $output, $question);

			echo '__ ' . $pasteWebfinger;

			$output->writeln('TESTING !!');
			$output->writeln('TESTING !!');
			$output->writeln('TESTING !!');
		}

		$output->writeln('saved');


//		$output->writeln('.  1) The automatic way, requiring a valid remote instance of Nextcloud.');
//		$output->writeln(
//			'.  2) The manual way, using the <comment>curl</comment> command from a remote terminal.'
//		);
//		$output->writeln(
//			'. If you prefer the automatic way, you will need to enter the valid remote instance of Nextcloud you want to use.'
//		);
//		$output->writeln('. If you want the manual way, just enter an empty field.');
//		$output->writeln('');
//		$output->writeln(
//			'. If you do not known a valid remote instance of Nextcloud, you can use <comment>\'https://circles.artificial-owl.com/\'</comment>'
//		);
//		$output->writeln(
//			'. Please note that no critical information will be shared during the process, and any data (ie. public key and address)'
//		);
//		$output->writeln(
//			'  generated during the process will be wiped of the remote instance after few minutes.'
//		);
//		$output->writeln('');

//		$question = new Question(
//			'- <comment>Which remote instance of Nextcloud do you want to use in order to test your setup:</comment> (empty to bypass this step): '
//		);
//		$helper = $this->getHelper('question');
//		$remote = $helper->ask($input, $output, $question);
//		if (is_null($frontal) || $frontal === '') {
//			$output->writeln('skipping.');
//
//			return;
//		}
//
//		$output->writeln('. The confirmation step is optional and can be done in 2 different ways:');
//
//
//		$output->writeln('');
//		$question = new Question(
//			'- <comment>Enter the <info>frontal</info> address you want to be used to identify your instance of Nextcloud over the Internet</comment>: '
//		);
//		$helper = $this->getHelper('question');
//		$frontal = $helper->ask($input, $output, $question);

//		while (true) {
//			$question = new Question(
//				'<info>Please write down a new frontal address to test</info>: ', ''
//			);
//
//			$frontal = $helper->ask($input, $output, $question);
//			if (is_null($frontal) || $frontal === '') {
//				$output->writeln('skipping.');
//
//				return;
//			}
//
//			try {
//				[$scheme, $cloudId] = $this->parseAddress($test);
//			} catch (Exception $e) {
//				$output->writeln('<error>format must be http[s]://domain.name[:post]</error>');
//				continue;
//			}
//
//			$frontal = $scheme . '://' . $cloudId;
//
//			$output->write('* testing address: ' . $frontal . ' ');
//
//			if ($remote === '') {
//				$output->writeln('remote empty, please run this curl request and paste the result in here');
//				$output->writeln(
//					'  curl ' . $frontal . '/.well-known/webfinger?resource=http://nextcloud.com/'
//				);
//				$question = new Question('result: ', '');
//
//				$resultWebfinger = $helper->ask($input, $output, $question);
//
//			} else {
//			}
//		}


//		if ($remote === '') {
//			$output->writeln('');
//		}

//		$output->writeln('');
//		$output->writeln(
//			'. By default, this feature is disabled. You will need to setup a valid entry to enabled it.'
//		);

//		$output->writeln('');
//		$output->write('* testing current address: ' . $this->configService->getLoopbackPath() . ' ');


//		$this->configService->getAppValue(ConfigService::CHECK_FRONTAL_USING);
	}


	/**
	 * @param OutputInterface $o
	 * @param string $type
	 * @param string $route
	 * @param array $args
	 *
	 * @return bool
	 */
	private function testRequest(
		OutputInterface $output,
		string $type,
		string $route,
		array $args = []
	): bool {
		$request = new NC22Request('', Request::type($type));
		$this->configService->configureLoopbackRequest($request, $route, $args);
		$request->setFollowLocation(false);

		$output->write('- ' . $type . ' request on ' . $request->getCompleteUrl() . ': ');

		try {
			$this->doRequest($request);
			$result = $request->getResult();

			$color = 'error';
			if ($result->getStatusCode() === 200) {
				$color = 'info';
			}

			$output->writeln('<' . $color . '>' . $result->getStatusCode() . '</' . $color . '>');
			if ($result->getStatusCode() === 200) {
				return true;
			}
		} catch (RequestNetworkException $e) {
			$output->writeln('<error>fail</error>');
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


	/**
	 * @param string $test
	 *
	 * @return array
	 * @throws Exception
	 */
	private function parseAddress(string $test): array {
		$scheme = parse_url($test, PHP_URL_SCHEME);
		$cloudId = parse_url($test, PHP_URL_HOST);
		$cloudIdPort = parse_url($test, PHP_URL_PORT);

		if (is_null($scheme) || is_null($cloudId)) {
			throw new Exception();
		}

		if (!is_null($cloudIdPort)) {
			$cloudId = $cloudId . ':' . $cloudIdPort;
		}

		return [$scheme, $cloudId];
	}
}
