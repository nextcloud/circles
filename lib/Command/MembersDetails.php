<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MembersDetails
 *
 * @package OCA\Circles\Command
 */
class MembersDetails extends Base {
	/** @var MemberRequest */
	private $memberRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var MemberService */
	private $memberService;


	/**
	 * MembersDetails constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param MemberService $memberService
	 */
	public function __construct(
		MemberRequest $memberRequest, FederatedUserService $federatedUserService, MemberService $memberService,
	) {
		parent::__construct();
		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->memberService = $memberService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:details')
			->setDescription('get details about a member by its ID')
			->addArgument('member_id', InputArgument::REQUIRED, 'ID of the member')
			->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			->addOption('initiator-type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0')
			->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 * @throws FederatedItemException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$memberId = $input->getArgument('member_id');

		$circleId = $this->memberRequest->getMemberById($memberId)->getCircleId();

		$this->federatedUserService->commandLineInitiator(
			$input->getOption('initiator'),
			Member::parseTypeString($input->getOption('initiator-type')),
			$circleId,
			false
		);

		$member = $this->memberService->getMemberById($memberId);

		$output->writeln(json_encode($member, JSON_PRETTY_PRINT));

		return 0;
	}
}
