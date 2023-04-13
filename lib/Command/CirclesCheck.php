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

use Exception;
use OC;
use OC\AppConfig;
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
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\LoopbackTest;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\RemoteService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\Service\RemoteUpstreamService;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TNCRequest;
use OCA\Circles\Tools\Traits\TStringTools;
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
	use TNCRequest;


	public static $checks = [
		'internal',
		'frontal',
		'loopback'
	];

	/** @var Capabilities */
	private $capabilities;

	/** @var InterfaceService */
	private $interfaceService;

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


	/** @var array */
	private $sessions = [];


	/**
	 * CirclesCheck constructor.
	 *
	 * @param Capabilities $capabilities
	 * @param InterfaceService $interfaceService
	 * @param FederatedEventService $federatedEventService
	 * @param RemoteService $remoteService
	 * @param RemoteStreamService $remoteStreamService
	 * @param RemoteUpstreamService $remoteUpstreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		Capabilities $capabilities,
		InterfaceService $interfaceService,
		FederatedEventService $federatedEventService,
		RemoteService $remoteService,
		RemoteStreamService $remoteStreamService,
		RemoteUpstreamService $remoteUpstreamService,
		ConfigService $configService
	) {
		parent::__construct();

		$this->capabilities = $capabilities;
		$this->interfaceService = $interfaceService;
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
			 ->addOption('capabilities', '', InputOption::VALUE_NONE, 'listing app\'s capabilities')
			 ->addOption('type', '', InputOption::VALUE_REQUIRED, 'configuration to check', '')
			 ->addOption('alpha', '', InputOption::VALUE_NONE, 'allow ALPHA features')
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

		if (!$input->getOption('alpha')) {
			return 0;
		}

		if ($type === '' || $type === 'frontal') {
			$output->writeln('### Testing <info>frontal</info> address.');
			$this->checkFrontal($input, $output, $test);
			$output->writeln('');
		}

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
				[$scheme, $cloudId, $path] = $this->parseAddress($loopback);
			} catch (Exception $e) {
				$output->writeln('<error>format must be http[s]://domain.name[:post][/path]</error>');
				continue;
			}

			$loopback = rtrim($scheme . '://' . $cloudId . $path, '/');
			$output->writeln('* testing address: ' . $loopback . ' ');

			try {
				$this->setupLoopback($input, $output, $loopback);
				$this->saveLoopback($input, $output, $loopback);

				return;
			} catch (Exception $e) {
				$output->writeln('');
			}
		}
	}


	/**
	 * @throws Exception
	 */
	private function setupLoopback(InputInterface $input, OutputInterface $output, string $address): void {
		$e = null;
		try {
			[$scheme, $cloudId, $path] = $this->parseAddress($address);

			$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_SCHEME, $scheme);
			$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_ID, $cloudId);
			$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_PATH, $path);
			if (!$this->testLoopback($input, $output)) {
				throw new Exception();
			}
		} catch (Exception $e) {
		}

		$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_SCHEME, '');
		$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_ID, '');
		$this->configService->setAppValue(ConfigService::LOOPBACK_TMP_PATH, '');

		if (!is_null($e)) {
			throw $e;
		}
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return bool
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	private function testLoopback(InputInterface $input, OutputInterface $output): bool {
		if (!$this->testRequest($output, 'GET', 'core.CSRFToken.index')) {
			return false;
		}

		if (!$this->testRequest(
			$output, 'POST', 'circles.EventWrapper.asyncBroadcast',
			['token' => 'test-dummy-token']
		)) {
			return false;
		}

		$timer = round(microtime(true) * 1000);
		$output->write('- Creating async FederatedEvent ');
		$test = new FederatedEvent(LoopbackTest::class);
		$this->federatedEventService->newEvent($test);
		$output->writeln(
			'<info>' . $test->getWrapperToken() . '</info> ' .
			'(took ' . (round(microtime(true) * 1000) - $timer) . 'ms)'
		);

		$output->writeln('- Waiting for async process to finish (5s)');
		sleep(5);

		$output->write('- Checking status on FederatedEvent ');
		$wrappers = $this->remoteUpstreamService->getEventsByToken($test->getWrapperToken());

		if (count($wrappers) !== 1) {
			$output->writeln('<error>Event created too many Wrappers</error>');

			return false;
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
		[$scheme, $cloudId, $path] = $this->parseAddress($loopback);

		$question = new ConfirmationQuestion(
			'- Do you want to save <info>'
			. $loopback
			. '</info> as your <info>loopback</info> address ? (y/N) ',
			false, '/^(y|Y)/i'
		);

		$helper = $this->getHelper('question');
		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('skipping.');

			return;
		}

		$this->configService->setAppValue(ConfigService::LOOPBACK_CLOUD_SCHEME, $scheme);
		$this->configService->setAppValue(ConfigService::LOOPBACK_CLOUD_ID, $cloudId);
		$this->configService->setAppValue(ConfigService::LOOPBACK_CLOUD_PATH, $path);
		$output->writeln(
			'- Address <info>' . $loopback . '</info> is now used as <info>loopback</info>'
		);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $test
	 *
	 * @throws SignatoryException
	 * @throws UnknownInterfaceException
	 * @throws Exception
	 */
	private function checkInternal(InputInterface $input, OutputInterface $output, string $test): void {
		$output->writeln(
			'. The <info>internal</info> setting should only be enabled if you are willing to use Circles in a GlobalScale setup on a local network.'
		);
		$output->writeln(
			'. The address you need to define here is the local address of your Nextcloud, reachable by all other instances of our GlobalScale.'
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
			$output->writeln('');
			$question = new Question(
				'<info>Please write down a new internal address to test</info>: ', ''
			);

			$internal = $helper->ask($input, $output, $question);
			if (is_null($internal) || $internal === '') {
				$output->writeln('skipping.');

				return;
			}

			try {
				[$scheme, $cloudId, $path] = $this->parseAddress($internal);
			} catch (Exception $e) {
				$output->writeln('<error>format must be http[s]://domain.name[:post][/path]</error>');
				continue;
			}

			$internal = rtrim($scheme . '://' . $cloudId, '/');
			$fullInternal = rtrim($scheme . '://' . $cloudId . $path, '/');

			$question = new ConfirmationQuestion(
				'<comment>Do you want to check the validity of this internal address?</comment> (Y/n) ', true,
				'/^(y|Y)/i'
			);

			if ($helper->ask($input, $output, $question)) {
				$testToken = $this->token();
				$this->configService->setAppValue(ConfigService::IFACE_TEST_ID, $cloudId);
				$this->configService->setAppValue(ConfigService::IFACE_TEST_SCHEME, $scheme);
				$this->configService->setAppValue(ConfigService::IFACE_TEST_PATH, $path);
				$this->configService->setAppValue(ConfigService::IFACE_TEST_TOKEN, $testToken);

				$output->writeln('');
				$output->writeln(
					'You will need to run this <info>curl</info> command from a terminal on your local network and paste its result: '
				);
				$output->writeln(
					'     curl -L "' . $internal
					. '/.well-known/webfinger?resource=http://nextcloud.com/&test='
					. $testToken . '"'
				);

				$output->writeln('paste the result here: ');
				$question = new Question('', '');
				$pastedWebfinger = new SimpleDataStore();
				$pastedWebfinger->json(trim($helper->ask($input, $output, $question)));

				if ($pastedWebfinger->g('subject') !== Application::APP_SUBJECT) {
					$output->writeln('<error>Cannot extract SUBJECT from the pasted data</error>');
					continue;
				}

				$pastedHref = '';
				foreach ($pastedWebfinger->gArray('links') as $link) {
					$entry = new SimpleDataStore($link);
					if ($entry->g('rel') === Application::APP_REL) {
						$pastedHref = $entry->g('href');
					}
				}

				if ($pastedHref === '') {
					$output->writeln('<error>Cannot retrieve HREF from the pasted data</error>');
					continue;
				}

				$href = $this->interfaceService->getCloudPath(
					'circles.Remote.appService',
					[],
					InterfaceService::IFACE_TEST
				);

				if ($pastedHref !== $href) {
					$output->writeln(
						'<error>The returned data (' . $pastedHref . ') are not the one expected: </error>'
						. $href
					);
					continue;
				}

				$output->writeln('');
				$output->writeln('<info>First step seems fine.</info>');
				$output->writeln(
					'Next step, please run this <info>curl</info> command from a terminal on your local network and paste its result: '
				);
				$output->writeln(
					'     curl -L "' . $pastedHref . '?test=' . $testToken . '" -H "Accept: application/json"'
				);

				$output->writeln('paste the result here: ');
				$question = new Question('', '');
				$pastedSignatory = new SimpleDataStore();
				$pastedSignatory->json(trim($helper->ask($input, $output, $question)));

				// small hack to refresh the cached config
				OC::$server->get(AppConfig::class)->clearCachedConfig();
				$this->interfaceService->setCurrentInterface(InterfaceService::IFACE_TEST);
				$appSignatory = $this->remoteStreamService->getAppSignatory(false);

				if ($appSignatory->getUid(true) !== $pastedSignatory->g('uid')
					|| $appSignatory->getRoot() !== $pastedSignatory->g('root')) {
					$output->writeln(
						'<error>The returned data ('
						. $pastedSignatory->g('uid') . '/' . $pastedSignatory->g('root')
						. ') are not the one expected: </error>'
						. $appSignatory->getUid(true) . '/' . $appSignatory->getRoot()
					);
					continue;
				}

				$output->writeln('* <info>Internal</info> address looks good');
			}

			$this->saveInternal($input, $output, $fullInternal);

			return;
		}
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $internal
	 *
	 * @throws Exception
	 */
	private function saveInternal(InputInterface $input, OutputInterface $output, string $internal): void {
		[$scheme, $cloudId, $path] = $this->parseAddress($internal);

		$output->writeln('');
		$question = new ConfirmationQuestion(
			'- Do you want to save <info>' . $internal
			. '</info> as your <info>internal</info> address ? (y/N) ',
			false, '/^(y|Y)/i'
		);

		$helper = $this->getHelper('question');
		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('skipping.');

			return;
		}

		$this->configService->setAppValue(ConfigService::INTERNAL_CLOUD_SCHEME, $scheme);
		$this->configService->setAppValue(ConfigService::INTERNAL_CLOUD_ID, $cloudId);
		$this->configService->setAppValue(ConfigService::INTERNAL_CLOUD_PATH, $path);

		$output->writeln('- Address <info>' . $internal . '</info> is now used as <info>internal</info>');
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
			'. By default, this feature is disabled.'
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
				[$scheme, $cloudId, $path] = $this->parseAddress($frontal);
			} catch (Exception $e) {
				$output->writeln('<error>format must be http[s]://domain.name[:post][/path]</error>');
				continue;
			}

			$frontal = rtrim($scheme . '://' . $cloudId, '/');
			$fullFrontal = rtrim($scheme . '://' . $cloudId . $path, '/');

			$question = new ConfirmationQuestion(
				'<comment>Do you want to check the validity of this frontal address?</comment> (y/N) ', false,
				'/^(y|Y)/i'
			);

			if ($helper->ask($input, $output, $question)) {
				$testToken = $this->token();
				$this->configService->setAppValue(ConfigService::IFACE_TEST_ID, $cloudId);
				$this->configService->setAppValue(ConfigService::IFACE_TEST_SCHEME, $scheme);
				$this->configService->setAppValue(ConfigService::IFACE_TEST_PATH, $path);
				$this->configService->setAppValue(ConfigService::IFACE_TEST_TOKEN, $testToken);

				$output->writeln('');
				$output->writeln(
					'You will need to run this <info>curl</info> command from a remote terminal and paste its result: '
				);
				$output->writeln(
					'     curl -L "' . $frontal
					. '/.well-known/webfinger?resource=http://nextcloud.com/&test='
					. $testToken . '"'
				);


				$output->writeln('paste the result here: ');
				$question = new Question('', '');
				$pastedWebfinger = new SimpleDataStore();
				$pastedWebfinger->json(trim($helper->ask($input, $output, $question)));

				if ($pastedWebfinger->g('subject') !== Application::APP_SUBJECT) {
					$output->writeln('<error>Cannot extract SUBJECT from the pasted data</error>');
					continue;
				}

				$pastedHref = '';
				foreach ($pastedWebfinger->gArray('links') as $link) {
					$entry = new SimpleDataStore($link);
					if ($entry->g('rel') === Application::APP_REL) {
						$pastedHref = $entry->g('href');
					}
				}

				if ($pastedHref === '') {
					$output->writeln('<error>Cannot retrieve HREF from the pasted data</error>');
					continue;
				}

				$href = $this->interfaceService->getCloudPath(
					'circles.Remote.appService',
					[],
					InterfaceService::IFACE_TEST
				);

				if ($pastedHref !== $href) {
					$output->writeln(
						'<error>The returned data (' . $pastedHref . ') are not the one expected: </error>'
						. $href
					);
					continue;
				}

				$output->writeln('');
				$output->writeln('<info>First step seems fine.</info>');
				$output->writeln(
					'Next step, please run this <info>curl</info> command from a remote terminal and paste its result: '
				);
				$output->writeln(
					'     curl -L "' . $pastedHref . '?test=' . $testToken . '" -H "Accept: application/json"'
				);

				$output->writeln('paste the result here: ');
				$question = new Question('', '');
				$pastedSignatory = new SimpleDataStore();
				$pastedSignatory->json(trim($helper->ask($input, $output, $question)));

				// small hack to refresh the cached config
				OC::$server->get(AppConfig::class)->clearCachedConfig();

				$this->interfaceService->setCurrentInterface(InterfaceService::IFACE_TEST);
				$appSignatory = $this->remoteStreamService->getAppSignatory(false);

				if ($appSignatory->getUid(true) !== $pastedSignatory->g('uid')
					|| $appSignatory->getRoot() !== $pastedSignatory->g('root')) {
					$output->writeln(
						'<error>The returned data ('
						. $pastedSignatory->g('uid') . '/' . $pastedSignatory->g('root')
						. ') are not the one expected: </error>'
						. $appSignatory->getUid(true) . '/' . $appSignatory->getRoot()
					);
					continue;
				}

				$output->writeln('* <info>Frontal</info> address looks good');
			}

			$this->saveFrontal($input, $output, $fullFrontal);

			return;
		}
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $frontal
	 *
	 * @throws Exception
	 */
	private function saveFrontal(InputInterface $input, OutputInterface $output, string $frontal): void {
		[$scheme, $cloudId, $path] = $this->parseAddress($frontal);

		$output->writeln('');
		$question = new ConfirmationQuestion(
			'- Do you want to save <info>' . $frontal
			. '</info> as your <info>frontal</info> address ? (y/N) ',
			false, '/^(y|Y)/i'
		);

		$helper = $this->getHelper('question');
		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('skipping.');

			return;
		}

		$this->configService->setAppValue(ConfigService::FRONTAL_CLOUD_SCHEME, $scheme);
		$this->configService->setAppValue(ConfigService::FRONTAL_CLOUD_ID, $cloudId);
		$this->configService->setAppValue(ConfigService::FRONTAL_CLOUD_PATH, $path);

		$output->writeln('- Address <info>' . $frontal . '</info> is now used as <info>frontal</info>');
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
		$request = new NCRequest('', Request::type($type));
		$this->configService->configureLoopbackRequest($request, $route, $args);
		$request->setFollowLocation(false);

		if ($request->getType() !== Request::TYPE_GET) {
			$request->setDataSerialize(new SimpleDataStore(['empty' => 1]));
		}

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
		$path = parse_url($test, PHP_URL_PATH);

		if (is_bool($scheme) || is_bool($cloudId) || is_null($scheme) || is_null($cloudId)) {
			throw new Exception();
		}

		if (is_null($path) || is_bool($path)) {
			$path = '';
		}

		$path = rtrim($path, '/');

		if (!is_null($cloudIdPort)) {
			$cloudId = $cloudId . ':' . $cloudIdPort;
		}

		return [$scheme, $cloudId, $path];
	}
}
