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

use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Probes\CircleProbe;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Test extends Base {


	/** @var CircleRequest */
	private $circleRequest;


	/**
	 * @param CircleRequest $circleRequest
	 */
	public function __construct(
		CircleRequest $circleRequest
	) {
		parent::__construct();

		$this->circleRequest = $circleRequest;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:db-test')
			 ->setDescription('testing some features');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		echo 'ok' . "\n";

		$this->circleRequest->getCircle();


//								public function getCircles(?IFederatedUser $initiator, CircleProbe $probe): array {
//									public function getCirclesByIds(array $circleIds): array {
//										public function getCircle(
//											public function getFederatedUserBySingleId(string $singleId): FederatedUser {
//											public function getSingleCircle(IFederatedUser $initiator): Circle {
//												public function searchCircle(Circle $circle, ?IFederatedUser $initiator = null): Circle {
//													public function getFederated(): array {

		return 0;
	}
}
