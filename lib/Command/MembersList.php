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

use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Exceptions\ItemNotFoundException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\UnknownTypeException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21TreeNode;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21ConsoleTree;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ModelManager;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\RemoteService;
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


	use TNC21ConsoleTree;


	/** @var ModelManager */
	private $modelManager;

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


	/**
	 * MembersList constructor.
	 *
	 * @param ModelManager $modelManager
	 * @param FederatedUserService $federatedUserService
	 * @param RemoteService $remoteService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		ModelManager $modelManager, FederatedUserService $federatedUserService, RemoteService $remoteService,
		CircleService $circleService, MemberService $memberService, ConfigService $configService
	) {
		parent::__construct();
		$this->modelManager = $modelManager;
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
			 ->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			 ->addOption('tree', '', InputOption::VALUE_NONE, 'display members as a tree')
			 ->addOption('json', '', InputOption::VALUE_NONE, 'returns result as JSON');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws UserTypeNotFoundException
	 * @throws InvalidItemException
	 * @throws MemberNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = $input->getArgument('circle_id');
		$instance = $input->getOption('instance');
		$initiator = $input->getOption('initiator');

		$tree = null;
		if ($input->getOption('tree')) {
			$this->federatedUserService->commandLineInitiator($initiator, $circleId, true);
			$circle = $this->circleService->getCircle($circleId);
			$output->writeln('<info>Name</info>: ' . $circle->getName());
			$owner = $circle->getOwner();
			$output->writeln('<info>Owner</info>: ' . $owner->getUserId() . '@' . $owner->getInstance());
			$type =
				implode(
					", ", $this->modelManager->getCircleTypes($circle, ModelManager::TYPES_LONG)
				);
			$output->writeln('<info>Config</info>: ' . $type);
			$output->writeln(' ');

			$tree = new NC21TreeNode(null, new SimpleDataStore(['circle' => $circle]));
		}

		$members = $this->getMembers($circleId, $instance, $initiator, $tree);

		if (!is_null($tree)) {
			$this->drawTree(
				$tree, [$this, 'displayLeaf'],
				[
					'height'       => 3,
					'node-spacing' => 1,
					'item-spacing' => 0,
				]
			);

			return 0;
		}

		if ($input->getOption('json')) {
			echo json_encode($members, JSON_PRETTY_PRINT) . "\n";

			return 0;
		}

		$output = new ConsoleOutput();
		$output = $output->section();

		$table = new Table($output);
		$table->setHeaders(['ID', 'Single ID', 'Type', 'Username', 'Instance', 'Level']);
		$table->render();

		$local = $this->configService->getLocalInstance();
		foreach ($members as $member) {
			$table->appendRow(
				[
					$member->getId(),
					$member->getSingleId(),
					Member::$DEF_TYPE[$member->getUserType()],
					$member->getUserId(),
					($member->getInstance() === $local) ? '' : $member->getInstance(),
					Member::$DEF_LEVEL[$member->getLevel()]
				]
			);
		}

		return 0;
	}


	/**
	 * @param string $circleId
	 * @param string $instance
	 * @param string $initiator
	 * @param NC21TreeNode|null $tree
	 * @param array $knownIds
	 *
	 * @return array
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
	 */
	private function getMembers(
		string $circleId,
		string $instance,
		string $initiator,
		?NC21TreeNode $tree,
		array $knownIds = []
	): array {
		if (in_array($circleId, $knownIds)) {
			return [];
		}
		$knownIds[] = $circleId;

		if ($instance !== '' && !$this->configService->isLocalInstance($instance)) {
			$data = [];
			if ($initiator) {
				$data['initiator'] = $this->federatedUserService->getFederatedUser($initiator);
			}

			$members = $this->remoteService->getMembersFromInstance($circleId, $instance, $data);
		} else {
			$this->federatedUserService->commandLineInitiator($initiator, $circleId, true);
			$members = $this->memberService->getMembers($circleId);
		}

		if (!is_null($tree)) {
			foreach ($members as $member) {
				if ($member->getUserType() === Member::TYPE_CIRCLE) {
					if ($instance !== '' && !$this->configService->isLocalInstance($instance)) {
						$data = [];
						if ($initiator) {
							$data['initiator'] = $this->federatedUserService->getFederatedUser($initiator);
						}

						$circle = $this->remoteService->getCircleFromInstance(
							$member->getSingleId(), $instance, $data
						);
					} else {
						$this->federatedUserService->commandLineInitiator(
							$initiator, $member->getSingleId(), true
						);
						$circle = $this->circleService->getCircle($member->getSingleId());
					}
					$node = new NC21TreeNode(
						$tree, new SimpleDataStore(
								 [
									 'circle'  => $circle,
									 'member'  => $member,
									 'cycling' => in_array($member->getSingleId(), $knownIds)
								 ]
							 )
					);

					$this->getMembers(
						$member->getSingleId(), $member->getInstance(), $initiator, $node, $knownIds
					);
				} else {
					new NC21TreeNode(
						$tree, new SimpleDataStore(
								 [
									 'member'  => $member,
									 'cycling' => in_array($member->getSingleId(), $knownIds)
								 ]
							 )
					);
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
				$circle = $data->gObj('circle', Circle::class);
			}

			if ($data->hasKey('member')) {
				/** @var Member $member */
				$member = $data->gObj('member', Member::class);

				if ($lineNumber === 1) {
					$line .= '<info>' . $member->getUserId() . '</info>';
					if (!$this->configService->isLocalInstance($member->getInstance())) {
						$line .= '@' . $member->getInstance();
					}
					$line .= ' (' . Member::$DEF_LEVEL[$member->getLevel()] . ')';

					if (!is_null($circle)) {
						$line .= ' <info>Name</info>: ' . $circle->getName();
					}
				}

				if ($lineNumber === 2 && !is_null($circle)) {
					$owner = $circle->getOwner();
					$line .= '<info>Owner</info>: ' . $owner->getUserId() . '@' . $owner->getInstance();
					$type =
						implode(
							", ", $this->modelManager->getCircleTypes($circle, ModelManager::TYPES_LONG)
						);
					$line .= ($type === '') ? '' : ' <info>Config</info>: ' . $type;
				}

			} else {
				if ($lineNumber === 1 && !is_null($circle)) {
					$line .= '<info>' . $circle->getId() . '</info>';
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

