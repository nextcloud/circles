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
use OC\User\NoUserException;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\RemoteEventException;
use OCA\Circles\Exceptions\ViewerNotFoundException;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\CurrentUserService;
use OCP\IL10N;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesCreate
 *
 * @package OCA\Circles\Command
 */
class CirclesCreate extends Base {


	/** @var IL10N */
	private $l10n;

	/** @var IUserManager */
	private $userManager;

	/** @var CurrentUserService */
	private $currentUserService;

	/** @var CircleService */
	private $circleService;


	/**
	 * CirclesCreate constructor.
	 *
	 * @param IL10N $l10n
	 * @param IUserManager $userManager
	 * @param CurrentUserService $currentUserService
	 * @param CircleService $circleService
	 */
	public function __construct(
		IL10N $l10n, IUserManager $userManager, CurrentUserService $currentUserService,
		CircleService $circleService
	) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->currentUserService = $currentUserService;
		$this->circleService = $circleService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:create')
			 ->setDescription('create a new circle')
			 ->addArgument('owner', InputArgument::REQUIRED, 'owner of the circle')
			 ->addArgument('name', InputArgument::REQUIRED, 'name of the circle')
			 ->addOption('type', '', InputOption::VALUE_REQUIRED, 'type of the circle (deprecated)', '');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws NoUserException
	 * @throws RemoteEventException
	 * @throws ViewerNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$ownerId = $input->getArgument('owner');
		$name = $input->getArgument('name');
		$type = $input->getOption('type');

		if ($this->userManager->get($ownerId) === null) {
			throw new NoUserException('user does not exist');
		}

		$owner = $this->currentUserService->createTemporaryViewer($ownerId);
		$circle = $this->circleService->create($name, $owner);

		echo json_encode($circle, JSON_PRETTY_PRINT) . "\n";

		return 0;
	}

}

