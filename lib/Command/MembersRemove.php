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
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
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
 * Class MembersRemove
 *
 * @package OCA\Circles\Command
 */
class MembersRemove extends Base {
	/** @var MemberRequest */
	private $memberRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var MemberService */
	private $memberService;


	/**
	 * MembersRemove constructor.
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
		$this->setName('circles:members:remove')
			->setDescription('remove a member from a circle')
			->addArgument('member_id', InputArgument::REQUIRED, 'ID of the member from the Circle')
			->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			->addOption('initiator-type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidIdException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$memberId = $input->getArgument('member_id');

		$member = $this->memberRequest->getMemberById($memberId);

		$this->federatedUserService->commandLineInitiator(
			$input->getOption('initiator'),
			Member::parseTypeString($input->getOption('initiator-type')),
			$member->getCircleId(),
			false
		);

		$outcome = $this->memberService->removeMember($memberId);

		if (strtolower($input->getOption('output')) === 'json') {
			$output->writeln(json_encode($outcome, JSON_PRETTY_PRINT));
		}

		return 0;
	}
}
