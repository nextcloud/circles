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

use Exception;
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
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\RemoteService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\ItemNotFoundException;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Exceptions\UnknownTypeException;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Model\TreeNode;
use OCA\Circles\Tools\Traits\TConsoleTree;
use OCA\Circles\Tools\Traits\TStringTools;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MembersList
 *
 * @package OCA\Circles\Command
 */
class MembersList extends Base {
	use TConsoleTree;
	use TStringTools;


	/** @var MemberRequest */
	private $memberRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var RemoteService */
	private $remoteService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;


	/** @var InputInterface */
	private $input;

	/** @var string */
	private $treeType = '';


	/**
	 * MembersList constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param RemoteService $remoteService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		MemberRequest $memberRequest, FederatedUserService $federatedUserService,
		RemoteService $remoteService, CircleService $circleService, MemberService $memberService,
		ConfigService $configService
	) {
		parent::__construct();

		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->remoteService = $remoteService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:list')
			 ->setDescription('listing Members from a Circle')
			 ->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			 ->addOption('instance', '', InputOption::VALUE_REQUIRED, 'Instance of the circle', '')
			 ->addOption('inherited', '', InputOption::VALUE_NONE, 'Display all inherited members')
			 ->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			 ->addOption('initiator-type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0')
			 ->addOption('display-name', '', InputOption::VALUE_NONE, 'display the displayName')
			 ->addOption('tree', '', InputOption::VALUE_OPTIONAL, 'display members as a tree', false);
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
	 * @throws InvalidIdException
	 * @throws InvalidItemException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 * @throws FederatedItemException
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->input = $input;
		$circleId = $input->getArgument('circle_id');
		$instance = $input->getOption('instance');
		$initiator = $input->getOption('initiator');
		$initiatorType = Member::parseTypeString($input->getOption('initiator-type'));
		$inherited = $input->getOption('inherited');

		$tree = null;
		if ($input->getOption('tree') !== false) {
			$this->treeType = (is_null($input->getOption('tree'))) ? 'all' : $input->getOption('tree');

			$this->federatedUserService->commandLineInitiator($initiator, $initiatorType, $circleId, true);
			$circle = $this->circleService->getCircle($circleId);

			$output->writeln('<info>Name</info>: ' . $circle->getName());
			$owner = $circle->getOwner();
			$output->writeln('<info>Owner</info>: ' . $owner->getUserId() . '@' . $owner->getInstance());
			$type = implode(", ", Circle::getCircleFlags($circle, Circle::FLAGS_LONG));
			$output->writeln('<info>Config</info>: ' . $type);
			$output->writeln(' ');

			$tree = new TreeNode(null, new SimpleDataStore(['circle' => $circle]));
			$inherited = false;
		}

		if ($inherited) {
			$this->federatedUserService->commandLineInitiator($initiator, $initiatorType, $circleId, true);
			$circle = $this->circleService->getCircle($circleId);
			$members = $circle->getInheritedMembers(true);
		} else {
			$members = $this->getMembers($circleId, $instance, $initiator, $initiatorType, $tree);
		}

		if (!is_null($tree)) {
			$this->drawTree(
				$tree, [$this, 'displayLeaf'],
				[
					'height' => 3,
					'node-spacing' => 1,
					'item-spacing' => 0,
				]
			);

			return 0;
		}

		if (strtolower($input->getOption('output')) === 'json') {
			$output->writeln(json_encode($members, JSON_PRETTY_PRINT));

			return 0;
		}

		$output = new ConsoleOutput();
		$output = $output->section();

		$table = new Table($output);
		$table->setHeaders(
			[
				'Circle Id', 'Circle Name', 'Member Id', 'Single Id', 'Type', 'Source',
				'Username', 'Level', 'Invited By'
			]
		);
		$table->render();

		foreach ($members as $member) {
			if ($member->getCircleId() === $circleId) {
				$level = $member->getLevel();
			} else {
				$level = $member->getInheritanceFrom()->getLevel();
			}

			$table->appendRow(
				[
					$member->getCircleId(),
					$member->getCircle()->getDisplayName(),
					$member->getId(),
					$member->getSingleId(),
					Member::$TYPE[$member->getUserType()],
					$member->hasBasedOn() ? Circle::$DEF_SOURCE[$member->getBasedOn()->getSource()] : '',
					$this->configService->displayFederatedUser(
						$member,
						$this->input->getOption('display-name')
					),
					($level > 0) ? Member::$DEF_LEVEL[$level] :
						'(' . strtolower($member->getStatus()) . ')',
					($member->hasInvitedBy()) ? $this->configService->displayFederatedUser(
						$member->getInvitedBy(),
						$this->input->getOption('display-name')
					) : 'Unknown'
				]
			);
		}

		return 0;
	}


	/**
	 * @param string $circleId
	 * @param string $instance
	 * @param string $initiator
	 * @param int $initiatorType
	 * @param TreeNode|null $tree
	 * @param array $knownIds
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidIdException
	 * @throws InvalidItemException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	private function getMembers(
		string $circleId,
		string $instance,
		string $initiator,
		int $initiatorType,
		?TreeNode $tree,
		array $knownIds = []
	): array {
		if (in_array($circleId, $knownIds)) {
			return [];
		}
		$knownIds[] = $circleId;

		if (!$this->configService->isLocalInstance($instance)) {
			$data = [];
			if ($initiator) {
				$data['initiator'] = $this->federatedUserService->getFederatedUser(
					$initiator,
					Member::TYPE_USER
				);
			}

			try {
				$members = $this->remoteService->getMembersFromInstance($circleId, $instance, $data);
			} catch (RemoteInstanceException $e) {
				return [];
			}
		} else {
			$this->federatedUserService->commandLineInitiator($initiator, $initiatorType, $circleId, true);
			$members = $this->memberService->getMembers($circleId);
		}

		if (!is_null($tree)) {
			foreach ($members as $member) {
				if ($member->getUserType() === Member::TYPE_CIRCLE) {
					if (!$this->configService->isLocalInstance($member->getInstance())) {
						$data = [];
						if ($initiator) {
							$data['initiator'] = $this->federatedUserService->getFederatedUser(
								$initiator,
								Member::TYPE_USER
							);
						}

						$circle = null;
						try {
							$circle = $this->remoteService->getCircleFromInstance(
								$member->getSingleId(), $member->getInstance(), $data
							);
						} catch (CircleNotFoundException | RemoteInstanceException $e) {
						}
					} else {
						$this->federatedUserService->commandLineInitiator(
							$initiator,
							$initiatorType,
							$member->getSingleId(),
							true
						);
						$circle = $this->circleService->getCircle($member->getSingleId());
					}
					$node = new TreeNode(
						$tree, new SimpleDataStore(
							[
								'circle' => $circle,
								'member' => $member,
								'cycling' => in_array($member->getSingleId(), $knownIds),
							]
						)
					);

					$this->getMembers(
						$member->getSingleId(),
						$member->getInstance(),
						$initiator,
						$initiatorType,
						$node,
						$knownIds
					);
				} else {
					if ($this->treeType !== 'circles-only') {
						new TreeNode(
							$tree, new SimpleDataStore(
								[
									'member' => $member,
									'cycling' => in_array($member->getSingleId(), $knownIds)
								]
							)
						);
					}
				}
			}
		}

		return $members;
	}


	/**
	 * @param SimpleDataStore $data
	 * @param int $lineNumber
	 *
	 * @return string
	 * @throws OwnerNotFoundException
	 */
	public function displayLeaf(SimpleDataStore $data, int $lineNumber): string {
		if ($lineNumber === 3) {
			return ($data->gBool('cycling')) ? '<comment>(loop detected)</comment>' : '';
		}

		try {
			$line = '';
			$circle = null;
			if ($data->hasKey('circle')) {
				/** @var Circle $circle */
				try {
					$circle = $data->gObj('circle', Circle::class);
				} catch (Exception $e) {
				}
			}

			if ($data->hasKey('member')) {
				/** @var Member $member */
				$member = $data->gObj('member', Member::class);

				if ($lineNumber === 1) {
					$line .= '<info>' . $member->getSingleId() . '</info>';
					if (!$this->configService->isLocalInstance($member->getInstance())) {
						$line .= '@' . $member->getInstance();
					}
					$line .= ' (' . Member::$DEF_LEVEL[$member->getLevel()] . ')';

					$line .= ' <info>MemberId</info>: ' . $member->getId();
					$line .= ' <info>Name</info>: ' . $this->configService->displayFederatedUser(
						$member,
						$this->input->getOption('display-name')
					);
					if ($member->hasBasedOn()) {
						$line .= ' <info>Source</info>: '
								 . Circle::$DEF_SOURCE[$member->getBasedOn()->getSource()];
					} else {
						$line .= ' <info>Type</info>: ' . Member::$TYPE[$member->getUserType()];
					}
				}

				if ($lineNumber === 2) {
					if (is_null($circle)) {
						if ($member->getUserType() === Member::TYPE_CIRCLE) {
							$line .= '<comment>(out of bounds)</comment>';
						}

						return $line;
					}
					$owner = $circle->getOwner();
					$line .= '<info>Owner</info>: ' . $owner->getUserId() . '@' . $owner->getInstance();
					$type = implode(", ", Circle::getCircleFlags($circle, Circle::FLAGS_LONG));
					$line .= ($type === '') ? '' : ' <info>Config</info>: ' . $type;
				}
			} else {
				if ($lineNumber === 1 && !is_null($circle)) {
					$line .= '<info>' . $circle->getSingleId() . '</info>';
					if (!$this->configService->isLocalInstance($circle->getInstance())) {
						$line .= '@' . $circle->getInstance();
					}
				}
			}

			return $line;
		} catch (InvalidItemException | ItemNotFoundException | UnknownTypeException $e) {
		}

		return '';
	}
}
