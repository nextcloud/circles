<?php
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

use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Db\CirclesRequest;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Clean extends Base {

	/** @var CirclesRequest */
	private $circlesRequest;

	public function __construct(CirclesRequest $circlesRequest) {
		parent::__construct();
		$this->circlesRequest = $circlesRequest;
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:clean')
			 ->setDescription('remove all extra data from database');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		try {
			$this->removeCirclesWithNoOwner();

			$output->writeln('done');
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
		}
	}


	private function removeCirclesWithNoOwner() {

		$circles = $this->circlesRequest->forceGetCircles();

		foreach ($circles as $circle) {
			if ($circle->getOwner()
					   ->getUserId() === null) {
				$this->circlesRequest->destroyCircle($circle->getUniqueId());
			}
		}
	}
}



