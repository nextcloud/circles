<?php

declare(strict_types=1);


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

use daita\MySmallPhpTools\Exceptions\RequestContentException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\RequestResultNotJsonException;
use daita\MySmallPhpTools\Exceptions\RequestResultSizeException;
use daita\MySmallPhpTools\Exceptions\RequestServerException;
use daita\MySmallPhpTools\Model\Nextcloud\nc22\NC22Request;
use daita\MySmallPhpTools\Model\Request;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCP\IL10N;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MembersCreate
 *
 * @package OCA\Circles\Command
 */
class MembersAdd extends Base {


	/** @var IL10N */
	private $l10n;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MembersCreate constructor.
	 *
	 * @param IL10N $l10n
	 * @param CircleRequest $circleRequest
	 * @param FederatedUserService $federatedUserService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IL10N $l10n, CircleRequest $circleRequest, FederatedUserService $federatedUserService,
		MemberService $memberService, ConfigService $configService
	) {
		parent::__construct();

		$this->l10n = $l10n;
		$this->federatedUserService = $federatedUserService;
		$this->circleRequest = $circleRequest;

		$this->memberService = $memberService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:add')
			 ->setDescription('Add a member to a Circle')
			 ->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			 ->addArgument('user', InputArgument::REQUIRED, 'username of the member')
			 ->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			 ->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception')
			 ->addOption('type', '', InputOption::VALUE_REQUIRED, 'type of the user', '0');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = $input->getArgument('circle_id');
		$userId = $input->getArgument('user');
		$type = $input->getOption('type');

		if ($type !== '0') {
			$type = Member::parseTypeString($type);
		}

		try {
			$this->federatedUserService->commandLineInitiator(
				$input->getOption('initiator'), $circleId, false
			);
			$federatedUser = $this->federatedUserService->generateFederatedUser($userId, (int)$type);
			$outcome = $this->memberService->addMember($circleId, $federatedUser);
		} catch (FederatedItemException $e) {
			if ($input->getOption('status-code')) {
				throw new FederatedItemException(
					' [' . get_class($e) . ', ' . $e->getStatus() . ']' . "\n" . $e->getMessage()
				);
			}

			throw $e;
		}

		$output->writeln(json_encode($outcome, JSON_PRETTY_PRINT));

		return 0;
	}


	/**
	 * @param string $search
	 * @param string $instance
	 *
	 * @return string
	 */
	private function findUserFromLookup(string $search, string &$instance = ''): string {
		$userId = '';

		/** @var string $lookup */
		try {
			$lookup = $this->configService->getGSStatus(ConfigService::GS_LOOKUP);
		} catch (GSStatusException $e) {
			return '';
		}

		$request = new NC22Request(ConfigService::GS_LOOKUP_USERS, Request::TYPE_GET);
		$this->configService->configureRequest($request);
		$request->basedOnUrl($lookup);
		$request->addParam('search', $search);

		try {
			$users = $this->retrieveJson($request);
		} catch (
		RequestContentException |
		RequestNetworkException |
		RequestResultSizeException |
		RequestServerException |
		RequestResultNotJsonException $e
		) {
			return '';
		}

		$result = [];
		foreach ($users as $user) {
			if (!array_key_exists('userid', $user)) {
				continue;
			}

			list(, $host) = explode('@', $user['federationId']);
			if (strtolower($user['userid']['value']) === strtolower($search)) {
				$userId = $user['userid']['value'];
				$instance = $host;
			}

			$result[] = $user['userid']['value'] . ' <info>@' . $host . '</info>';
		}

//		if ($userId === '') {
//			foreach($result as $item) {
//				$output->writeln($item);
//			}
//		}

		return $userId;
	}

}

