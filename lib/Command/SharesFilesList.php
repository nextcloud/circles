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

use daita\MySmallPhpTools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\ShareWrapperService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class SharesFilesList
 *
 * @package OCA\Circles\Command
 */
class SharesFilesList extends Base {


	use TArrayTools;


	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var ShareWrapperService */
	private $shareWrapperService;


	/**
	 * SharesFilesList constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param ShareWrapperService $shareWrapperService
	 */
	public function __construct(
		FederatedUserService $federatedUserService, ShareWrapperService $shareWrapperService
	) {
		parent::__construct();
		$this->federatedUserService = $federatedUserService;
		$this->shareWrapperService = $shareWrapperService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:shares:files:list')
			 ->setDescription('listing shares files')
			 ->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($input->getOption('initiator'));
		$shareWrappers = $this->shareWrapperService->getSharedWith($federatedUser, 0, -1, 0, true);

		echo json_encode($shareWrappers, JSON_PRETTY_PRINT);
		$output = new ConsoleOutput();
		$output = $output->section();
		$table = new Table($output);
		$table->setHeaders(['Share Id', 'Original', 'Displayed Name']);
		$table->render();

		foreach ($shareWrappers as $share) {
			$table->appendRow(
				[
					$share->getId(),
					$share->getFileTarget(),
					($share->getChildId() > 0) ? $share->getChildFileTarget() : $share->getFileTarget()
				]
			);
		}

		return 0;
	}

}

