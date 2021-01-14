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

use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\SignatureException;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21WellKnown;
use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteUidException;
use OCA\Circles\Model\AppService;
use OCA\Circles\Service\RemoteService;
use OCP\IL10N;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Class CirclesRemote
 *
 * @package OCA\Circles\Command
 */
class CirclesRemote extends Base {


	use TNC21WellKnown;
	use TStringTools;


	/** @var IL10N */
	private $l10n;

	/** @var RemoteService */
	private $remoteService;


	/**
	 * CirclesList constructor.
	 *
	 * @param RemoteService $remoteService
	 */
	public function __construct(RemoteService $remoteService) {
		parent::__construct();

		$this->remoteService = $remoteService;
		$this->setup('app', 'circles');
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:remote')
			 ->setDescription('remote features')
			 ->addArgument('host', InputArgument::REQUIRED, 'host of the remote instance of Nextcloud')
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

		$webfinger = $this->getWebfinger($host, Application::APP_SUBJECT);
		if ($input->getOption('all')) {
			$output->writeln('- Webfinger on <info>' . $host . '</info>');
			$output->writeln(json_encode($webfinger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			$output->writeln('');
		}

		if ($input->getOption('all')) {
			$circleLink = $this->extractLink(Application::APP_REL, $webfinger);
			$output->writeln('- Information about Circles app on <info>' . $host . '</info>');
			$output->writeln(json_encode($circleLink, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			$output->writeln('');
		}

		$output->writeln('- Available services on <info>' . $host . '</info>');
		foreach ($webfinger->getLinks() as $link) {
			$app = $link->getProperty('name');
			$ver = $link->getProperty('version');
			if ($app !== '') {
				$app .= ' ';
			}
			if ($ver !== '') {
				$ver = 'v' . $ver;
			}

			$output->writeln(' * ' . $link->getRel() . ' ' . $app . $ver);
		}
		$output->writeln('');

		$output->writeln('- Resources related to Circles on <info>' . $host . '</info>');
		$resource = $this->getResourceData($host, Application::APP_SUBJECT, Application::APP_REL);
		$output->writeln(json_encode($resource, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		$output->writeln('');


		$tempUid = $resource->g('uid');
		$output->writeln(
			'- Confirming UID=' . $tempUid . ' from parsed Signatory at <info>' . $host . '</info>'
		);

		try {
			$remoteSignatory = $this->remoteService->retrieveSignatory($resource->g('id'), true, true);
			$output->writeln(' * No SignatureException: <info>Identity authed</info>');
		} catch (SignatureException $e) {
			$output->writeln(
				'<error>' . $host . ' cannot auth its identity: ' . $e->getMessage() . '</error>'
			);

			return 0;
		}

		$output->writeln(' * Found <info>' . $remoteSignatory->getUid() . '</info>');
		if ($remoteSignatory->getUid(true) !== $tempUid) {
			$output->writeln('<error>looks like ' . $host . ' is faking its identity');

			return 0;
		}

		$output->writeln('');

		$testUrl = $resource->g('test');
		$output->writeln('- Testing signed payload on <info>' . $testUrl . '</info>');

		try {
			$localSignatory = $this->remoteService->getAppSignatory();
		} catch (SignatoryException $e) {
			$output->writeln(
				'<error>Federated Circles not enabled locally. Please run ./occ circles:remote:init</error>'
			);

			return 0;
		}

		$payload = [
			'test'  => 42,
			'token' => $this->uuid()
		];
		$signedRequest = $this->remoteService->test($testUrl, $payload);
		$output->writeln(' * Payload: ');
		$output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		$output->writeln('');

		$output->writeln(' * Clear Signature: ');
		$output->writeln('<comment>' . $signedRequest->getClearSignature() . '</comment>');
		$output->writeln('');

		$output->writeln(' * Signed Signature (base64 encoded): ');
		$output->writeln('<comment>' . base64_encode($signedRequest->getSignedSignature()) . '</comment>');
		$output->writeln('');

		$result = $signedRequest->getOutgoingRequest()->getResult();
		$code = $result->getStatusCode();
		$output->writeln(' * Result: ' . (($code === 200) ? '<info>' . $code . '</info>' : $code));
		$output->writeln(
			json_encode(json_decode($result->getContent(), true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
		);
		$output->writeln('');

		if ($input->getOption('all')) {
			$output->writeln('');
			$output->writeln('<info>### Complete report ###</info>');
			$output->writeln(json_encode($signedRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			$output->writeln('');
		}

		if ($remoteSignatory->getUid() !== $localSignatory->getUid()) {
			$remoteSignatory->setInstance($host);
			try {
				$stored = new AppService();
				$this->remoteService->confirmValidRemote($remoteSignatory, $stored);
				$output->writeln(
					'<info>The remote instance ' . $host
					. ' is already known with this current identity</info>'
				);

				if ($remoteSignatory->getInstance() !== $stored->getInstance()) {
					$output->writeln(
						'- updating host from ' . $stored->getInstance() . ' to '
						. $remoteSignatory->getInstance()
					);
					$this->remoteService->update($remoteSignatory, RemoteService::UPDATE_INSTANCE);
				}
				if ($remoteSignatory->getId() !== $stored->getId()) {
					$output->writeln(
						'- updating href/Id from ' . $stored->getId() . ' to '
						. $remoteSignatory->getId()
					);
					$this->remoteService->update($remoteSignatory, RemoteService::UPDATE_HREF);
				}

			} catch (RemoteUidException $e) {
				$this->updateRemote($input, $output, $remoteSignatory);
			} catch (RemoteNotFoundException $e) {
				$this->saveRemote($input, $output, $remoteSignatory);
			}
		}

		return 0;
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param AppService $remoteSignatory
	 */
	private function saveRemote(InputInterface $input, OutputInterface $output, AppService $remoteSignatory) {
		$output->writeln('');
		$helper = $this->getHelper('question');

		$output->writeln(
			'The remote instance <info>' . $remoteSignatory->getInstance() . '</info> looks good.'
		);
		$question = new ConfirmationQuestion(
			'Would you like to allow the sharing of your circles with this remote instance ? (y/N) ',
			false,
			'/^(y|Y)/i'
		);

		if ($helper->ask($input, $output, $question)) {
			$this->remoteService->save($remoteSignatory);
			$output->writeln('<info>remote instance saved</info>');
		}
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param AppService $remoteSignatory
	 */
	private function updateRemote(InputInterface $input, OutputInterface $output, AppService $remoteSignatory
	) {
		$output->writeln('');
		$helper = $this->getHelper('question');

		$output->writeln(
			'The remote instance <info>' . $remoteSignatory->getInstance()
			. '</info> is known but <error>its identity has changed.</error>'
		);
		$output->writeln(
			'<comment>If you are not sure on why identity changed, please say No to the next question and contact the admin of the remote instance</comment>'
		);
		$question = new ConfirmationQuestion(
			'Do you consider this new identity as valid and update the entry in the database? (y/N) ',
			false,
			'/^(y|Y)/i'
		);

		if ($helper->ask($input, $output, $question)) {
			$this->remoteService->update($remoteSignatory);
			$output->writeln('remote instance updated');
		}
	}


}

