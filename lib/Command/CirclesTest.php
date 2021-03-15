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


use daita\MySmallPhpTools\Exceptions\ItemNotFoundException;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CoreQueryBuilder;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\GroupService;
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
	private $members = [];

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
				 'am-i-aware-this-will-delete-all-my-data', '', InputOption::VALUE_REQUIRED,
				 'Well, are you ?', ''
			 );
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws ItemNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->output = $output;
		if ($input->getOption('am-i-aware-this-will-delete-all-my-data') == 'yes') {
			try {
				$this->testCirclesApp();
			} catch (Exception $e) {
				if ($this->pOn) {
					$this->output->writeln('<error>' . $e->getMessage() . '</error>');
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
	 * @throws ItemNotFoundException
	 */
	private function testCirclesApp() {
		$this->t('Initialisation');
		$this->loadConfiguration();
		$this->initEnvironment();
		$this->confirmVersion();
		$this->confirmEmptyCircles();
		$this->syncCircles();

		$this->t('Fresh installation status');
		$this->statusFreshInstances();

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
		$this->p('Init environment');
		foreach ($this->getInstances(false) as $instance) {
			$this->pm($instance);
			$this->occ($instance, 'circles:clean --uninstall', false, false);
			$this->occ($instance, 'app:enable circles', true, false);
		}
		$this->r();

		$this->p('Init environment on local');
		$this->coreQueryBuilder->cleanDatabase();
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


	private function statusFreshInstances() {
		foreach ($this->getInstances() as $instanceId) {
			$this->p('Circles on ' . $instanceId);
			$result = $this->occ($instanceId, 'circles:manage:list --all');
			$this->r(true, sizeof($result) . ' circles');

			$members = $groups = [];
			foreach ($result as $item) {
				$circle = new Circle();
				$circle->import($item);
				if ($circle->isConfig(Circle::CFG_SINGLE)) {
					$members[] = $circle;
				}

				if ($circle->getSource() === GroupService::GROUP_TYPE) {
					$groups[] = $circle;
				}
			}

			$instance = $this->getConfig($instanceId, 'config.frontal_cloud_id');
			foreach ($this->getConfigArray($instanceId, 'users') as $userId) {
				$this->p('Searching Single Circle for <comment>' . $userId . '@' . $instance . '</comment>');
				$circle = $this->getSingleCircleForMember($members, $userId, $instance);

				$compareToOwner = new Member();
				$compareToOwner->setUserId($userId)
							   ->setUserType(Member::TYPE_USER)
							   ->setInstance($instance)
							   ->setDisplayName($userId)
							   ->setId('{CIRCLEID}')
							   ->setCircleId('{CIRCLEID}')
							   ->setSingleId('{CIRCLEID}')
							   ->setStatus(Member::STATUS_MEMBER)
							   ->setLevel(Member::LEVEL_OWNER);
				$compareTo = new Circle();
				$compareTo->setOwner($compareToOwner)
						  ->setName('single:' . $userId . ':{CIRCLEID}')
						  ->setDisplayName('single:' . $userId . ':{CIRCLEID}');

				$this->confirmCircleData($circle, $compareTo);
				$this->r(true, $circle->getId());
			}

			$this->p('Searching Single Circle for <comment>Circles App</comment>');
			$circle = $this->getSingleCircleForMember($members, 'circles', $instance);
			$compareToOwner = new Member();
			$compareToOwner->setUserId(Application::APP_ID)
						   ->setUserType(Member::TYPE_APP)
						   ->setInstance($instance)
						   ->setDisplayName(Application::APP_ID)
						   ->setId('{CIRCLEID}')
						   ->setCircleId('{CIRCLEID}')
						   ->setSingleId('{CIRCLEID}')
						   ->setStatus(Member::STATUS_MEMBER)
						   ->setLevel(Member::LEVEL_OWNER);

			$compareTo = new Circle();
			$compareTo->setOwner($compareToOwner)
					  ->setConfig(Circle::CFG_SINGLE)
//					  ->setConfig(Circle::CFG_HIDDEN + Circle::CFG_NO_OWNER + Circle::CFG_SYSTEM)
					  ->setName('app:circles:{CIRCLEID}')
					  ->setDisplayName('app:circles:{CIRCLEID}');

			$this->confirmCircleData($circle, $compareTo);
			$this->r(true, $circle->getId());


			$this->output->writeln('');
		}

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
		$currMember = null;
		foreach ($circles as $circle) {
			$owner = $circle->getOwner();
			if ($owner->getUserId() === $userId && $owner->getInstance() === $instance) {
				return $circle;
			}
		}

		throw new CircleNotFoundException('cannot find ' . $userId . ' in the list of Single Circle');
	}


	/**
	 * @param Circle $circle
	 * @param Circle $compareTo
	 *
	 * @throws Exception
	 */
	private function confirmCircleData(Circle $circle, Circle $compareTo) {
		if ($circle->isConfig(Circle::CFG_SINGLE) && $circle->getConfig() !== Circle::CFG_SINGLE) {
			throw new Exception('Circle is set as Single but have more flags');
		}

		$owner = $circle->getOwner();
		$compareToOwner = $compareTo->getOwner();

		$params = [
			'CIRCLEID' => $circle->getId()
		];
		
		if ($compareTo->getName() !== ''
			&& $this->feedStringWithParams($compareTo->getName(), $params) !== $circle->getName()) {
			throw new Exception('wrong circle.name');
		}
		if ($compareTo->getDisplayName() !== ''
			&& $this->feedStringWithParams($compareTo->getDisplayName(), $params)
			   !== $circle->getDisplayName()) {
			throw new Exception('wrong circle.displayName');
		}
		if ($compareTo->getConfig() > 0
			&& $compareTo->getConfig() !== $circle->getConfig()) {
			throw new Exception('wrong circle.source');
		}
		if ($owner->getCircleId() !== $circle->getId()) {
			throw new Exception('owner.circleId is different than circle.id');
		}
		if ($compareToOwner->getId() !== ''
			&& $this->feedStringWithParams($compareToOwner->getId(), $params) !== $owner->getId()) {
			throw new Exception('wrong owner.memberId');
		}
		if ($compareToOwner->getCircleId() !== ''
			&& $this->feedStringWithParams($compareToOwner->getCircleId(), $params)
			   !== $owner->getCircleId()) {
			throw new Exception('wrong owner.circleId');
		}
		if ($compareToOwner->getSingleId() !== ''
			&& $this->feedStringWithParams($compareToOwner->getSingleId(), $params)
			   !== $owner->getSingleId()) {
			throw new Exception('wrong owner.singleId');
		}
		if ($compareToOwner->getUserId() !== ''
			&& $this->feedStringWithParams($compareToOwner->getUserId(), $params) !== $owner->getUserId()) {
			throw new Exception('wrong owner.userId');
		}
		if ($compareToOwner->getInstance() !== ''
			&& $this->feedStringWithParams($compareToOwner->getInstance(), $params)
			   !== $owner->getInstance()) {
			throw new Exception('wrong owner.instance');
		}
		if ($compareToOwner->getUserType() > 0
			&& $compareToOwner->getUserType() !== $owner->getUserType()) {
			throw new Exception('wrong owner.userType');
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
			$this->output->writeln('<error>fail</error>');
		}
	}

}

