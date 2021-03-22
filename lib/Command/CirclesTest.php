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


use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Exceptions\ItemNotFoundException;
use daita\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Deserialize;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CoreQueryBuilder;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
	use TNC22Deserialize;


	static $INSTANCES = [
		'global-scale-1',
		'global-scale-2',
		'global-scale-3',
		'passive',
		'external',
		'trusted'
	];


	/** @var CoreQueryBuilder */
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
	 * @param CoreQueryBuilder $coreQueryBuilder
	 * @param ConfigService $configService
	 */
	public function __construct(CoreQueryBuilder $coreQueryBuilder, ConfigService $configService) {
		parent::__construct();

		$this->coreQueryBuilder = $coreQueryBuilder;
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
			 ->addOption('skip-setup', '', InputOption::VALUE_NONE, 'Bypass Circles Setup');
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
		$this->t('Bootup');
		$this->loadConfiguration();

		if (!$this->input->getOption('skip-setup')) {
			$this->t('Initialisation');
			if (!$this->input->getOption('skip-init')) {
				$this->initEnvironment();
			}
			$this->reloadCirclesApp();
			$this->configureCirclesApp();
			$this->confirmVersion();
			$this->confirmEmptyCircles();
			$this->syncCircles();

			$this->t('Fresh installation status');
			$this->statusFreshInstances();
			$this->createRemoteLink();
		}

		$this->t('Building Local Database');
		$this->buildingLocalDatabase();

		$this->t('Testing Basic Circle Creation');
		$this->basicCirclesCreation();

		$this->t('Adding local users and moderators');
		$this->basicCirclesMembers();

		$this->t('Adding globalscale admin');


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
		foreach ($this->getInstances() as $instance) {
			$this->p('Creating users on ' . $instance);
			foreach ($this->getConfigArray($instance, 'users') as $userId) {
				$this->pm($userId);
				$this->occ($instance, 'user:add ' . $userId, false, false);
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
									  ->setDisplayName('user:' . $userId . ':{CIRCLEID}');

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
						  ->setDisplayName('user:' . $userId . ':{CIRCLEID}');

				$this->confirmCircleData($circle, $compareTo);
				$this->r(true, $circle->getId());
			}

			$this->p('Checking Single Circle for <comment>Circles App</comment>');
			$circle = $this->getSingleCircleForMember($membersList, 'circles', $instance);

			$compareToOwnerBasedOn = new Circle();
			$compareToOwnerBasedOn->setConfig(Circle::CFG_SINGLE | Circle::CFG_ROOT)
								  ->setName('app:circles:{CIRCLEID}')
								  ->setDisplayName('app:circles:{CIRCLEID}');

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
					  ->setDisplayName('app:circles:{CIRCLEID}');

			$this->confirmCircleData($circle, $compareTo);
			$this->r(true, $circle->getId());

			foreach ($this->getConfigArray($instanceId, 'groups') as $groupId => $members) {
				$this->p('Checking Circle for <comment>' . $groupId . '@' . $instance . '</comment>');
				$circle = $this->getCircleByName($groupsList, 'group:' . $groupId);

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
				$this->r(true, $circle->getId());
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
	private function basicCirclesCreation() {
		$this->p('Creating basic circle');

		$localInstanceId = 'global-scale-1';
		$owner = 'test1';
		$name = 'test_001';
		$dataCreatedCircle001 = $this->occ($localInstanceId, 'circles:manage:create ' . $owner . ' ' . $name);
		/** @var Circle $createdCircle001 */
		$createdCircle001 = $this->deserialize($dataCreatedCircle001, Circle::class);
		$this->r(true, $createdCircle001->getId());;

		$this->p('Comparing data returned at creation');
		if ($createdCircle001->getId() === '' || $createdCircle001->getOwner()->getId() === '') {
			throw new InvalidItemException('empty id or owner.member_id');
		}

		$knownOwner = new Member();
		$knownOwner->importFromIFederatedUser($this->federatedUsers[$localInstanceId][$owner]);
		$knownOwner->setCircleId($createdCircle001->getId());
		$knownOwner->setLevel(Member::LEVEL_OWNER);
		$knownOwner->setStatus(Member::STATUS_MEMBER);
		$knownOwner->setId($createdCircle001->getOwner()->getId());

		$compareTo = new Circle();
		$compareTo->setOwner($knownOwner)
				  ->setId($createdCircle001->getId())
				  ->setInitiator($knownOwner)
				  ->setConfig(Circle::CFG_CIRCLE)
				  ->setName($name)
				  ->setDisplayName($name);

		$this->confirmCircleData($createdCircle001, $compareTo, 'circle', true);
		$this->r();


		$this->p('Comparing local stored data');
		$dataCircle = $this->occ($localInstanceId, 'circle:manage:details ' . $createdCircle001->getId());

		/** @var Circle $tmpCircle */
		$tmpCircle = $this->deserialize($dataCircle, Circle::class);
		$this->confirmCircleData($tmpCircle, $createdCircle001);
		$this->r(true, $tmpCircle->getId());

		$links = $this->getConfigArray('global-scale-1', 'remote');
		foreach ($this->getInstances(false) as $instanceId) {
			$this->p('Comparing data stored on ' . $instanceId);
			$dataCircle =
				$this->occ($instanceId, 'circle:manage:details ' . $createdCircle001->getId(), false);

			if ($instanceId === $localInstanceId || $links[$instanceId] === 'GlobalScale') {
				/** @var Circle $tmpCircle */
				$tmpCircle = $this->deserialize($dataCircle, Circle::class);
				// we reset some data that should not be available on remote instance
				$createdCircle001->setInitiator(null)
								 ->getOwner()->setBasedOn(null);
				$this->confirmCircleData($tmpCircle, $createdCircle001);
				$this->r(true, $tmpCircle->getId());
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
	 */
	private function basicCirclesMembers() {
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
				'CIRCLEID' => $circle->getId()
			];
		}

		$this->compare($compareTo->getId(), $circle->getId(), $prefix . '.id', $params);
		$this->compare($compareTo->getName(), $circle->getName(), $prefix . '.name', $params);
		$this->compare(
			$compareTo->getDisplayName(), $circle->getDisplayName(), $prefix . '.displayName', $params
		);
		$this->compareInt($compareTo->getConfig(), $circle->getConfig(), $prefix . '.config', $params, true);
		$this->compareInt($compareTo->getSource(), $circle->getSource(), $prefix . '.source', $params);

		if ($compareTo->hasOwner()) {
			$compareToOwner = $compareTo->getOwner();
			if ($compareToOwner !== null) {
				$owner = $circle->getOwner();
				if ($owner === null) {
					throw new Exception('empty owner');
				}
				if ($owner->getCircleId() !== $circle->getId()) {
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
				if ($initiator->getCircleId() !== $circle->getId()) {
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
		$this->compareInt($compareTo->getUserType(), $member->getUserType(), $prefix . '.userType', $params);
		$this->compare($compareTo->getInstance(), $member->getInstance(), $prefix . '.instance', $params);
		$this->compareInt($compareTo->getLevel(), $member->getLevel(), $prefix . '.level', $params, true);
		$this->compare($compareTo->getStatus(), $member->getStatus(), $prefix . '.status', $params);

		$compareToBasedOn = $compareTo->getBasedOn();
		if ($compareToBasedOn !== null) {
			$basedOn = $member->getBasedOn();
			if ($basedOn === null) {
				throw new Exception('empty ' . $prefix . '.basedOn');
			}
			$this->confirmCircleData($basedOn, $compareToBasedOn, $prefix . '.basedOn', false, $params);
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
	private function compareInt(int $expected, int $compare, string $def, array $params, bool $force = false
	) {
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
	 * @param array $circles
	 * @param string $name
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	private function getCircleByName(array $circles, string $name): Circle {
		foreach ($circles as $circle) {
			if ($circle->getName() === $name) {
				return $circle;
			}
		}

		throw new CircleNotFoundException('cannot find \'' . $name . '\' in the list of Circles');
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
	 * @param string $instance
	 * @param string $cmd
	 * @param bool $exceptionOnFail
	 * @param bool $jsonAsOutput
	 *
	 * @return array
	 * @throws ItemNotFoundException
	 * @throws Exception
	 */
	private function occ(
		string $instance,
		string $cmd,
		bool $exceptionOnFail = true,
		bool $jsonAsOutput = true
	): ?array {
		$configInstance = $this->getConfigInstance($instance);
		$path = $this->get('path', $configInstance);
		$occ = rtrim($path, '/') . '/occ';

		$command = array_merge([$occ], explode(' ', $cmd));
		if ($jsonAsOutput) {
			$command = array_merge($command, ['--output=json']);
		}
		$process = new Process($command);
		$process->run();

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

}

