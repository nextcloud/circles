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

use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\ItemNotFoundException;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TStringTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\CirclesManager;
use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\ConfigService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class CirclesTest
 *
 * @package OCA\Circles\Command
 */
class CirclesTest extends Base {
	use TArrayTools;
	use TStringTools;
	use TDeserialize;


	public static $INSTANCES = [
		'global-scale-1',
		'global-scale-2',
		'global-scale-3',
		'passive',
		'external',
		'trusted'
	];


	public static $TEST_CIRCLES = [
		'test_001'
	];


	/** @var CoreRequestBuilder */
	private $coreQueryBuilder;

	/** @var ConfigService */
	private $configService;


	/** @var InputInterface */
	private $input;

	/** @var OutputInterface */
	private $output;

	/** @var array */
	private $config = [];

	/** @var string */
	private $local = '';

	/** @var bool */
	private $pOn = false;

	/** @var array */
	private $circles = [];

	/** @var array */
	private $federatedUsers = [];


	/**
	 * CirclesTest constructor.
	 *
	 * @param CoreRequestBuilder $coreRequestBuilder
	 * @param ConfigService $configService
	 */
	public function __construct(CoreRequestBuilder $coreRequestBuilder, ConfigService $configService) {
		parent::__construct();

		$this->coreQueryBuilder = $coreRequestBuilder;
		$this->configService = $configService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:test')
			 ->setDescription('testing some features')
			 ->addArgument('deprecated', InputArgument::OPTIONAL, '')
			 ->addOption(
			 	'are-you-aware-this-will-delete-all-my-data', '', InputOption::VALUE_REQUIRED,
			 	'Well, are you ?', ''
			 )
			 ->addOption('skip-init', '', InputOption::VALUE_NONE, 'Bypass Initialisation')
			 ->addOption('skip-setup', '', InputOption::VALUE_NONE, 'Bypass Circles Setup')
			 ->addOption('only-setup', '', InputOption::VALUE_NONE, 'Stop after Circles Setup, pre-Sync');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->input = $input;
		$this->output = $output;


		// loading CirclesManager
		/** @var CirclesManager $circlesManager */
		$circlesManager = \OC::$server->get(CirclesManager::class);
//		$circlesManager->startSuperSession();


		$federatedUser = $circlesManager->getFederatedUser('test1', Member::TYPE_USER);
		$circlesManager->startSession($federatedUser);

		$probe = new CircleProbe();
		$probe->mustBeMember();

		//$probe->includePersonalCircles();

		// get hidden circles (to get Groups)
//		$probe->includeHiddenCircles();

//		$probe->includePersonalCircles();
//		$probe->addOptionBool('filterPersonalCircles', true);
//		$probe->mustBeMember();

		$circles = $circlesManager->getCircles($probe);


		// display result
		$output = new ConsoleOutput();
		$table = new Table($output->section());
		$table->setHeaders(['SingleId', 'Circle Name', 'Type']);
		$table->render();

		foreach ($circles as $entry) {
			$table->appendRow(
				[
					$entry->getSingleId(),
					$entry->getDisplayName(),
					Circle::$DEF_SOURCE[$entry->getSource()]
				]
			);
		}


		return 0;
		$federatedUser = $circlesManager->getFederatedUser('test1', Member::TYPE_USER);
		$circlesManager->startSession($federatedUser);
		$circle = $circlesManager->getCircle($circleId);
		$member = $circle->getInitiator();

		// get the singleId of a Group
		$federatedUser = $circlesManager->getFederatedUser('testGroup', Member::TYPE_GROUP);
		echo 'singleId: ' . $federatedUser->getSingleId() . "\n";

//		$federatedUser->getMemberships();


		// get Circles available to test1
		$federatedUser = $circlesManager->getFederatedUser('test1', Member::TYPE_USER);
		$circlesManager->startSession($federatedUser);
		$circles = $circlesManager->getCircles(
			null,
			null,
			[
				'mustBeMember' => true,
				'include' => Circle::CFG_SYSTEM | Circle::CFG_HIDDEN
			]
		);


		$output = new ConsoleOutput();
		$table = new Table($output->section());
		$table->setHeaders(['SingleId', 'Circle Name', 'Type']);
		$table->render();

		foreach ($circles as $entry) {
			$table->appendRow(
				[
					$entry->getSingleId(),
					$entry->getDisplayName(),
					Circle::$DEF_SOURCE[$entry->getSource()]
				]
			);
		}


		// exit
		return 0;


		$members = array_map(
			function (Member $member): string {
				return $member->getUserId() . ' ' . $member->getSingleId() . '   - ' . $member->getUserType();
			}, $circle->getInheritedMembers()
		);

		echo json_encode($members, JSON_PRETTY_PRINT);
//		$circlesManager->startSession($federatedUser);

//		$circlesManager->destroyCircle('XXHHLGdwQTxENgU');
//		$circles = array_map(function(Circle $circle): string {
//			return $circle->getDisplayName();
//		}, $circlesManager->getCircles());
		return 0;

		$circlesManager->stopSession();


		//echo json_encode($circles, JSON_PRETTY_PRINT);


//		$circle = $circlesManager->createCircle('This is a test2');
//
//
//		$federatedUser2 = $circlesManager->getFederatedUser('test3', Member::TYPE_USER);
//		$circlesManager->startSession($federatedUser);
//
		////		$info = $circlesManager->getCircle($circle->getSingleId());
		////echo json_encode($info);
//
//		$circles = $circlesManager->getCircles();
//		foreach ($circles as $circle) {
//			echo $circle->getDisplayName() . "\n";
		////			$circlesManager->startSession($federatedUser2);
		////			$circlesManager->destroyCircle($circle->getSingleId());
//		}
//
//
//		// testing getCircle()
//
//
//		return 0;


		// testing queryHelper;

		$circlesQueryHelper = $circlesManager->getQueryHelper();

		$qb = $circlesQueryHelper->getQueryBuilder();
		$qb->select(
			'test.id',
			'test.shared_to',
			'test.data'
		)
		   ->from('circles_test', 'test');


		/** @var FederatedUser $federatedUser */
		$federatedUser = $circlesManager->getFederatedUser('test1', Member::TYPE_USER);

//		$circlesQueryHelper->limitToInheritedMembers('test', 'shared_to', $federatedUser, true);
//		$circlesQueryHelper->addCircleDetails('test', 'shared_to');
//
//		$items = [];
//		$cursor = $qb->execute();
//		while ($row = $cursor->fetch()) {
//			try {
//				$items[] = [
//					'id'     => $row['id'],
//					'data'   => $row['data'],
//					'circle' => $circlesQueryHelper->extractCircle($row)
//				];
//			} catch (Exception $e) {
//			}
//		}
//		$cursor->closeCursor();
//
//		echo json_encode($items, JSON_PRETTY_PRINT);
//
//		return 0;


		if ($input->getOption('are-you-aware-this-will-delete-all-my-data') === 'yes-i-am') {
			try {
				$this->testCirclesApp();
			} catch (Exception $e) {
				if ($this->pOn) {
					$message = ($e->getMessage() !== '') ? $e->getMessage() : get_class($e);
					$this->output->writeln('<error>' . $message . '</error>');
				} else {
					throw $e;
				}
			}

			return 0;
		}

		$output->writeln('');
		$output->writeln(
			'<error>Since Nextcloud 22, this command have changed, please read the message below:</error>'
		);
		$output->writeln('<error>This new command is to test the integrity of the Circles App.</error>');
		$output->writeln(
			'<error>Running this command will REMOVE all your current configuration and all your current Circles.</error>'
		);
		$output->writeln('<error>There is a huge probability that you do not want to do that.</error>');
		$output->writeln('');
		$output->writeln(
			'<error>The old testing command you might looking for have moved to "./occ circles:check"</error>'
		);
		$output->writeln('');

		return 0;
	}


	/**
	 * @throws CircleNotFoundException
	 * @throws InvalidItemException
	 * @throws ItemNotFoundException
	 */
	private function testCirclesApp() {
		$this->t('Boot up');
		$this->loadConfiguration();

		if (!$this->input->getOption('skip-setup')) {
			if (!$this->input->getOption('skip-init')) {
				$this->t('Initialisation Nextcloud');
				$this->initEnvironment();
			}

			$this->t('Initialisation Circles App');
			$this->reloadCirclesApp();
			$this->configureCirclesApp();
			$this->confirmVersion();
			$this->confirmEmptyCircles();

			if ($this->input->getOption('only-setup')) {
				return;
			}

			$this->syncCircles();

			$this->t('Fresh installation status');
			$this->statusFreshInstances();
			$this->createRemoteLink();
		}

		$this->t('Building Local Database');
		$this->buildingLocalDatabase();

		$this->t('Testing Basic Circle Creation');
		$this->circleCreation001();

		$this->t('Adding local users and moderators');
		$this->addLocalMemberByUserId();
//		$this->addLocalMemberBySingleId();
//		$this->addLocalMemberUsingMember();
//		$this->levelLocalMemberToModerator();
//		$this->addRemoteMemberUsingModerator();
//		$this->addRemoteMemberUsingRemoteMember();
//		$this->levelRemoteMemberToAdmin();
//		$this->addRemoteMemberUsingRemoteAdmin();
//
	}


	/**
	 * @throws ItemNotFoundException
	 */
	private function loadConfiguration() {
		$this->p('Loading configuration');
		$configuration = file_get_contents(__DIR__ . '/../../testConfiguration.json');
		$this->config = json_decode($configuration, true);
		$this->r(true, 'testConfiguration.json');

		$this->p('Checking configuration');
		foreach (self::$INSTANCES as $instance) {
			$cloudId = $this->getConfig($instance, 'config.frontal_cloud_id');
			if ($this->configService->isLocalInstance($cloudId)) {
				$this->local = $instance;
			}
		}
		$this->r();

		$this->p('Checking local');
		if ($this->local === '') {
			throw new ItemNotFoundException('local not defined');
		}
		$this->r(true, $this->local);
	}


	/**
	 * @throws ItemNotFoundException
	 */
	private function initEnvironment() {
		$this->p('Disabling Password Policy App:');
		foreach ($this->getInstances() as $instanceId) {
			$this->occ($instanceId, 'app:disable password_policy', false, false);
			$this->pm($instanceId);
		}
		$this->r();

		foreach ($this->getInstances() as $instance) {
			$this->p('Creating users on ' . $instance);
			foreach ($this->getConfigArray($instance, 'users') as $userId) {
				$this->pm($userId);
				$this->occ(
					$instance, 'user:add --password-from-env ' . $userId, false, false,
					['OC_PASS' => 'testtest']
				);
			}
			$this->r();

			foreach ($this->getConfigArray($instance, 'groups') as $groupId => $users) {
				$this->p('Creating group <info>' . $groupId . '</info> on <info>' . $instance . '</info>');
				$this->occ($instance, 'group:add ' . $groupId, false, false);
				foreach ($users as $userId) {
					$this->pm($userId);
					$this->occ($instance, 'group:adduser ' . $groupId . ' ' . $userId, true, false);
				}
				$this->r();
			}
		}
	}


	/**
	 * @throws ItemNotFoundException
	 */
	private function reloadCirclesApp() {
		$this->p('Reload Circles App');
		foreach ($this->getInstances(false) as $instance) {
			$this->pm($instance);
			$this->occ($instance, 'circles:clean --uninstall', false, false);
			$this->occ($instance, 'app:enable circles', true, false);
		}
		$this->r();

		$this->p('Empty Circles database on local');
		$this->coreQueryBuilder->cleanDatabase();
		$this->r();
	}


	/**
	 * @throws ItemNotFoundException
	 */
	private function configureCirclesApp() {
		$this->p('Configure Circles App');
		foreach ($this->getInstances(true) as $instance) {
			$this->pm($instance);
			foreach ($this->getConfigArray($instance, 'config') as $k => $v) {
				$this->occ($instance, 'config:app:set --value ' . $v . ' circles ' . $k, true, false);
			}
		}
		$this->r();
	}


	/**
	 * @throws ItemNotFoundException
	 * @throws Exception
	 */
	private function confirmVersion() {
		$version = $this->configService->getAppValue('installed_version');
		$this->p('Confirming version <info>' . $version . '</info>');
		foreach ($this->getInstances(false) as $instance) {
			$this->pm($instance);
			$capabilities = $this->occ($instance, 'circles:check --capabilities');
			$v = $this->get('version', $capabilities);
			if ($v !== $version) {
				throw new Exception($v);
			}
		}
		$this->r();
	}


	/**
	 * @throws ItemNotFoundException
	 * @throws Exception
	 */
	private function confirmEmptyCircles() {
		$this->p('Confirming empty database');
		foreach ($this->getInstances() as $instance) {
			$this->pm($instance);
			$result = $this->occ($instance, 'circles:manage:list --all');
			if (!is_array($result) || !empty($result)) {
				throw new Exception('no');
			}
		}
		$this->r();
	}


	/**
	 * @throws ItemNotFoundException
	 * @throws Exception
	 */
	private function syncCircles() {
		$this->p('Running Circles Sync');
		foreach ($this->getInstances() as $instance) {
			$this->pm($instance);
			$this->occ($instance, 'circles:sync');
		}
		$this->r();
	}


	/**
	 * @throws CircleNotFoundException
	 * @throws InvalidItemException
	 * @throws ItemNotFoundException
	 */
	private function statusFreshInstances() {
		foreach ($this->getInstances() as $instanceId) {
			$this->p('Circles on ' . $instanceId);
			$result = $this->occ($instanceId, 'circles:manage:list --all');
			$expectedSize = sizeof($this->getConfigArray($instanceId, 'groups'))
							+ sizeof($this->getConfigArray($instanceId, 'users'))
							+ 1;
			$this->r((sizeof($result) === $expectedSize), sizeof($result) . ' circles');

			$membersList = $groupsList = [];
			foreach ($result as $item) {
				/** @var Circle $circle */
				$circle = $this->deserialize($item, Circle::class);

				if ($circle->isConfig(Circle::CFG_SINGLE)) {
					$membersList[] = $circle;
				}

				if ($circle->getSource() === Member::TYPE_GROUP) {
					$groupsList[] = $circle;
				}
			}

			$instance = $this->getConfig($instanceId, 'config.frontal_cloud_id');

			foreach ($this->getConfigArray($instanceId, 'users') as $userId) {
				$this->p('Checking Single Circle for <comment>' . $userId . '@' . $instance . '</comment>');
				$circle = $this->getSingleCircleForMember($membersList, $userId, $instance);

				$compareToOwnerBasedOn = new Circle();
				$compareToOwnerBasedOn->setConfig(Circle::CFG_SINGLE)
									  ->setName('user:' . $userId . ':{CIRCLEID}')
									  ->setDisplayName($userId);

				$compareToOwner = new Member();
				$compareToOwner->setUserId($userId)
							   ->setUserType(Member::TYPE_USER)
							   ->setInstance($instance)
							   ->setDisplayName($userId)
							   ->setId('{CIRCLEID}')
							   ->setCircleId('{CIRCLEID}')
							   ->setSingleId('{CIRCLEID}')
							   ->setStatus(Member::STATUS_MEMBER)
							   ->setLevel(Member::LEVEL_OWNER)
							   ->setBasedOn($compareToOwnerBasedOn);

				$compareTo = new Circle();
				$compareTo->setOwner($compareToOwner)
						  ->setConfig(Circle::CFG_SINGLE)
						  ->setName('user:' . $userId . ':{CIRCLEID}')
						  ->setDisplayName($userId);

				$this->confirmCircleData($circle, $compareTo);
				$this->r(true, $circle->getSingleId());
			}

			$this->p('Checking Single Circle for <comment>Circles App</comment>');
			$circle = $this->getSingleCircleForMember($membersList, 'circles', $instance);

			$compareToOwnerBasedOn = new Circle();
			$compareToOwnerBasedOn->setConfig(Circle::CFG_SINGLE | Circle::CFG_ROOT)
								  ->setName('app:circles:{CIRCLEID}')
								  ->setDisplayName('circles');

			$compareToOwner = new Member();
			$compareToOwner->setUserId(Application::APP_ID)
						   ->setUserType(Member::TYPE_APP)
						   ->setInstance($instance)
						   ->setDisplayName(Application::APP_ID)
						   ->setId('{CIRCLEID}')
						   ->setCircleId('{CIRCLEID}')
						   ->setSingleId('{CIRCLEID}')
						   ->setStatus(Member::STATUS_MEMBER)
						   ->setLevel(Member::LEVEL_OWNER)
						   ->setBasedOn($compareToOwnerBasedOn);

			$compareTo = new Circle();
			$compareTo->setOwner($compareToOwner)
					  ->setConfig(Circle::CFG_SINGLE | Circle::CFG_ROOT)
					  ->setName('app:circles:{CIRCLEID}')
					  ->setDisplayName('circles');

			$this->confirmCircleData($circle, $compareTo);
			$this->r(true, $circle->getSingleId());

			foreach ($this->getConfigArray($instanceId, 'groups') as $groupId => $members) {
				$this->p('Checking Circle for <comment>' . $groupId . '@' . $instance . '</comment>');
				$circle = $this->getCircleFromList($groupsList, 'group:' . $groupId);

				$appCircle = $this->getSingleCircleForMember($membersList, 'circles', $instance);
				$appOwner = $appCircle->getOwner();

				$compareToOwnerBasedOn = new Circle();
				$compareToOwnerBasedOn->setConfig(Circle::CFG_SINGLE | Circle::CFG_ROOT)
									  ->setName($appCircle->getName())
									  ->setDisplayName($appCircle->getDisplayName());

				$compareToOwner = new Member();
				$compareToOwner->setUserId($appOwner->getUserId())
							   ->setUserType($appOwner->getUserType())
							   ->setInstance($appOwner->getInstance())
							   ->setDisplayName($appOwner->getDisplayName())
							   ->setCircleId('{CIRCLEID}')
							   ->setSingleId($appOwner->getSingleId())
							   ->setStatus($appOwner->getStatus())
							   ->setLevel($appOwner->getLevel())
							   ->setBasedOn($compareToOwnerBasedOn);

				$compareTo = new Circle();
				$compareTo->setOwner($compareToOwner)
						  ->setConfig(Circle::CFG_SYSTEM | Circle::CFG_NO_OWNER | Circle::CFG_HIDDEN)
						  ->setName('group:' . $groupId)
						  ->setDisplayName($groupId);

				$this->confirmCircleData($circle, $compareTo);
				$this->r(true, $circle->getSingleId());
			}

			$this->output->writeln('');
		}
	}


	/**
	 *
	 */
	private function createRemoteLink() {
		foreach ($this->getInstances() as $instanceId) {
			$this->p('Init remote link from ' . $instanceId);
			$links = $this->getConfigArray($instanceId, 'remote');
			foreach ($links as $link => $type) {
				$remote = $this->getConfig($link, 'config.frontal_cloud_id');
				$this->pm($remote . '(' . $type . ')');
				$this->occ($instanceId, 'circles:remote ' . $remote . ' --type ' . $type . ' --yes');
			}
			$this->r();
		}
	}


	/**
	 * @throws InvalidItemException
	 * @throws ItemNotFoundException
	 */
	private function buildingLocalDatabase() {
		$this->circles = $this->federatedUsers = [];
		foreach ($this->getInstances() as $instanceId) {
			$this->p('Retrieving Circles from ' . $instanceId);
			$circles = $this->occ($instanceId, 'circles:manage:list --all');
			foreach ($circles as $item) {
				/** @var Circle $circle */
				$circle = $this->deserialize($item, Circle::class);
				if ($circle->isConfig(Circle::CFG_SINGLE)) {
					$pos = strrpos($circle->getName(), ':');
					if (!$pos) {
						throw new InvalidItemException('could not parse circle.name');
					}

					$owner = new FederatedUser();
					$owner->importFromIFederatedUser($circle->getOwner());
					$this->federatedUsers[$instanceId][$owner->getUserId()] = $owner;
					$this->circles[$instanceId][substr($circle->getName(), 0, $pos)] = $circle;
				} else {
					$this->circles[$instanceId][$circle->getName()] = $circle;
				}
			}
			$this->r();
		}
	}


	/**
	 * @throws InvalidItemException
	 * @throws ItemNotFoundException
	 */
	private function circleCreation001() {
		$this->p('Creating basic circle');

		$localInstanceId = 'global-scale-1';
		$name = self::$TEST_CIRCLES[0];
		$owner = $this->getInstanceUsers($localInstanceId)[1];
		$dataCreatedCircle001 =
			$this->occ($localInstanceId, 'circles:manage:create --type user ' . $owner . ' ' . $name);
		/** @var Circle $createdCircle */
		$createdCircle = $this->deserialize($dataCreatedCircle001, Circle::class);
		$this->circles[$localInstanceId][$createdCircle->getName()] = $createdCircle;
		$this->r(true, $createdCircle->getSingleId());
		;

		$this->p('Comparing data returned at creation');
		if ($createdCircle->getSingleId() === '' || $createdCircle->getOwner()->getId() === '') {
			throw new InvalidItemException('empty id or owner.member_id');
		}

		$knownOwner = new Member();
		$knownOwner->importFromIFederatedUser($this->federatedUsers[$localInstanceId][$owner]);
		$knownOwner->setCircleId($createdCircle->getSingleId());
		$knownOwner->setLevel(Member::LEVEL_OWNER);
		$knownOwner->setStatus(Member::STATUS_MEMBER);
		$knownOwner->setId($createdCircle->getOwner()->getId());

		$compareTo = new Circle();
		$compareTo->setOwner($knownOwner)
				  ->setSingleId($createdCircle->getSingleId())
				  ->setInitiator($knownOwner)
				  ->setConfig(Circle::CFG_CIRCLE)
				  ->setName($name)
				  ->setDisplayName($name);
		echo json_encode($createdCircle, JSON_PRETTY_PRINT);
		$this->confirmCircleData($createdCircle, $compareTo, 'circle', true);
		$this->r();


		$this->p('Comparing local stored data');
		$dataCircle = $this->occ($localInstanceId, 'circle:manage:details ' . $createdCircle->getSingleId());

		/** @var Circle $tmpCircle */
		$tmpCircle = $this->deserialize($dataCircle, Circle::class);
		$this->confirmCircleData($tmpCircle, $createdCircle);
		$this->r(true, $tmpCircle->getSingleId());

		$links = $this->getConfigArray('global-scale-1', 'remote');
		foreach ($this->getInstances(false) as $instanceId) {
			$this->p('Comparing data stored on ' . $instanceId);
			$dataCircle =
				$this->occ($instanceId, 'circle:manage:details ' . $createdCircle->getSingleId(), false);

			if ($instanceId === $localInstanceId || $links[$instanceId] === 'GlobalScale') {
				/** @var Circle $tmpCircle */
				$tmpCircle = $this->deserialize($dataCircle, Circle::class);
				// we reset some data that should not be available on remote instance
				$createdCircle->setInitiator(null)
							  ->getOwner()->setBasedOn(null);
				$this->confirmCircleData($tmpCircle, $createdCircle);
				$this->r(true, $tmpCircle->getSingleId());
			} else {
				if (is_null($dataCircle)) {
					$this->r(true, 'empty');
				} else {
					$this->r(false, 'should be empty');
				}
			}
		}
	}


	/**
	 * @throws InvalidItemException
	 * @throws ItemNotFoundException
	 * @throws CircleNotFoundException
	 */
	private function addLocalMemberByUserId() {
		$this->p('Adding local user as member, based on userId');

		$instanceId = 'global-scale-1';
		$circleName = self::$TEST_CIRCLES[0];
		$member = $this->getInstanceUsers($instanceId)[2];

		$addedMember = $this->processMemberAdd($instanceId, $circleName, $member, 'user');
		$this->r(true, $addedMember->getId());
		;

		// check test2
	}


	/**
	 * @throws CircleNotFoundException
	 * @throws InvalidItemException
	 * @throws ItemNotFoundException
	 */
	private function addLocalMemberBySingleId() {
		$this->p('Adding local user as member, based on singleId');

		$localInstanceId = 'global-scale-1';
		$name = self::$TEST_CIRCLES[0];
		$circle = $this->getCircleByName($localInstanceId, $name);
		$userId = $this->getInstanceUsers($localInstanceId)[6];
		$userCircle = $this->getCircleByName($localInstanceId, 'user:' . $userId);
		$user = $userCircle->getOwner();
		$dataAddedMember =
			$this->occ(
				$localInstanceId, 'circles:members:add ' . $circle->getSingleId() . ' ' . $user->getSingleId()
			);
		/** @var Member $addedMember */
		$addedMember = $this->deserialize($dataAddedMember, Member::class);
		$this->r(true, $addedMember->getId());
		;

		// check test6
	}


	private function addLocalMemberUsingMember() {
		$this->p('Adding local member using local Member');
		$localInstanceId = 'global-scale-1';
		$initiator = $this->getInstanceUsers($localInstanceId)[6];
		$member = $this->getInstanceUsers($localInstanceId)[6];
		$circleName = self::$TEST_CIRCLES[0];

		$circle = $this->getCircleByName($localInstanceId, $circleName);
		$userId = $this->getInstanceUsers($localInstanceId)[6];
		$userCircle = $this->getCircleByName($localInstanceId, 'user:' . $userId);
		$user = $userCircle->getOwner();
		$dataAddedMember =
			$this->occ(
				$localInstanceId, 'circles:members:add ' . $circle->getSingleId() . ' ' . $user->getSingleId()
			);
		/** @var Member $addedMember */
		$addedMember = $this->deserialize($dataAddedMember, Member::class);
		$this->r(true, $addedMember->getId());
		;
	}

	private function levelLocalMemberToModerator() {
		$this->p('Changing local level to Moderator');
	}

	private function addLocalMemberUsingModerator() {
		$this->p('Adding local member using local Moderator');
	}

	private function levelLocalMemberToModeratorUsingModerator() {
		// fail
		$this->p('Changing local level to moderator using local Moderator');
	}

	private function addRemoteMemberUsingModerator() {
		$this->p('Adding remote user using local Moderator');
	}

	private function addLocalMemberUsingRemoteMember() {
		// fail
		$this->p('Adding local user using remote Member');
	}

	private function addRemoteMemberUsingRemoteMember() {
		// fail
		$this->p('Adding remote user using remote Member');
	}

	private function levelRemoteMemberToAdmin() {
		$this->p('Changing remote level to Admin');
	}

	private function addLocalMemberUsingRemoteAdmin() {
		$this->p('Adding remote member using remote Admin');
	}

	private function addRemoteMemberUsingRemoteAdmin() {
		$this->p('Adding remote member using remote Admin');
	}

	private function levelRemoteMemberToModeratorUsingRemoteAdmin() {
	}

	private function levelRemoteMemberToAdminUsingRemoteAdmin() {
		// fail
	}

	private function verifyMemberList001() {
	}

	private function removeLocalMemberUsingRemoteMember() {
		// fail
	}

	private function removeLocalMemberUsingRemoteAdmin() {
	}


	private function removeRemoteMemberUsingRemoteMember() {
		// fail
	}

	private function removeRemoteMemberUsingRemoteAdmin() {
	}

	/**
	 * @param Circle $circle
	 * @param Circle $compareTo
	 * @param string $prefix
	 * @param bool $versa
	 * @param array $params
	 *
	 * @throws Exception
	 */
	private function confirmCircleData(
		Circle $circle,
		Circle $compareTo,
		string $prefix = 'circle',
		bool $versa = false,
		array $params = []
	) {
		if (empty($params)) {
			$params = [
				'CIRCLEID' => $circle->getSingleId()
			];
		}

		$this->compare($compareTo->getSingleId(), $circle->getSingleId(), $prefix . '.id', $params);
		$this->compare($compareTo->getName(), $circle->getName(), $prefix . '.name', $params);
		$this->compare(
			$compareTo->getDisplayName(), $circle->getDisplayName(), $prefix . '.displayName', $params
		);
		$this->compareInt($compareTo->getConfig(), $circle->getConfig(), $prefix . '.config', true);
		$this->compareInt($compareTo->getSource(), $circle->getSource(), $prefix . '.source');

		if ($compareTo->hasOwner()) {
			$compareToOwner = $compareTo->getOwner();
			if ($compareToOwner !== null) {
				$owner = $circle->getOwner();
				if ($owner === null) {
					throw new Exception('empty owner');
				}
				if ($owner->getCircleId() !== $circle->getSingleId()) {
					throw new Exception($prefix . '.owner.circleId is different than ' . $prefix . '.id');
				}
				$this->confirmMemberData($owner, $compareToOwner, 'owner', false, $params);
			}
		}
		if ($compareTo->hasInitiator()) {
			$compareToInitiator = $compareTo->getInitiator();
			if ($compareToInitiator !== null) {
				if (!$circle->hasInitiator()) {
					throw new Exception('empty initiator');
				}
				$initiator = $circle->getInitiator();
				if ($initiator->getCircleId() !== $circle->getSingleId()) {
					throw new Exception($prefix . '.initiator.circleId is different than ' . $prefix . '.id');
				}
				$this->confirmMemberData($initiator, $compareToInitiator, 'owner', false, $params);
			}
		}

		if ($versa) {
			$this->confirmCircleData($compareTo, $circle);
		}
	}

	/**
	 * @param Member $member
	 * @param Member $compareTo
	 * @param string $prefix
	 * @param bool $versa
	 * @param array $params
	 *
	 * @throws Exception
	 */
	private function confirmMemberData(
		Member $member,
		Member $compareTo,
		string $prefix = 'member',
		bool $versa = false,
		array $params = []
	) {
		$this->compare($compareTo->getId(), $member->getId(), $prefix . '.id', $params);
		$this->compare($compareTo->getCircleId(), $member->getCircleId(), $prefix . '.circleId', $params);
		$this->compare($compareTo->getSingleId(), $member->getSingleId(), $prefix . '.singleId', $params);
		$this->compare($compareTo->getUserId(), $member->getUserId(), $prefix . '.userId', $params);
		$this->compare(
			$compareTo->getDisplayName(), $member->getDisplayName(), $prefix . '.displayName', $params
		);
		$this->compareInt($compareTo->getUserType(), $member->getUserType(), $prefix . '.userType');
		$this->compare($compareTo->getInstance(), $member->getInstance(), $prefix . '.instance', $params);
		$this->compareInt($compareTo->getLevel(), $member->getLevel(), $prefix . '.level', true);
		$this->compare($compareTo->getStatus(), $member->getStatus(), $prefix . '.status', $params);

		if ($compareTo->hasBasedOn()) {
			if (!$member->hasBasedOn()) {
				throw new Exception('empty ' . $prefix . '.basedOn');
			}
			$basedOn = $member->getBasedOn();
			$this->confirmCircleData(
				$basedOn,
				$compareTo->getBasedOn(),
				$prefix . '.basedOn',
				false,
				$params
			);
		}
	}


	/**
	 * @param string $expected
	 * @param string $compare
	 * @param string $def
	 * @param array $params
	 *
	 * @throws Exception
	 */
	private function compare(string $expected, string $compare, string $def, array $params) {
		if ($expected !== ''
			&& $this->feedStringWithParams($expected, $params) !== $compare) {
			throw new Exception($def . ': ' . $compare . ' (' . $expected . ')');
		}
	}

	/**
	 * @param int $expected
	 * @param int $compare
	 * @param string $def
	 * @param array $params
	 * @param bool $force
	 *
	 * @throws Exception
	 */
	private function compareInt(int $expected, int $compare, string $def, bool $force = false) {
		if (($expected > 0 || ($force && $expected >= 0))
			&& $expected !== $compare) {
			throw new Exception('wrong ' . $def . ': ' . $compare . ' (' . $expected . ')');
		}
	}


	/**
	 * @param bool $localIncluded
	 *
	 * @return array
	 */
	private function getInstances(bool $localIncluded = true): array {
		$instances = self::$INSTANCES;
		if (!$localIncluded) {
			$instances = array_diff($instances, [$this->local]);
		}

		return $instances;
	}


	/**
	 * @param array $circles
	 * @param string $userId
	 * @param string $instance
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	private function getSingleCircleForMember(array $circles, string $userId, string $instance): Circle {
		foreach ($circles as $circle) {
			$owner = $circle->getOwner();
			if ($owner->getUserId() === $userId && $owner->getInstance() === $instance) {
				return $circle;
			}
		}

		throw new CircleNotFoundException('cannot find ' . $userId . ' in the list of Single Circle');
	}


	/**
	 * @param string $instanceId
	 * @param string $name
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	private function getCircleByName(string $instanceId, string $name): Circle {
		if (array_key_exists($instanceId, $this->circles)
			&& array_key_exists($name, $this->circles[$instanceId])) {
			return $this->circles[$instanceId][$name];
		}

		throw new CircleNotFoundException(
			'cannot extract \'' . $name . '\' from the list of generated Circles'
		);
	}


	/**
	 * @param array $circles
	 * @param string $name
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	private function getCircleFromList(array $circles, string $name): Circle {
		foreach ($circles as $circle) {
			if ($circle->getName() === $name) {
				return $circle;
			}
		}

		throw new CircleNotFoundException(
			'cannot extract  \'' . $name . '\' from the list of provided Circles'
		);
	}


	/**
	 * @param string $instance
	 * @param string $key
	 *
	 * @return string
	 * @throws ItemNotFoundException
	 */
	private function getConfig(string $instance, string $key): string {
		$config = $this->getConfigInstance($instance);

		return $this->get($key, $config);
	}

	/**
	 * @param string $instance
	 * @param string $key
	 *
	 * @return array
	 * @throws ItemNotFoundException
	 */
	private function getConfigArray(string $instance, string $key): array {
		$config = $this->getConfigInstance($instance);

		return $this->getArray($key, $config);
	}


	/**
	 * @param string $instance
	 *
	 * @return array
	 * @throws ItemNotFoundException
	 */
	private function getConfigInstance(string $instance): array {
		foreach ($this->getArray('instances', $this->config) as $item) {
			if (strtolower($this->get('id', $item)) === strtolower($instance)) {
				return $item;
			}
		}

		throw new ItemNotFoundException($instance . ' not found');
	}


	/**
	 * @param $instanceId
	 *
	 * @return array
	 * @throws ItemNotFoundException
	 */
	private function getInstanceUsers($instanceId): array {
		return $this->getConfigArray($instanceId, 'users');
	}


	/**
	 * @param string $instance
	 * @param string $cmd
	 * @param bool $exceptionOnFail
	 * @param bool $jsonAsOutput
	 * @param array $env
	 *
	 * @return array
	 * @throws ItemNotFoundException
	 * @throws Exception
	 */
	private function occ(
		string $instance,
		string $cmd,
		bool $exceptionOnFail = true,
		bool $jsonAsOutput = true,
		array $env = []
	): ?array {
		$configInstance = $this->getConfigInstance($instance);
		$path = $this->get('path', $configInstance);
		$occ = rtrim($path, '/') . '/occ';

		$command = array_merge([$occ], explode(' ', $cmd));
		if ($jsonAsOutput) {
			$command = array_merge($command, ['--output=json']);
		}
		$process = new Process($command);
		$process->run(null, $env);

		if ($exceptionOnFail && !$process->isSuccessful()) {
			throw new Exception(implode(' ', $command) . ' failed');
		}

		$output = json_decode($process->getOutput(), true);
		if (!is_array($output)) {
			return null;
		}

		return $output;
	}



	//
	//
	//


	/**
	 * @param string $title
	 */
	private function t(string $title): void {
		$this->output->writeln('');
		$this->output->writeln('<comment>### ' . $title . '</comment>');
		$this->output->writeln('');
	}

	/**
	 * @param string $processing
	 */
	private function p(string $processing): void {
		$this->pOn = true;
		$this->output->write('- ' . $processing . ': ');
	}

	/**
	 * @param string $more
	 */
	private function pm(string $more): void {
		$this->output->write($more . ' ');
	}

	/**
	 * @param bool $result
	 * @param string $info
	 */
	private function r(bool $result = true, string $info = ''): void {
		$this->pOn = false;
		if ($result) {
			$this->output->writeln('<info>' . (($info !== '') ? $info : 'done') . '</info>');
		} else {
			$this->output->writeln('<error>' . (($info !== '') ? $info : 'done') . '</error>');
		}
	}


	/**
	 * @param string $instanceId
	 * @param string $circleName
	 * @param string $userId
	 * @param string $type
	 *
	 * @return Member
	 * @throws CircleNotFoundException
	 * @throws InvalidItemException
	 * @throws ItemNotFoundException
	 */
	private function processMemberAdd(string $instanceId, string $circleName, string $userId, string $type
	): Member {
		$circle = $this->getCircleByName($instanceId, $circleName);
		$dataAddedMember =
			$this->occ(
				$instanceId,
				'circles:members:add ' . $circle->getSingleId() . ' ' . $userId . ' --type ' . $type
			);
		/** @var Member $addedMember */
		$addedMember = $this->deserialize($dataAddedMember, Member::class);


		echo 'ADDEDMEMBER: ' . json_encode($addedMember, JSON_PRETTY_PRINT) . "\n";

		$federatedUser = $this->federatedUsers[$instanceId][$userId];
		echo 'FEDERATEDUER: ' . json_encode($federatedUser, JSON_PRETTY_PRINT) . "\n";

		return $addedMember;
	}
}
