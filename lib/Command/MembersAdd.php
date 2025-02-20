<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Tools\Exceptions\RequestContentException;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\RequestResultNotJsonException;
use OCA\Circles\Tools\Exceptions\RequestResultSizeException;
use OCA\Circles\Tools\Exceptions\RequestServerException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\Request;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MembersAdd
 *
 * @package OCA\Circles\Command
 */
class MembersAdd extends Base {
	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MembersAdd constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		FederatedUserService $federatedUserService,
		MemberService $memberService,
		ConfigService $configService,
	) {
		parent::__construct();

		$this->federatedUserService = $federatedUserService;
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
			->addOption('initiator-type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0')
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
				$input->getOption('initiator'),
				Member::parseTypeString($input->getOption('initiator-type')),
				$circleId,
				false
			);

			$federatedUser = $this->federatedUserService->generateFederatedUser($userId, (int)$type);
			$outcome = $this->memberService->addMember($circleId, $federatedUser);
		} catch (FederatedItemException $e) {
			if ($input->getOption('status-code')) {
				throw new FederatedItemException(
					' [' . get_class($e) . ', ' . ((string)$e->getStatus()) . ']' . "\n" . $e->getMessage()
				);
			}

			throw $e;
		}

		if (strtolower($input->getOption('output')) === 'json') {
			$output->writeln(json_encode($outcome, JSON_PRETTY_PRINT));
		}

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
			$lookup = $this->configService->getGSLookup();
		} catch (GSStatusException $e) {
			return '';
		}

		$request = new NCRequest(ConfigService::GS_LOOKUP_USERS, Request::TYPE_GET);
		$this->configService->configureRequest($request);
		$request->basedOnUrl($lookup);
		$request->addParam('search', $search);

		try {
			$users = $this->retrieveJson($request);
		} catch (
			RequestContentException|
			RequestNetworkException|
			RequestResultSizeException|
			RequestServerException|
			RequestResultNotJsonException $e
		) {
			return '';
		}

		$result = [];
		foreach ($users as $user) {
			if (!array_key_exists('userid', $user)) {
				continue;
			}

			[, $host] = explode('@', $user['federationId']);
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
