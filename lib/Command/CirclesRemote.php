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

use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21WellKnown;
use daita\MySmallPhpTools\Traits\TArrayTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Service\SignatureService;
use OCP\IL10N;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesRemote
 *
 * @package OCA\Circles\Command
 */
class CirclesRemote extends Base {


	use TArrayTools;
	use TNC21WellKnown;


	/** @var IL10N */
	private $l10n;

	/** @var SignatureService */
	private $signatureService;


	/**
	 * CirclesList constructor.
	 *
	 * @param SignatureService $signatureService
	 */
	public function __construct(SignatureService $signatureService) {
		parent::__construct();

		$this->signatureService = $signatureService;
	}


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

		$resource = $this->getResourceData($host, Application::APP_SUBJECT, Application::APP_REL);
		if ($input->getOption('all')) {
			$output->writeln('- Resources related to Circles on <info>' . $host . '</info>');
			$output->writeln(json_encode($resource, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			$output->writeln('');
		}

		$testUrl = $resource->g('test');
		$output->writeln('- Testing signed payload on <info>' . $testUrl . '</info>');


		$orig = ['test' => 42];
		$signedRequest = $this->signatureService->test($testUrl, $orig);
//		$output->writeln('* Clear Signature: ');
		$output->writeln(json_encode($signedRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

		return 0;
	}

}

