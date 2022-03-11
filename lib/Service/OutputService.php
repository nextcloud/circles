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
