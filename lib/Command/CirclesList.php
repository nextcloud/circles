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
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ModelManager;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
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


	/** @var ModelManager */
	private $modelManager;

	/** @var CircleService */
	private $circleService;

	/** @var RemoteService */
	private $remoteService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CirclesList constructor.
	 *
	 * @param ModelManager $modelManager
	 * @param CircleService $circleService
	 * @param RemoteService $remoteService
	 * @param ConfigService $configService
	 */
	public function __construct(
		ModelManager $modelManager, CircleService $circleService, RemoteService $remoteService,
		ConfigService $configService
	) {
		parent::__construct();
		$this->modelManager = $modelManager;
		$this->circleService = $circleService;
		$this->remoteService = $remoteService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:list')
			 ->setDescription('listing current circles')
			 ->addArgument('owner', InputArgument::OPTIONAL, 'filter by owner', '')
			 ->addOption('level', '', InputOption::VALUE_REQUIRED, 'level of membership', Member::LEVEL_OWNER)
			 ->addOption('def', '', InputOption::VALUE_NONE, 'display complete circle configuration')
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
	 * @throws InvalidItemException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$owner = $input->getArgument('owner');
		$level = $input->getOption('level');
		$viewer = $input->getOption('viewer');
		$json = $input->getOption('json');
		$remote = $input->getOption('remote');

		$output = new ConsoleOutput();
		$output = $output->section();

		$filter = null;
		if ($owner !== '') {
			$filter = new Member($owner, Member::TYPE_USER, '');
			$filter->setLevel((int)$level);
		}
		$circles = $this->getCircles($filter, $viewer, $remote);

		if ($json) {
			echo json_encode($circles, JSON_PRETTY_PRINT) . "\n";

			return 0;
		}

		$table = new Table($output);
		$table->setHeaders(['ID', 'Name', 'Type', 'Owner', 'Instance', 'Limit', 'Description']);
		$table->render();

		$local = $this->configService->getLocalInstance();
		$display = ($input->getOption('def') ? ModelManager::TYPES_LONG : ModelManager::TYPES_SHORT);
		foreach ($circles as $circle) {
//			if ($circle->isHidden() && !$input->getOption('all')) {
//				continue;
//			}

			$owner = $circle->getOwner();
			$table->appendRow(
				[
					$circle->getId(),
					$circle->getName(),
					json_encode($this->modelManager->getCircleTypes($circle, $display)),
					$owner->getUserId(),
					($owner->getInstance() === $local) ? '' : $owner->getInstance(),
					$this->getInt('members_limit', $circle->getSettings(), -1),
					substr($circle->getDescription(), 0, 30)
				]
			);
		}

		return 0;
	}


	/**
	 * @param Member|null $filter
	 * @param string $viewer
	 * @param string $remote
	 *
	 * @return Circle[]
	 * @throws InvalidItemException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	private function getCircles(?Member $filter, string $viewer, string $remote): array {
		if ($viewer !== '') {
			$this->circleService->setLocalViewer($viewer);
		}

		if ($remote !== '') {
			return $this->remoteService->getCircles($remote);
		}

		return $this->circleService->getCircles($filter);
	}

}

