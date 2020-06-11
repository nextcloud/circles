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

use daita\MySmallPhpTools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\GlobalScaleService;
use OCP\IL10N;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesList
 *
 * @package OCA\Circles\Command
 */
class CirclesTest extends Base {


	use TArrayTools;


	/** @var IL10N */
	private $l10n;

	/** @var GlobalScaleService */
	private $globalScaleService;

	/** @var ConfigService */
	private $configService;

	/**
	 * CirclesList constructor.
	 *
	 * @param IL10N $l10n
	 * @param GlobalScaleService $globalScaleService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IL10N $l10n, GlobalScaleService $globalScaleService, ConfigService $configService
	) {
		parent::__construct();

		$this->l10n = $l10n;
		$this->globalScaleService = $globalScaleService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:test')
			 ->setDescription('testing some features');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$instances = $this->globalScaleService->getInstances(true);
		$output->writeln('<info>Instances: </info>' . json_encode($instances));

		return 0;
	}

}

