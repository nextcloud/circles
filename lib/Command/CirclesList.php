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

use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\SignatureException;
use daita\MySmallPhpTools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\ModelManager;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\RemoteService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesList
 *
 * @package OCA\Circles\Command
 */
class CirclesList extends Base {


	use TArrayTools;


	/** @var CircleService */
	private $circleService;

	/** @var RemoteService */
	private $remoteService;

	/** @var ModelManager */
	private $modelManager;


	/**
	 * CirclesList constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param RemoteService $remoteService
	 * @param ModelManager $modelManager
	 */
	public function __construct(
		CircleRequest $circleRequest, CircleService $circleService, RemoteService $remoteService,
		ModelManager $modelManager
	) {
		parent::__construct();
		$this->circleService = $circleService;
		$this->remoteService = $remoteService;
		$this->modelManager = $modelManager;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:list')
			 ->setDescription('listing current circles')
			 ->addArgument('owner', InputArgument::OPTIONAL, 'filter by owner', '')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'display also hidden Circles')
			 ->addOption('viewer', '', InputOption::VALUE_REQUIRED, 'set viewer', '')
			 ->addOption('json', '', InputOption::VALUE_NONE, 'returns result as JSON')
			 ->addOption('remote', '', InputOption::VALUE_REQUIRED, 'remote Nextcloud address', '');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$owner = $input->getArgument('owner');
		$viewer = $input->getOption('viewer');
		$json = $input->getOption('json');
		$remote = $input->getOption('remote');

		$output = new ConsoleOutput();
		$output = $output->section();
		$circles = $this->getCircles($owner, $viewer, $remote);

		if ($json) {
			echo json_encode($circles, JSON_PRETTY_PRINT) . "\n";

			return 0;
		}

		$table = new Table($output);
		$table->setHeaders(['ID', 'Name', 'Type', 'Owner', 'Instance', 'Limit', 'Description']);
		$table->render();

		foreach ($circles as $circle) {
			if ($circle->isHidden() && !$input->getOption('all')) {
				continue;
			}

			$owner = $circle->getOwner();
			$settings = $circle->getSettings();
			$table->appendRow(
				[
					$circle->getId(),
					$circle->getName(),
					json_encode($this->modelManager->getCircleTypes($circle, ModelManager::TYPES_SHORT)),
					$owner->getUserId(),
					$owner->getInstance(),
					$this->getInt('members_limit', $settings, -1),
					substr($circle->getDescription(), 0, 30)
				]
			);
		}

		return 0;
	}


	/**
	 * @param string $owner
	 * @param string $viewer
	 * @param string $remote
	 *
	 * @return Circle[]
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SignatureException
	 * @throws InvalidItemException
	 */
	private function getCircles(string $owner, string $viewer, string $remote): array {
		if ($viewer !== '') {
			$this->circleService->setLocalViewer($viewer);
		}

		if ($remote !== '') {
			return $this->remoteService->getCircles($remote);
		}

		return $this->circleService->getCircles($owner);
	}

}

