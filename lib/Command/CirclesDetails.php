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

use OC\Core\Command\Base;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCP\IL10N;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesDetails
 *
 * @package OCA\Circles\Command
 */
class CirclesDetails extends Base {


	/** @var IL10N */
	private $l10n;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;


	/**
	 * CirclesDetails constructor.
	 *
	 * @param IL10N $l10n
	 * @param DeprecatedCirclesRequest $circlesRequest
	 */
	public function __construct(IL10N $l10n, DeprecatedCirclesRequest $circlesRequest) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->circlesRequest = $circlesRequest;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:details')
			 ->setDescription('get details about a circle by its ID')
			 ->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleDoesNotExistException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = $input->getArgument('circle_id');

		$circle = $this->circlesRequest->forceGetCircle($circleId);

		echo json_encode($circle, JSON_PRETTY_PRINT) . "\n";

		return 0;
	}

}

