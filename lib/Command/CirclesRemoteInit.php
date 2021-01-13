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
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21WellKnown;
use daita\MySmallPhpTools\Traits\TStringTools;
use OC\Core\Command\Base;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\RemoteService;
use OCP\IL10N;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


/**
 * Class CirclesRemote
 *
 * @package OCA\Circles\Command
 */
class CirclesRemoteInit extends Base {


	use TNC21WellKnown;
	use TStringTools;


	/** @var IL10N */
	private $l10n;

	/** @var RemoteService */
	private $remoteService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CirclesList constructor.
	 *
	 * @param RemoteService $remoteService
	 * @param ConfigService $configService
	 */
	public function __construct(RemoteService $remoteService, ConfigService $configService) {
		parent::__construct();

		$this->remoteService = $remoteService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:remote:init')
			 ->setDescription('init remote features')
			 ->addOption('reset', '', InputOption::VALUE_NONE, 'stop Federated Circles');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws SignatoryException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('reset')) {
			$this->reset($input, $output);

			return 0;
		}

		try {
			$signatory = $this->remoteService->getAppSignatory();
			$output->writeln('Looks like Federated Circles is already enabled:');
			$output->writeln('');
			$output->writeln(
				'<info>' . json_encode($signatory, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
				. '</info>'
			);

			$output->writeln('');
			$output->writeln(
				'Run <comment>./occ circles:remote:init --reset</comment> to disable the feature'
			);

			return 0;
		} catch (SignatoryException $e) {
		}

		$this->init($input, $output);

		return 0;
	}


	private function reset(InputInterface $input, OutputInterface $output): void {
		try {
			$this->remoteService->getAppSignatory();
		} catch (SignatoryException $e) {
			$output->writeln('<error>Federated Circles not enabled</error>');

			return;
		}

		$output->writeln(
			'<comment>Warning</comment>: Reset will disable Federated Circles but also reset your current identity'
		);

		$output->writeln('');
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion(
			'<info>Do you want to reset/disable Federated Circles ?</info> (y/N) ', false, '/^(y|Y)/i'
		);

		if ($helper->ask($input, $output, $question)) {
			$this->remoteService->resetAppSignatory();
			$output->writeln('Federated Circles have been reset');
		}
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws SignatoryException
	 */
	private function init(InputInterface $input, OutputInterface $output): void {
		$output->writeln(
			'Federated Circles is a new feature that allows you to open your circles to other instances of Nextcloud'
		);
		$output->writeln('This feature is a Work in Progress and still in development state.');
		$output->writeln('');

		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion(
			'<info>Do you want to enable this feature ?</info> (y/N) ', false, '/^(y|Y)/i'
		);

		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('Federated Circles NOT enabled');

			return;
		}


		$output->writeln('');
		$output->writeln(
			'Please understand that the feature is <comment>in ALPHA version</comment>, is <comment>not enabled by default</comment>, and <comment>should not be enable</comment> unless you really need it'
		);
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion(
			'<info>I understand this but I really want this feature!</info> (y/N) ', false, '/^(y|Y)/i'
		);

		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('Federated Circles NOT enabled');

			return;
		}


		$currInstance = $this->configService->getLocalInstance();
		$output->writeln('');
		$output->writeln('The domain name of your instance is: <info>' . $currInstance . '</info>');
		$helper = $this->getHelper('question');
		$question =
			new Question('<info>Change your domain name:</info> (' . $currInstance . ') ', $currInstance);

		$newInstance = $helper->ask($input, $output, $question);

		if ($newInstance !== $currInstance) {
			$this->configService->setAppValue(ConfigService::LOCAL_CLOUD_ID, $newInstance);
		}


		$output->writeln('');
		$currScheme = $this->configService->getAppValue(ConfigService::LOCAL_CLOUD_SCHEME);
		$output->writeln('Current protocol is <info>' . strtoupper($currScheme) . '</info>');
		$question =
			new Question('<info>Change the used protocol:</info> (' . $currScheme . ') ', $currScheme);
		$newScheme = strtolower($helper->ask($input, $output, $question));

		if ($newScheme !== $currScheme) {
			if (!in_array($newScheme, ['http', 'https'])) {
				$output->writeln('<error>protocol can only be \'https\' or \'http\'</error>');

				return;
			}

			$this->configService->setAppValue(ConfigService::LOCAL_CLOUD_SCHEME, $newScheme);
		}

		$output->writeln('');
		$output->writeln('');
		$output->writeln('Federated Circles is now <info>enable</info>.');
		$output->writeln('Your identity: ');

		$app = $this->remoteService->getAppSignatory(true);

		$output->writeln(
			'<info>' . json_encode($app, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</info>'
		);
		$output->writeln('');
	}

}

