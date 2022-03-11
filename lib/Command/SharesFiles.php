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

use OCA\Circles\Tools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\ShareWrapperService;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SharesFilesList
 *
 * @package OCA\Circles\Command
 */
class SharesFiles extends Base {
	use TArrayTools;


	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var ShareWrapperService */
	private $shareWrapperService;

	/** @var ConfigService */
	private $configService;


	/** @var int */
	private $fileId = 0;


	/**
	 * SharesFilesList constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param ShareWrapperService $shareWrapperService
	 * @param ConfigService $configService
	 */
	public function __construct(
		FederatedUserService $federatedUserService, ShareWrapperService $shareWrapperService,
		ConfigService $configService
	) {
		parent::__construct();
		$this->federatedUserService = $federatedUserService;
		$this->shareWrapperService = $shareWrapperService;
		$this->configService = $configService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:shares:files')
			 ->setDescription('listing shares files')
			 ->addArgument('file_id', InputArgument::OPTIONAL, 'filter on a File Id', '0')
			 ->addOption('to', '', InputOption::VALUE_REQUIRED, 'get files shared TO CIRCLEID', '')
			 ->addOption('with', '', InputOption::VALUE_REQUIRED, 'get files shared WITH USERID', '')
			 ->addOption('by', '', InputOption::VALUE_REQUIRED, 'get files shared BY USERID', '')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'get all data about the shares');
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
		$this->fileId = (int)$input->getArgument('file_id');
		$json = (strtolower($input->getOption('output')) === 'json');

		$this->displayShares(
			(int)$input->getArgument('file_id'),
			$input->getOption('to'),
			$input->getOption('with'),
			$input->getOption('by'),
			$input->getOption('all'),
			$json
		);

		return 0;
//
//		if ($input->getOption('to')) {
//			$this->sharedToCircle(
//				$input->getOption('to'),
//				$input->getOption('with'),
//				$input->getOption('by'),
//				$input->getOption('all'),
//				$json
//			);
//
//			return 0;
//		}
//
//		if ($input->getOption('with')) {
//			$this->sharedWith($input->getOption('with'), $json);
//
//			return 0;
//		}
//
//		if ($input->getOption('by')) {
//			$this->sharesBy($input->getOption('by'), $json);
//
//			return 0;
//		}
//
//		if ($this->fileId > 0) {
//			$this->sharedFile($json);
//
//			return 0;
//		}
	}


	/**
	 * @param int $fileId
	 * @param string $to
	 * @param string $with
	 * @param string $by
	 * @param bool $all
	 * @param bool $json
	 *
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	private function displayShares(
		int $fileId,
		string $to,
		string $with,
		string $by,
		bool $all,
		bool $json
	): void {
		$shareWrappers = $this->getShares($fileId, $to, $with, $by, $all, $filterRecipient);

		$output = new ConsoleOutput();
		if ($json) {
			$output->writeln(json_encode($shareWrappers, JSON_PRETTY_PRINT));

			return;
		}

		$output = $output->section();
		$table = new Table($output);
		$headers = [
			'Share Id',
			'File Id',
			'File Owner',
			'Original Filename',
			'Shared By',
			'Shared To'
		];

		if (!$filterRecipient) {
			$headers = array_merge($headers, ['Recipient', 'Target Name']);
		}

		$table->setHeaders($headers);
		$table->render();

		foreach ($shareWrappers as $share) {
			if (!$filterRecipient) {
				$recipient = $share->getInitiator();
				$sharedTo = $recipient->getDisplayName();
				if (!$this->configService->isLocalInstance($recipient->getInstance())) {
					$sharedTo .= '@' . $recipient->getInstance();
				}
			}
			$circle = $share->getCircle();
			$row = [
				$share->getId(),
				$share->getFileSource(),
				$share->getShareOwner(),
				$share->getFileTarget(),
				$share->getSharedBy(),
				$circle->getDisplayName() . ' (' . $share->getSharedWith()
				. ', ' . Circle::$DEF_SOURCE[$circle->getSource()] . ')'
			];

			if (!$filterRecipient) {
				$row = array_merge(
					$row, [
						$sharedTo . ' (' . $recipient->getSingleId() . ', '
						. Circle::$DEF_SOURCE[$recipient->getBasedOn()->getSource()] . ')',
						(($share->getChildId() > 0) ? $share->getChildFileTarget(
						) : $share->getFileTarget()),
					]
				);
			}

			$table->appendRow($row);
		}
	}


	/**
	 * @param int $fileId
	 * @param string $to
	 * @param string $with
	 * @param string $by
	 * @param bool $all
	 * @param bool|null $filterRecipient
	 *
	 * @return ShareWrapper[]
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	private function getShares(
		int $fileId,
		string $to,
		string $with,
		string $by,
		bool $all,
		?bool &$filterRecipient = false
	): array {
		if ($fileId > 0) {
			return $this->shareWrapperService->getSharesByFileId($this->fileId, true);
		}

		if ($to !== '') {
			$filterRecipient = !$all;

			return $this->shareWrapperService->getSharesToCircle(
				$to,
				($with === '') ? null : $this->federatedUserService->getLocalFederatedUser($with),
				($by === '') ? null : $this->federatedUserService->getLocalFederatedUser($by),
				$all
			);
		}

		if ($by !== '') {
			$filterRecipient = !$all;

			return $this->shareWrapperService->getSharesBy(
				$this->federatedUserService->getLocalFederatedUser($by),
				$fileId,
				true,
				-1,
				0,
				true,
				$all
			);
		}

		if ($with !== '') {
			$probe = new CircleProbe();
			$probe->includePersonalCircles();

			return $this->shareWrapperService->getSharedWith(
				$this->federatedUserService->getLocalFederatedUser($with),
				$fileId,
				$probe
			);
		}

		throw new MissingInputException(
			'Specify a FileId or an option: --with (USER), --by (USER), --to (CIRCLE)'
		);
	}
}
