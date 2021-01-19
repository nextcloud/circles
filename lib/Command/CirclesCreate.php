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
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeDisabledException;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Service\CirclesService;
use OCP\IL10N;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var CirclesService */
	private $circlesService;


	/**
	 * CirclesCreate constructor.
	 *
	 * @param IL10N $l10n
	 * @param IUserManager $userManager
	 * @param DeprecatedCirclesRequest $circlesRequest
	 * @param CirclesService $circlesService
	 */
	public function __construct(
		IL10N $l10n, IUserManager $userManager, DeprecatedCirclesRequest $circlesRequest, CirclesService $circlesService
	) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->circlesRequest = $circlesRequest;
		$this->circlesService = $circlesService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:create')
			 ->setDescription('create a new circle')
			 ->addArgument('owner', InputArgument::REQUIRED, 'owner of the circle')
			 ->addArgument('type', InputArgument::REQUIRED, 'type of the circle')
			 ->addArgument('name', InputArgument::REQUIRED, 'name of the circle');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleAlreadyExistsException
	 * @throws CircleTypeNotValidException
	 * @throws MemberAlreadyExistsException
	 * @throws NoUserException
	 * @throws CircleTypeDisabledException
	 * @throws CircleDoesNotExistException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$ownerId = $input->getArgument('owner');
		$type = $input->getArgument('type');
		$name = $input->getArgument('name');

		if ($this->userManager->get($ownerId) === null) {
			throw new NoUserException('user does not exist');
		}

		$types = [
			'personal' => DeprecatedCircle::CIRCLES_PERSONAL,
			'secret'   => DeprecatedCircle::CIRCLES_SECRET,
			'closed'   => DeprecatedCircle::CIRCLES_CLOSED,
			'public'   => DeprecatedCircle::CIRCLES_PUBLIC
		];

		if (!key_exists(strtolower($type), $types)) {
			throw new CircleTypeNotValidException('unknown type: ' . json_encode(array_keys($types)));
		}

		$type = $types[strtolower($type)];

		$circle = $this->circlesService->createCircle($type, $name, $ownerId);
		$circle = $this->circlesRequest->forceGetCircle($circle->getUniqueId());

		echo json_encode($circle, JSON_PRETTY_PRINT) . "\n";

		return 0;
	}

}

