<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Service;

use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\Migration\IOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OutputService
 *
 * @package OCA\Circles\Service
 */
class OutputService {
	use TStringTools;
	use TNCLogger;


	/** @var IOutput */
	private $migrationOutput;

	/** @var OutputInterface */
	private $occOutput;


	public function __construct() {
	}


	/**
	 * @param OutputInterface $output
	 */
	public function setOccOutput(OutputInterface $output): void {
		$this->occOutput = $output;
	}

	/**
	 * @param IOutput $output
	 */
	public function setMigrationOutput(IOutput $output): void {
		$this->migrationOutput = $output;
	}


	/**
	 * @param string $message
	 * @param bool $advance
	 */
	public function output(string $message, bool $advance = false): void {
		if (!is_null($this->occOutput)) {
			$this->occOutput->writeln((($advance) ? '+' : '-') . ' ' . $message);
		}

		if (!is_null($this->migrationOutput)) {
			if ($advance) {
				$this->migrationOutput->advance(1, '(Circles) ' . $message);
			} else {
				$this->migrationOutput->info('(Circles) ' . $message);
			}
		}
	}


	/**
	 * @param int $int
	 */
	public function startMigrationProgress(int $int): void {
		if (is_null($this->migrationOutput)) {
			return;
		}

		$this->migrationOutput->startProgress($int);
	}


	/**
	 *
	 */
	public function finishMigrationProgress(): void {
		if (is_null($this->migrationOutput)) {
			return;
		}

		$this->migrationOutput->finishProgress();
	}
}
