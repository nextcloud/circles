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

use daita\MySmallPhpTools\Exceptions\RequestContentException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\RequestResultSizeException;
use daita\MySmallPhpTools\Exceptions\RequestServerException;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TRequest;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\GlobalScaleService;
use OCA\Circles\Service\GSUpstreamService;
use OCP\IL10N;
use OCP\IURLGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesList
 *
 * @package OCA\Circles\Command
 */
class CirclesTest extends Base {


	use TArrayTools;
	use TRequest;


	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var GlobalScaleService */
	private $globalScaleService;

	/** @var GSUpstreamService */
	private $gsUpstreamService;

	/** @var ConfigService */
	private $configService;


	/** @var int */
	private $delay = 5;


	/**
	 * CirclesList constructor.
	 *
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 * @param GlobalScaleService $globalScaleService
	 * @param GSUpstreamService $gsUpstreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IL10N $l10n, IURLGenerator $urlGenerator, GlobalScaleService $globalScaleService,
		GSUpstreamService $gsUpstreamService, ConfigService $configService
	) {
		parent::__construct();

		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->gsUpstreamService = $gsUpstreamService;
		$this->globalScaleService = $globalScaleService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:test')
			 ->setDescription('testing some features')
			 ->addOption('delay', 'd', InputOption::VALUE_REQUIRED, 'delay before checking result');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('delay')) {
			$this->delay = (int)$input->getOption('delay');
		}

		if (!$this->testLocalAddress($output)) {
			return 0;
		}

		$instances =
			array_merge([$this->configService->getLocalCloudId()], $this->globalScaleService->getInstances());

		$test = new GSEvent(GSEvent::TEST, true, true);
		$test->setAsync(true);
		$wrapper = $this->gsUpstreamService->newEvent($test);

		$output->writeln('- Async request is sent, now waiting ' . $this->delay . ' seconds');
		sleep($this->delay);
		$output->writeln('- Pause is over, checking results for ' . $wrapper->getToken());

		$wrappers = $this->gsUpstreamService->getEventsByToken($wrapper->getToken());

		$result = [];
		foreach ($wrappers as $wrapper) {
			$result[$wrapper->getInstance()] = $wrapper->getEvent();
		}

		foreach ($instances as $instance) {
			$output->write($instance . ' ');
			if (array_key_exists($instance, $result)
				&& $result[$instance]->getResult()
									 ->gInt('status') === 1) {
				$output->writeln('<info>ok</info>');
			} else {
				$output->writeln('<error>fail</error>');
			}
		}

		return 0;
	}


	/**
	 * @param OutputInterface $output
	 *
	 * @return bool
	 * @throws RequestContentException
	 * @throws RequestNetworkException
	 * @throws RequestResultSizeException
	 * @throws RequestServerException
	 */
	private function testLocalAddress(OutputInterface $output): bool {
		$absolute = $this->urlGenerator->linkToRouteAbsolute('core.CSRFToken.index');
		$output->write('- Simple request on ' . $absolute . ': ');

		$request = new Request('', Request::TYPE_GET);
		$request->setAddressFromUrl($absolute);
		if (method_exists($request, 'setFollowLocation')) {
			$request->setFollowLocation(false);
		}

		$this->doRequest($request);
		$color = 'error';
		if ($request->getResultCode() === 200) {
			$color = 'info';
		}
		$output->writeln('<' . $color . '>' . $request->getResultCode() . '</' . $color . '>');

		if ($request->getResultCode() === 200) {
			return true;
		}

		return false;
	}

}

