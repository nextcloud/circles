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
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\GSUpstreamService;
use OCA\Circles\Service\MembersService;
use OCP\IL10N;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesSync
 *
 * @package OCA\Circles\Command
 */
class CirclesSync extends Base {


	/** @var IL10N */
	private $l10n;

	/** @var MembersService */
	private $membersService;

	/** @var CirclesService */
	private $circlesService;

	/** @var GSUpstreamService */
	private $gsUpstreamService;


	/**
	 * CirclesSync constructor.
	 *
	 * @param IL10N $l10n
	 * @param CirclesService $circlesService
	 * @param MembersService $membersService
	 * @param GSUpstreamService $gsUpstreamService
	 */
	public function __construct(
		IL10N $l10n, CirclesService $circlesService, MembersService $membersService,
		GSUpstreamService $gsUpstreamService
	) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->circlesService = $circlesService;
		$this->membersService = $membersService;
		$this->gsUpstreamService = $gsUpstreamService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:sync')
			 ->setDescription('sync circles and members');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circles = $this->circlesService->getCirclesToSync();
		foreach ($circles as $circle) {
			$this->membersService->updateCachedFromCircle($circle);
		}

		try {
			$this->gsUpstreamService->synchronize($circles);
		} catch (GSStatusException $e) {
		}

		return 0;
	}

}

