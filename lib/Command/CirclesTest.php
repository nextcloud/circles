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

use daita\MySmallPhpTools\Exceptions\ItemNotFoundException;
use daita\MySmallPhpTools\Traits\TArrayTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Service\ConfigService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;


/**
 * Class CirclesTest
 *
 * @package OCA\Circles\Command
 */
class CirclesTest extends Base {


	use TArrayTools;


	/** @var ConfigService */
	private $configService;


	/** @var array */
	private $config = [];

	/** @var array */
	private $sessions = [];


	/**
	 * CirclesTest constructor.
	 *
	 * @param ConfigService $configService
	 */
	public function __construct(ConfigService $configService) {
		parent::__construct();

		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:test')
			 ->setDescription('testing some features')
			 ->addArgument('deprecated', InputArgument::OPTIONAL, '')
			 ->addOption(
				 'am-i-aware-this-will-delete-all-my-data', '', InputOption::VALUE_REQUIRED,
				 'Well, are you ?', ''
			 );
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('am-i-aware-this-will-delete-all-my-data') == 'yes') {
			$this->testCirclesApp();

			return 0;
		}

		$output->writeln('');
		$output->writeln(
			'<error>Since Nextcloud 22, this command have changed, please read the message below:</error>'
		);
		$output->writeln('<error>This new command is to test the integrity of the Circles App.</error>');
		$output->writeln(
			'<error>Running this command will REMOVE all your current configuration and all your current Circles.</error>'
		);
		$output->writeln('<error>There is a huge probability that you do not want to do that.</error>');
		$output->writeln('');
		$output->writeln(
			'<error>The old testing command you might looking for have moved to "./occ circles:check"</error>'
		);
		$output->writeln('');

		return 0;
	}


	/**
	 * @throws ItemNotFoundException
	 */
	private function testCirclesApp() {
		$this->loadConfiguration();

		$listing = $this->occ('global-scale-1', 'circles:manage:list');

		echo json_encode($listing, JSON_PRETTY_PRINT) . "\n";
	}


	/**
	 * @param string $instance
	 * @param string $cmd
	 *
	 * @return array
	 * @throws ItemNotFoundException
	 */
	private function occ(string $instance, string $cmd): array {
		$configInstance = $this->getConfigInstance($instance);
		$path = $this->get('path', $configInstance);
		$occ = rtrim($path, '/') . '/occ';

		$process = new Process(array_merge([$occ], explode(' ', $cmd), ['--output=json']));
		$process->run();

		return json_decode($process->getOutput(), true);
	}


	/**
	 *
	 */
	private function loadConfiguration() {
		$configuration = file_get_contents(__DIR__ . '/../../testConfiguration.json');
		$this->config = json_decode($configuration, true);
	}


	/**
	 * @param string $instance
	 *
	 * @return array
	 * @throws ItemNotFoundException
	 */
	private function getConfigInstance(string $instance): array {
		foreach ($this->getArray('instances', $this->config) as $item) {
			if (strtolower($this->get('id', $item)) === strtolower($instance)) {
				return $item;
			}
		}

		throw new ItemNotFoundException();
	}

}

