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

use daita\MySmallPhpTools\Exceptions\RequestContentException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\RequestResultNotJsonException;
use daita\MySmallPhpTools\Exceptions\RequestResultSizeException;
use daita\MySmallPhpTools\Exceptions\RequestServerException;
use daita\MySmallPhpTools\Model\Nextcloud\NC19Request;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Traits\Nextcloud\TNC19Request;
use daita\MySmallPhpTools\Traits\TRequest;
use Exception;
use OC\Core\Command\Base;
use OC\User\NoUserException;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MembersService;
use OCP\IL10N;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MembersCreate
 *
 * @package OCA\Circles\Command
 */
class MembersCreate extends Base {


	use TNC19Request;


	/** @var IL10N */
	private $l10n;

	/** @var IUserManager */
	private $userManager;

	/** @var MembersService */
	private $membersService;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var ConfigService */
	private $configService;


	/**
	 * MembersCreate constructor.
	 *
	 * @param IL10N $l10n
	 * @param IUserManager $userManager
	 * @param MembersService $membersService
	 * @param MembersRequest $membersRequest
	 * @param ConfigService $configService
	 */
	public function __construct(
		IL10N $l10n, IUserManager $userManager, MembersService $membersService,
		MembersRequest $membersRequest, ConfigService $configService
	) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->membersService = $membersService;
		$this->membersRequest = $membersRequest;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:create')
			 ->setDescription('create a new member')
			 ->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			 ->addArgument('user', InputArgument::REQUIRED, 'username of the member')
			 ->addArgument('level', InputArgument::OPTIONAL, 'level of the member', 'member');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws NoUserException
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = $input->getArgument('circle_id');
		$userId = $input->getArgument('user');
		$level = $input->getArgument('level');

		$instance = '';
		$user = $this->userManager->get($userId);
		if ($user === null) {
			$userId = $this->findUserFromLookup($userId, $instance);
		} else {
			$userId = $user->getUID();
		}

		if ($userId === '') {
			throw new NoUserException('user does not exist');
		}

		$levels = [
			'member'    => Member::LEVEL_MEMBER,
			'moderator' => Member::LEVEL_MODERATOR,
			'admin'     => Member::LEVEL_ADMIN,
			'owner'     => Member::LEVEL_OWNER
		];

		if (!key_exists(strtolower($level), $levels)) {
			throw new Exception('unknown level: ' . json_encode(array_keys($levels)));
		}

		$level = $levels[strtolower($level)];

		$this->membersService->addMember($circleId, $userId, Member::TYPE_USER, $instance, true);
		$this->membersService->levelMember($circleId, $userId, Member::TYPE_USER, $instance, $level, true);

		$member = $this->membersRequest->forceGetMember($circleId, $userId, Member::TYPE_USER, $instance);
		echo json_encode($member, JSON_PRETTY_PRINT) . "\n";

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

		$request = new NC19Request('/users', Request::TYPE_GET);
		$this->configService->configureRequest($request);
		$request->setProtocols(['https', 'http']);
		$request->addData('search', $search);
		$request->setAddressFromUrl($lookup);

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

