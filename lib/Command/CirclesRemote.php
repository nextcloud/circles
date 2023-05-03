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

use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Exceptions\SignatureException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\NCSignedRequest;
use OCA\Circles\Tools\Traits\TNCWellKnown;
use OCA\Circles\Tools\Traits\TStringTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteUidException;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\GlobalScaleService;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\RemoteStreamService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class CirclesRemote
 *
 * @package OCA\Circles\Command
 */
class CirclesRemote extends Base {
	use TNCWellKnown;
	use TStringTools;


	/** @var RemoteRequest */
	private $remoteRequest;

	/** @var GlobalScaleService */
	private $globalScaleService;

	/** @var RemoteStreamService */
	private $remoteStreamService;

	/** @var InterfaceService */
	private $interfaceService;

	/** @var ConfigService */
	private $configService;


	/** @var InputInterface */
	private $input;

	/** @var OutputInterface */
	private $output;


	/**
	 * CirclesRemote constructor.
	 *
	 * @param RemoteRequest $remoteRequest
	 * @param GlobalScaleService $globalScaleService
	 * @param RemoteStreamService $remoteStreamService
	 * @param InterfaceService $interfaceService
	 * @param ConfigService $configService
	 */
	public function __construct(
		RemoteRequest $remoteRequest,
		GlobalScaleService $globalScaleService,
		RemoteStreamService $remoteStreamService,
		InterfaceService $interfaceService,
		ConfigService $configService
	) {
		parent::__construct();

		$this->remoteRequest = $remoteRequest;
		$this->globalScaleService = $globalScaleService;
		$this->remoteStreamService = $remoteStreamService;
		$this->interfaceService = $interfaceService;
		$this->configService = $configService;

		$this->setup('app', 'circles');
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:remote')
			 ->setDescription('remote features')
			 ->addArgument('host', InputArgument::OPTIONAL, 'host of the remote instance of Nextcloud')
			 ->addOption(
			 	'type', '', InputOption::VALUE_REQUIRED, 'set type of remote', RemoteInstance::TYPE_UNKNOWN
			 )
			 ->addOption(
			 	'iface', '', InputOption::VALUE_REQUIRED, 'set interface to use to contact remote',
			 	InterfaceService::$LIST_IFACE[InterfaceService::IFACE_FRONTAL]
			 )
			 ->addOption('yes', '', InputOption::VALUE_NONE, 'silently add the remote instance')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'display all information');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$host = $input->getArgument('host');

		$this->input = $input;
		$this->output = $output;

		if ($host) {
			$this->requestInstance($host);
		} else {
			$this->checkKnownInstance();
		}

		return 0;
	}


	/**
	 * @param string $host
	 *
	 * @throws Exception
	 */
	private function requestInstance(string $host): void {
		$remoteType = $this->getRemoteType();
		$remoteIface = $this->getRemoteInterface();
		$this->interfaceService->setCurrentInterface($remoteIface);

		$webfinger = $this->getWebfinger($host, Application::APP_SUBJECT);
		if ($this->input->getOption('all')) {
			$this->output->writeln('- Webfinger on <info>' . $host . '</info>');
			$this->output->writeln(json_encode($webfinger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			$this->output->writeln('');
		}

		if ($this->input->getOption('all')) {
			$circleLink = $this->extractLink(Application::APP_REL, $webfinger);
			$this->output->writeln('- Information about Circles app on <info>' . $host . '</info>');
			$this->output->writeln(json_encode($circleLink, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			$this->output->writeln('');
		}

		$this->output->writeln('- Available services on <info>' . $host . '</info>');
		foreach ($webfinger->getLinks() as $link) {
			$app = $link->getProperty('name');
			$ver = $link->getProperty('version');
			if ($app !== '') {
				$app .= ' ';
			}
			if ($ver !== '') {
				$ver = 'v' . $ver;
			}

			$this->output->writeln(' * ' . $link->getRel() . ' ' . $app . $ver);
		}
		$this->output->writeln('');

		$this->output->writeln('- Resources related to Circles on <info>' . $host . '</info>');
		$resource = $this->getResourceData($host, Application::APP_SUBJECT, Application::APP_REL);
		$this->output->writeln(json_encode($resource, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		$this->output->writeln('');


		$tempUid = $resource->g('uid');
		$this->output->writeln(
			'- Confirming UID=' . $tempUid . ' from parsed Signatory at <info>' . $host . '</info>'
		);

		try {
			/** @var RemoteInstance $remoteSignatory */
			$remoteSignatory = $this->remoteStreamService->retrieveSignatory($resource->g('id'), true);
			$this->output->writeln(' * No SignatureException: <info>Identity authed</info>');
		} catch (SignatureException $e) {
			$this->output->writeln(
				'<error>' . $host . ' cannot auth its identity: ' . $e->getMessage() . '</error>'
			);

			return;
		}

		$this->output->writeln(' * Found <info>' . $remoteSignatory->getUid() . '</info>');
		if ($remoteSignatory->getUid(true) !== $tempUid) {
			$this->output->writeln('<error>looks like ' . $host . ' is faking its identity');

			return;
		}

		$this->output->writeln('');

		$testUrl = $resource->g('test');
		$this->output->writeln('- Testing signed payload on <info>' . $testUrl . '</info>');

		try {
			$localSignatory = $this->remoteStreamService->getAppSignatory();
		} catch (SignatoryException $e) {
			$this->output->writeln(
				'<error>Federated Circles not enabled locally. Please run ./occ circles:remote:init</error>'
			);

			return;
		}

		$payload = [
			'test' => 42,
			'token' => $this->uuid()
		];
		$signedRequest = $this->outgoingTest($testUrl, $payload);
		$this->output->writeln(' * Payload: ');
		$this->output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		$this->output->writeln('');

		$this->output->writeln(' * Clear Signature: ');
		$this->output->writeln('<comment>' . $signedRequest->getClearSignature() . '</comment>');
		$this->output->writeln('');

		$this->output->writeln(' * Signed Signature (base64 encoded): ');
		$this->output->writeln(
			'<comment>' . base64_encode($signedRequest->getSignedSignature()) . '</comment>'
		);
		$this->output->writeln('');

		$result = $signedRequest->getOutgoingRequest()->getResult();
		$code = $result->getStatusCode();
		$this->output->writeln(' * Result: ' . (($code === 200) ? '<info>' . $code . '</info>' : $code));
		$this->output->writeln(
			json_encode(json_decode($result->getContent(), true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
		);
		$this->output->writeln('');

		if ($this->input->getOption('all')) {
			$this->output->writeln('');
			$this->output->writeln('<info>### Complete report ###</info>');
			$this->output->writeln(json_encode($signedRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			$this->output->writeln('');
		}

		if ($remoteSignatory->getUid() !== $localSignatory->getUid()) {
			$remoteSignatory->setInstance($host)
							->setType($remoteType)
							->setInterface($remoteIface);

			try {
				$stored = new RemoteInstance();
				$this->remoteStreamService->confirmValidRemote($remoteSignatory, $stored);
				$this->output->writeln(
					'<info>The remote instance ' . $host
					. ' is already known with this current identity</info>'
				);


				$this->output->writeln('- updating item');
				$this->remoteStreamService->update($remoteSignatory, RemoteStreamService::UPDATE_ITEM);

				if ($remoteSignatory->getType() !== $stored->getType()) {
					$this->output->writeln(
						'- updating type from ' . $stored->getType() . ' to '
						. $remoteSignatory->getType()
					);
					$this->remoteStreamService->update(
						$remoteSignatory, RemoteStreamService::UPDATE_TYPE
					);
				}

				if ($remoteSignatory->getInstance() !== $stored->getInstance()) {
					$this->output->writeln(
						'- updating host from ' . $stored->getInstance() . ' to '
						. $remoteSignatory->getInstance()
					);
					$this->remoteStreamService->update(
						$remoteSignatory, RemoteStreamService::UPDATE_INSTANCE
					);
				}
				if ($remoteSignatory->getId() !== $stored->getId()) {
					$this->output->writeln(
						'- updating href/Id from ' . $stored->getId() . ' to '
						. $remoteSignatory->getId()
					);
					$this->remoteStreamService->update($remoteSignatory, RemoteStreamService::UPDATE_HREF);
				}
			} catch (RemoteUidException $e) {
				$this->updateRemote($remoteSignatory);
			} catch (RemoteNotFoundException $e) {
				$this->saveRemote($remoteSignatory);
			}
		}
	}


	/**
	 * @param RemoteInstance $remoteSignatory
	 *
	 * @throws RemoteUidException
	 */
	private function saveRemote(RemoteInstance $remoteSignatory) {
		$this->output->writeln('');
		$helper = $this->getHelper('question');

		$this->output->writeln(
			'The remote instance <info>' . $remoteSignatory->getInstance() . '</info> looks good.'
		);
		$question = new ConfirmationQuestion(
			'Would you like to identify this remote instance as \'<comment>' . $remoteSignatory->getType()
			. '</comment>\' using interface \'<comment>'
			. InterfaceService::$LIST_IFACE[$remoteSignatory->getInterface()]
			. '</comment>\' ? (y/N) ',
			false,
			'/^(y|Y)/i'
		);

		if ($this->input->getOption('yes') || $helper->ask($this->input, $this->output, $question)) {
			if (!$this->interfaceService->isInterfaceInternal($remoteSignatory->getInterface())) {
				$remoteSignatory->setAliases([]);
			}
			$this->remoteRequest->save($remoteSignatory);
			$this->output->writeln('<info>remote instance saved</info>');
		}
	}


	/**
	 * @param RemoteInstance $remoteSignatory
	 *
	 * @throws RemoteUidException
	 */
	private function updateRemote(RemoteInstance $remoteSignatory): void {
		$this->output->writeln('');
		$helper = $this->getHelper('question');

		$this->output->writeln(
			'The remote instance <info>' . $remoteSignatory->getInstance()
			. '</info> is known but <error>its identity has changed.</error>'
		);
		$this->output->writeln(
			'<comment>If you are not sure on why identity changed, please say No to the next question and contact the admin of the remote instance</comment>'
		);
		$question = new ConfirmationQuestion(
			'Do you consider this new identity as valid and update the entry in the database? (y/N) ',
			false,
			'/^(y|Y)/i'
		);

		if ($helper->ask($this->input, $this->output, $question)) {
			$this->remoteStreamService->update($remoteSignatory);
			$this->output->writeln('remote instance updated');
		}
	}


	/**
	 * @param string $remote
	 * @param array $payload
	 *
	 * @return NCSignedRequest
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 */
	private function outgoingTest(string $remote, array $payload): NCSignedRequest {
		$request = new NCRequest();
		$request->basedOnUrl($remote);
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);
		$request->setData($payload);

		$app = $this->remoteStreamService->getAppSignatory();
		$signedRequest = $this->remoteStreamService->signOutgoingRequest($request, $app);
		$outgoingRequest = $signedRequest->getOutgoingRequest();
		$outgoingRequest->setLocalAddressAllowed(true);
		$outgoingRequest->setFollowLocation(true);

		$this->doRequest($outgoingRequest);

		return $signedRequest;
	}


	/**
	 *
	 */
	private function checkKnownInstance(): void {
		$this->verifyGSInstances();
		$this->checkRemoteInstances();
	}


	/**
	 *
	 */
	private function verifyGSInstances(): void {
		$instances = $this->globalScaleService->getGlobalScaleInstances();
		$known = array_map(
			function (RemoteInstance $instance): string {
				return $instance->getInstance();
			}, $this->remoteRequest->getFromType(RemoteInstance::TYPE_GLOBALSCALE)
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
		$this->output->write('Adding <comment>' . $instance . '</comment>: ');
		if ($this->configService->isLocalInstance($instance)) {
			$this->output->writeln('<comment>instance is local</comment>');
			return;
		}

		try {
			$this->remoteStreamService->addRemoteInstance(
				$instance,
				RemoteInstance::TYPE_GLOBALSCALE,
				InterfaceService::IFACE_INTERNAL,
				true
			);
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
		$table->setHeaders(['Instance', 'Type', 'iface', 'UID', 'Authed', 'Aliases']);
		$table->render();

		foreach ($instances as $instance) {
			try {
				$current = $this->remoteStreamService->retrieveRemoteInstance($instance->getInstance());
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
					InterfaceService::$LIST_IFACE[$instance->getInterface()],
					$instance->getUid(),
					$currentUid,
					json_encode($instance->getAliases())
				]
			);
		}
	}


	/**
	 * @throws Exception
	 */
	private function getRemoteType(): string {
		foreach (RemoteInstance::$LIST_TYPE as $type) {
			if (strtolower($this->input->getOption('type')) === strtolower($type)) {
				return $type;
			}
		}

		throw new Exception('Unknown type: ' . implode(', ', RemoteInstance::$LIST_TYPE));
	}

	/**
	 * @throws Exception
	 */
	private function getRemoteInterface(): int {
		foreach (InterfaceService::$LIST_IFACE as $iface => $def) {
			if (strtolower($this->input->getOption('iface')) === strtolower($def)) {
				return $iface;
			}
		}

		throw new Exception('Unknown interface: ' . implode(', ', InterfaceService::$LIST_IFACE));
	}
}
